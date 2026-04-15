<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\CorrelationAnalysisServiceInterface;
use App\Contracts\MonitoredAssetRepositoryInterface;
use App\Contracts\PortfolioPositionRepositoryInterface;
use App\Contracts\PortfolioRiskServiceInterface;
use App\Contracts\RiskSettingsServiceInterface;
use App\Models\PortfolioPosition;
use Illuminate\Support\Collection;

class PortfolioRiskService implements PortfolioRiskServiceInterface
{
    public function __construct(
        private readonly RiskSettingsServiceInterface         $riskSettingsService,
        private readonly CorrelationAnalysisServiceInterface  $correlationAnalysisService,
        private readonly PortfolioMarkToMarketService         $markToMarketService,
        private readonly PortfolioPositionRepositoryInterface $portfolioPositionRepository,
        private readonly MonitoredAssetRepositoryInterface    $monitoredAssetRepository,
    ) {
    }

    public function summary(int $userId): array
    {
        $settings = $this->riskSettingsService->getForUser($userId);
        $this->markToMarketService->refreshForUser($userId);

        $positions    = $this->portfolioPositionRepository->findOpenByUserWithRelations($userId);
        $snapshots    = $this->markToMarketService->snapshots($positions);
        $snapshotRows = array_map(static fn ($item): array => $item->toArray(), $snapshots);

        $capitalAllocated = round(array_sum(array_map(
            static fn (array $item): float => (float) $item['current_value'],
            $snapshotRows,
        )), 2);

        $capitalFree = round(max(0, $settings->totalCapital - $capitalAllocated), 2);

        $openRiskAmount = round(array_sum(array_map(
            static fn (array $item): float => max(0.0, (float) (($item['current_price'] ?? 0) - ($item['stop_price'] ?? 0))) * (float) $item['quantity'],
            $snapshotRows,
        )), 2);

        $openRiskPercent = $settings->totalCapital > 0
            ? round(($openRiskAmount / $settings->totalCapital) * 100, 4)
            : 0.0;

        $largestPositionPercent = $settings->totalCapital > 0 && $snapshotRows !== []
            ? round(max(array_map(
                static fn (array $item): float => ((float) $item['current_value'] / $settings->totalCapital) * 100,
                $snapshotRows,
            )), 4)
            : 0.0;

        $largestRiskPercent = $settings->totalCapital > 0 && $snapshotRows !== []
            ? round(max(array_map(
                static fn (array $item): float => max(0.0, ((float) (($item['current_price'] ?? 0) - ($item['stop_price'] ?? 0))) * (float) $item['quantity']) / $settings->totalCapital * 100,
                $snapshotRows,
            )), 4)
            : 0.0;

        $exposure     = $this->buildExposure(
            rows:             $snapshotRows,
            capitalTotal:     $settings->totalCapital,
            maxAssetPercent:  $settings->maxPositionSizePercent,
            maxSectorPercent: $settings->maxSectorExposurePercent,
        );
        $correlations = $this->correlations($userId);

        $violations = [];

        if ($openRiskPercent > $settings->maxPortfolioRiskPercent) {
            $violations[] = 'Risco aberto acima do limite configurado.';
        }

        if (count($snapshotRows) > $settings->maxOpenPositions) {
            $violations[] = 'Quantidade de posições abertas acima do máximo permitido.';
        }

        if ($exposure['over_asset_limit'] !== []) {
            $violations[] = 'Concentração por ativo excedida.';
        }

        if ($exposure['over_sector_limit'] !== []) {
            $violations[] = 'Concentração por setor excedida.';
        }

        if (count((array) ($correlations['high_correlation_assets'] ?? [])) > $settings->maxCorrelatedPositions) {
            $violations[] = 'Quantidade de ativos correlacionados acima do limite.';
        }

        return [
            'settings'                        => $settings->toArray(),
            'capital_total'                   => round($settings->totalCapital, 2),
            'capital_allocated'               => $capitalAllocated,
            'capital_free'                    => $capitalFree,
            'open_positions'                  => count($snapshotRows),
            'open_risk_amount'                => $openRiskAmount,
            'open_risk_percent'               => $openRiskPercent,
            'largest_position_percent'        => $largestPositionPercent,
            'largest_individual_risk_percent' => $largestRiskPercent,
            'exposure'                        => $exposure,
            'correlations'                    => $correlations,
            'blocked'                         => $violations !== [],
            'violations'                      => $violations,
        ];
    }

    public function exposure(int $userId): array
    {
        $settings = $this->riskSettingsService->getForUser($userId);
        $this->markToMarketService->refreshForUser($userId);

        $positions = $this->portfolioPositionRepository->findOpenByUserWithRelations($userId);
        $snapshots = $this->markToMarketService->snapshots($positions);

        $rows = array_map(static fn ($item): array => $item->toArray(), $snapshots);

        return $this->buildExposure(
            rows:             $rows,
            capitalTotal:     $settings->totalCapital,
            maxAssetPercent:  $settings->maxPositionSizePercent,
            maxSectorPercent: $settings->maxSectorExposurePercent,
        );
    }

    public function correlations(int $userId): array
    {
        $positions = $this->portfolioPositionRepository->findOpenByUserWithRelations($userId);
        $tickers   = $this->extractTickers($positions);

        return $this->correlationAnalysisService->highCorrelationSummary(
            tickers:      $tickers,
            lookbackDays: (int) config('market.correlations.lookback_days', 90),
        );
    }

    public function canOpenPosition(int $userId, int $monitoredAssetId, float $positionValue, float $riskAmount): array
    {
        $settings = $this->riskSettingsService->getForUser($userId);
        $summary  = $this->summary($userId);

        $violations = [];
        $warnings   = [];

        if ($summary['open_positions'] >= $settings->maxOpenPositions) {
            $violations[] = 'Limite de posições abertas atingido.';
        }

        if ($summary['capital_free'] < $positionValue) {
            $violations[] = 'Capital livre insuficiente para a alocação sugerida.';
        }

        $positionPercent = $settings->totalCapital > 0
            ? ($positionValue / $settings->totalCapital) * 100
            : 0.0;

        if ($positionPercent > $settings->maxPositionSizePercent) {
            $violations[] = 'Tamanho da posição acima do limite máximo por ativo.';
        }

        $newRiskPercent = $settings->totalCapital > 0
            ? $summary['open_risk_percent'] + (($riskAmount / $settings->totalCapital) * 100)
            : $summary['open_risk_percent'];

        if ($newRiskPercent > $settings->maxPortfolioRiskPercent) {
            $violations[] = 'Risco total projetado acima do limite da carteira.';
        }

        $asset  = $this->monitoredAssetRepository->findById($monitoredAssetId);
        $ticker = strtoupper((string) ($asset?->ticker ?? ''));
        $sector = (string) ($asset?->sectorMapping?->sector ?? $asset?->sector ?? 'Outros');

        $assetExposure  = (array) ($summary['exposure']['by_asset'] ?? []);
        $sectorExposure = (array) ($summary['exposure']['by_sector'] ?? []);

        $projectedAsset = (float) ($assetExposure[$ticker]['percent'] ?? 0.0) + $positionPercent;

        if ($ticker !== '' && $projectedAsset > $settings->maxPositionSizePercent) {
            $violations[] = "Exposição projetada em {$ticker} acima do limite por ativo.";
        }

        $projectedSector = (float) ($sectorExposure[$sector]['percent'] ?? 0.0) + $positionPercent;

        if ($projectedSector > $settings->maxSectorExposurePercent) {
            $violations[] = "Exposição projetada no setor {$sector} acima do limite.";
        }

        $openPositions = $this->portfolioPositionRepository->findOpenByUserWithRelations($userId);
        $openTickers   = $this->extractTickers($openPositions);

        if ($ticker !== '') {
            $openTickers[] = $ticker;
        }

        $correlationSummary = $this->correlationAnalysisService->highCorrelationSummary(
            tickers:      array_values(array_unique($openTickers)),
            lookbackDays: (int) config('market.correlations.lookback_days', 90),
        );

        if (count((array) ($correlationSummary['high_correlation_assets'] ?? [])) > $settings->maxCorrelatedPositions) {
            $violations[] = 'Inclusão da posição eleva o cluster de correlação acima do permitido.';
        }

        if ($positionPercent >= ($settings->maxPositionSizePercent * 0.85)) {
            $warnings[] = 'Posição próxima do limite máximo por ativo.';
        }

        return [
            'allowed'    => $violations === [],
            'violations' => $violations,
            'warnings'   => $warnings,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function buildExposure(
        array $rows,
        float $capitalTotal,
        float $maxAssetPercent,
        float $maxSectorPercent,
    ): array {
        $byAsset  = [];
        $bySector = [];

        foreach ($rows as $item) {
            $ticker = (string) ($item['ticker'] ?? '');
            $sector = (string) ($item['sector'] ?? 'Outros');
            $value  = (float) ($item['current_value'] ?? 0.0);

            if (! isset($byAsset[$ticker])) {
                $byAsset[$ticker] = ['value' => 0.0, 'percent' => 0.0];
            }

            if (! isset($bySector[$sector])) {
                $bySector[$sector] = ['value' => 0.0, 'percent' => 0.0];
            }

            $byAsset[$ticker]['value']  += $value;
            $bySector[$sector]['value'] += $value;
        }

        foreach ($byAsset as $key => $item) {
            $byAsset[$key]['value']   = round((float) $item['value'], 2);
            $byAsset[$key]['percent'] = $capitalTotal > 0
                ? round(((float) $item['value'] / $capitalTotal) * 100, 4)
                : 0.0;
        }

        foreach ($bySector as $key => $item) {
            $bySector[$key]['value']   = round((float) $item['value'], 2);
            $bySector[$key]['percent'] = $capitalTotal > 0
                ? round(((float) $item['value'] / $capitalTotal) * 100, 4)
                : 0.0;
        }

        $overAssetLimit = array_keys(array_filter(
            $byAsset,
            static fn (array $item): bool => (float) $item['percent'] > $maxAssetPercent,
        ));

        $overSectorLimit = array_keys(array_filter(
            $bySector,
            static fn (array $item): bool => (float) $item['percent'] > $maxSectorPercent,
        ));

        return [
            'by_asset'          => $byAsset,
            'by_sector'         => $bySector,
            'over_asset_limit'  => array_values($overAssetLimit),
            'over_sector_limit' => array_values($overSectorLimit),
        ];
    }

    /**
     * @param  Collection<int, PortfolioPosition>  $positions
     * @return array<int, string>
     */
    private function extractTickers(Collection $positions): array
    {
        return $positions
            ->map(static fn (PortfolioPosition $position): string => strtoupper((string) $position->monitoredAsset?->ticker))
            ->filter(static fn (string $ticker): bool => $ticker !== '')
            ->values()
            ->all();
    }
}
