<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\MarketRegimeServiceInterface;
use App\DTOs\MarketRegimeDTO;
use App\Enums\MarketRegime;
use App\Models\AssetAnalysisScore;
use App\Models\MarketIndex;

class MarketRegimeService implements MarketRegimeServiceInterface
{
    public function current(): MarketRegimeDTO
    {
        $history = MarketIndex::query()
            ->where('symbol', 'IBOV')
            ->orderByDesc('trade_date')
            ->limit(260)
            ->get(['trade_date', 'close'])
            ->sortBy('trade_date')
            ->values();

        if ($history->count() < 30) {
            return new MarketRegimeDTO(
                regime: MarketRegime::NEUTRAL->value,
                contextScore: $this->contextScoreFromRegime(MarketRegime::NEUTRAL->value),
                metrics: [
                    'samples' => $history->count(),
                    'reason' => 'insufficient_data',
                ],
            );
        }

        $closes = $history->pluck('close')->map(static fn ($value): float => (float) $value)->all();

        $current = end($closes) ?: 0.0;
        $sma200 = $this->simpleMovingAverage($closes, 200);
        $sma50 = $this->simpleMovingAverage($closes, 50);

        $sliceForPastSma = array_slice($closes, 0, -10);
        $sma50Past = $this->simpleMovingAverage($sliceForPastSma, 50);
        $slope50Percent = $sma50Past > 0 ? (($sma50 - $sma50Past) / $sma50Past) * 100 : 0.0;

        $change20Percent = $this->windowChangePercent($closes, 20);
        $volatility20 = $this->annualizedVolatilityPercent($closes, 20);
        $breadthPercent = $this->breadthPercent();

        $highVolThreshold = (float) config('market.regime.high_volatility_threshold', 38.0);

        $regime = MarketRegime::NEUTRAL;

        if ($volatility20 >= $highVolThreshold) {
            $regime = MarketRegime::HIGH_VOLATILITY;
        } elseif ($current < ($sma200 * 0.96) && $slope50Percent < -0.4 && $change20Percent <= -4.0) {
            $regime = MarketRegime::BEAR;
        } elseif ($current < $sma200 && $change20Percent <= -1.2) {
            $regime = MarketRegime::CORRECTION;
        } elseif ($current > $sma200 && $slope50Percent > 0.1 && $breadthPercent >= 55.0) {
            $regime = MarketRegime::BULL;
        }

        return new MarketRegimeDTO(
            regime: $regime->value,
            contextScore: $this->contextScoreFromRegime($regime->value),
            metrics: [
                'ibov_close' => round($current, 2),
                'sma_50' => round($sma50, 2),
                'sma_200' => round($sma200, 2),
                'sma_50_slope_percent' => round($slope50Percent, 4),
                'change_20d_percent' => round($change20Percent, 4),
                'volatility_20d_percent' => round($volatility20, 4),
                'breadth_percent' => round($breadthPercent, 4),
                'reference_date' => optional($history->last()->trade_date)?->toDateString(),
            ],
        );
    }

    public function rulesForRegime(string $regime): array
    {
        $defaults = [
            'min_score' => 75.0,
            'max_calls' => 5,
        ];

        $rules = (array) config("market.regime.rules.{$regime}", []);

        return [
            'min_score' => (float) ($rules['min_score'] ?? $defaults['min_score']),
            'max_calls' => (int) ($rules['max_calls'] ?? $defaults['max_calls']),
        ];
    }

    public function contextScoreFromRegime(string $regime): float
    {
        return (float) match ($regime) {
            MarketRegime::BULL->value => config('market.regime.context_scores.bull', 86),
            MarketRegime::CORRECTION->value => config('market.regime.context_scores.correction', 48),
            MarketRegime::BEAR->value => config('market.regime.context_scores.bear', 35),
            MarketRegime::HIGH_VOLATILITY->value => config('market.regime.context_scores.high_volatility', 42),
            default => config('market.regime.context_scores.neutral', 70),
        };
    }

    private function breadthPercent(): float
    {
        $latestDate = AssetAnalysisScore::query()->max('trade_date');

        if ($latestDate === null) {
            return 50.0;
        }

        $total = AssetAnalysisScore::query()->whereDate('trade_date', (string) $latestDate)->count();

        if ($total === 0) {
            return 50.0;
        }

        $strong = AssetAnalysisScore::query()
            ->whereDate('trade_date', (string) $latestDate)
            ->where('final_score', '>=', 70)
            ->count();

        return ($strong / $total) * 100;
    }

    /**
     * @param  array<int, float>  $series
     */
    private function simpleMovingAverage(array $series, int $window): float
    {
        if ($series === []) {
            return 0.0;
        }

        if (count($series) < $window) {
            return (float) (array_sum($series) / max(1, count($series)));
        }

        $slice = array_slice($series, -$window);

        return (float) (array_sum($slice) / $window);
    }

    /**
     * @param  array<int, float>  $series
     */
    private function windowChangePercent(array $series, int $window): float
    {
        $count = count($series);

        if ($count <= $window) {
            return 0.0;
        }

        $start = (float) $series[$count - $window - 1];
        $end = (float) $series[$count - 1];

        if ($start <= 0.0) {
            return 0.0;
        }

        return (($end - $start) / $start) * 100;
    }

    /**
     * @param  array<int, float>  $series
     */
    private function annualizedVolatilityPercent(array $series, int $window): float
    {
        $returns = [];

        $slice = count($series) > ($window + 1)
            ? array_slice($series, -($window + 1))
            : $series;

        for ($index = 1; $index < count($slice); $index++) {
            $previous = (float) $slice[$index - 1];
            $current = (float) $slice[$index];

            if ($previous <= 0.0) {
                continue;
            }

            $returns[] = ($current / $previous) - 1;
        }

        if ($returns === []) {
            return 0.0;
        }

        $mean = array_sum($returns) / count($returns);
        $variance = 0.0;

        foreach ($returns as $value) {
            $variance += ($value - $mean) ** 2;
        }

        $variance /= max(1, count($returns) - 1);

        return sqrt($variance) * sqrt(252) * 100;
    }
}
