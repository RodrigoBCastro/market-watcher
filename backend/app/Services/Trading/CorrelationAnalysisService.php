<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\AssetQuoteRepositoryInterface;
use App\Contracts\CorrelationAnalysisServiceInterface;
use App\Contracts\MonitoredAssetRepositoryInterface;

class CorrelationAnalysisService implements CorrelationAnalysisServiceInterface
{
    public function __construct(
        private readonly MonitoredAssetRepositoryInterface $monitoredAssetRepository,
        private readonly AssetQuoteRepositoryInterface     $assetQuoteRepository,
    ) {
    }

    public function correlationsForTickers(array $tickers, int $lookbackDays = 90): array
    {
        $tickers = $this->normalizeTickers($tickers);

        if (count($tickers) < 2) {
            return [];
        }

        $seriesByTicker = $this->returnsSeriesByTicker($tickers, $lookbackDays);

        $pairs = [];

        for ($indexA = 0; $indexA < count($tickers); $indexA++) {
            for ($indexB = $indexA + 1; $indexB < count($tickers); $indexB++) {
                $tickerA = $tickers[$indexA];
                $tickerB = $tickers[$indexB];

                $seriesA     = $seriesByTicker[$tickerA] ?? [];
                $seriesB     = $seriesByTicker[$tickerB] ?? [];
                $sharedDates = array_intersect(array_keys($seriesA), array_keys($seriesB));

                if (count($sharedDates) < 20) {
                    continue;
                }

                $valuesA = [];
                $valuesB = [];

                foreach ($sharedDates as $date) {
                    $valuesA[] = (float) $seriesA[$date];
                    $valuesB[] = (float) $seriesB[$date];
                }

                $correlation = $this->pearson($valuesA, $valuesB);

                if ($correlation === null) {
                    continue;
                }

                $absCorrelation = abs($correlation);

                $pairs[] = [
                    'ticker_a'        => $tickerA,
                    'ticker_b'        => $tickerB,
                    'correlation'     => round($correlation, 6),
                    'abs_correlation' => round($absCorrelation, 6),
                    'sample_size'     => count($sharedDates),
                    'strength'        => $this->strengthLabel($absCorrelation),
                ];
            }
        }

        usort(
            $pairs,
            static fn (array $left, array $right): int => $right['abs_correlation'] <=> $left['abs_correlation'],
        );

        return $pairs;
    }

    public function highCorrelationSummary(array $tickers, int $lookbackDays = 90): array
    {
        $threshold = (float) config('market.correlations.high_threshold', 0.75);
        $pairs     = $this->correlationsForTickers($tickers, $lookbackDays);

        $highPairs = array_values(array_filter(
            $pairs,
            static fn (array $item): bool => (float) $item['abs_correlation'] >= $threshold,
        ));

        $assetFrequency = [];

        foreach ($highPairs as $pair) {
            $assetFrequency[$pair['ticker_a']] = ($assetFrequency[$pair['ticker_a']] ?? 0) + 1;
            $assetFrequency[$pair['ticker_b']] = ($assetFrequency[$pair['ticker_b']] ?? 0) + 1;
        }

        arsort($assetFrequency);

        return [
            'threshold'                => $threshold,
            'pairs'                    => $highPairs,
            'high_correlation_assets'  => array_keys($assetFrequency),
            'asset_frequency'          => $assetFrequency,
            'max_cluster_size'         => $assetFrequency === [] ? 0 : max($assetFrequency),
        ];
    }

    /**
     * @param  array<int, string>  $tickers
     * @return array<string, array<string, float>>
     */
    private function returnsSeriesByTicker(array $tickers, int $lookbackDays): array
    {
        // Bulk-resolve ticker → id in one query
        $idsByTicker = $this->monitoredAssetRepository->findIdsByTickers($tickers);

        $series = [];

        foreach ($idsByTicker as $ticker => $assetId) {
            $quotes = $this->assetQuoteRepository
                ->findByAssetDescending($assetId, $lookbackDays + 5)
                ->sortBy('trade_date')
                ->values();

            $returnsByDate = [];

            for ($i = 1; $i < $quotes->count(); $i++) {
                $previous = (float) ($quotes[$i - 1]->close ?? 0.0);
                $current  = (float) ($quotes[$i]->close ?? 0.0);

                if ($previous <= 0.0) {
                    continue;
                }

                $date = $quotes[$i]->trade_date?->toDateString();

                if ($date === null) {
                    continue;
                }

                $returnsByDate[$date] = ($current / $previous) - 1;
            }

            $series[strtoupper($ticker)] = $returnsByDate;
        }

        return $series;
    }

    /**
     * @param  array<int, float>  $x
     * @param  array<int, float>  $y
     */
    private function pearson(array $x, array $y): ?float
    {
        $n = min(count($x), count($y));

        if ($n <= 1) {
            return null;
        }

        $sumX       = array_sum($x);
        $sumY       = array_sum($y);
        $sumXY      = 0.0;
        $sumXSquare = 0.0;
        $sumYSquare = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $xv = (float) $x[$i];
            $yv = (float) $y[$i];

            $sumXY      += $xv * $yv;
            $sumXSquare += $xv ** 2;
            $sumYSquare += $yv ** 2;
        }

        $numerator   = ($n * $sumXY) - ($sumX * $sumY);
        $denominator = sqrt((($n * $sumXSquare) - ($sumX ** 2)) * (($n * $sumYSquare) - ($sumY ** 2)));

        if ($denominator <= 0.0) {
            return null;
        }

        return $numerator / $denominator;
    }

    /**
     * @param  array<int, string>  $tickers
     * @return array<int, string>
     */
    private function normalizeTickers(array $tickers): array
    {
        $normalized = array_map(
            static fn ($ticker): string => strtoupper(trim((string) $ticker)),
            $tickers,
        );

        $normalized = array_values(array_filter($normalized, static fn (string $item): bool => $item !== ''));

        return array_values(array_unique($normalized));
    }

    private function strengthLabel(float $absCorrelation): string
    {
        if ($absCorrelation >= 0.85) {
            return 'very_high';
        }

        if ($absCorrelation >= 0.75) {
            return 'high';
        }

        if ($absCorrelation >= 0.55) {
            return 'moderate';
        }

        return 'low';
    }
}
