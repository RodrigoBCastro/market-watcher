<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\MonitoredAssetRepositoryInterface;
use App\Contracts\PortfolioSimulationServiceInterface;
use App\Contracts\PositionSizingServiceInterface;
use App\Contracts\RiskSettingsServiceInterface;
use App\Contracts\TradeCallRepositoryInterface;
use App\DTOs\PortfolioSimulationResultDTO;

class PortfolioSimulationService implements PortfolioSimulationServiceInterface
{
    public function __construct(
        private readonly RiskSettingsServiceInterface    $riskSettingsService,
        private readonly PositionSizingServiceInterface  $positionSizingService,
        private readonly TradeCallRepositoryInterface    $tradeCallRepository,
        private readonly MonitoredAssetRepositoryInterface $monitoredAssetRepository,
    ) {
    }

    public function simulate(int $userId, array $payload): PortfolioSimulationResultDTO
    {
        $settings = $this->riskSettingsService->getForUser($userId);

        $capitalTotal     = (float) ($payload['capital_total'] ?? $settings->totalCapital);
        $capitalAvailable = $capitalTotal;

        $candidates = $this->resolveCandidates($payload);

        $projectedRiskAmount    = 0.0;
        $projectedAllocated     = 0.0;
        $expectedReturnAmount   = 0.0;
        $optimisticAmount       = 0.0;
        $conservativeAmount     = 0.0;

        $exposureByAsset  = [];
        $exposureBySector = [];
        $calls            = [];

        foreach ($candidates as $candidate) {
            $entryPrice   = (float) ($candidate['entry_price'] ?? 0.0);
            $stopPrice    = isset($candidate['stop_price']) ? (float) $candidate['stop_price'] : null;
            $stopDistance = $this->stopDistance($candidate);

            if ($entryPrice <= 0.0 || $stopDistance <= 0.0 || $capitalAvailable <= 0.0) {
                continue;
            }

            $sizing = $this->positionSizingService->calculateForUser($userId, [
                'entry_price'              => $entryPrice,
                'stop_price'               => $stopPrice,
                'stop_distance_percent'    => $stopDistance,
                'capital_total'            => $capitalTotal,
                'risk_per_trade_percent'   => $candidate['risk_per_trade_percent'] ?? $settings->riskPerTradePercent,
                'available_capital'        => $capitalAvailable,
            ]);

            $positionValue = (float) $sizing->suggestedPositionValue;

            if ($positionValue <= 0.0) {
                continue;
            }

            $positionRiskAmount = $positionValue * ($stopDistance / 100);

            $projectedRiskAmount += $positionRiskAmount;
            $projectedAllocated  += $positionValue;
            $capitalAvailable     = max(0.0, $capitalAvailable - $positionValue);

            $rewardPercent = $this->rewardPercent($candidate);
            $expectancy    = (float) ($candidate['expectancy'] ?? 0.0);

            $expectedReturnAmount += $positionValue * ($expectancy / 100);
            $optimisticAmount     += $positionValue * (max(0.0, $rewardPercent) / 100);
            $conservativeAmount   -= $positionRiskAmount;

            $ticker = strtoupper((string) ($candidate['ticker'] ?? ''));
            $sector = (string) ($candidate['sector'] ?? 'Outros');

            if (! isset($exposureByAsset[$ticker])) {
                $exposureByAsset[$ticker] = 0.0;
            }

            if (! isset($exposureBySector[$sector])) {
                $exposureBySector[$sector] = 0.0;
            }

            $exposureByAsset[$ticker]  += $positionValue;
            $exposureBySector[$sector] += $positionValue;

            $calls[] = [
                'ticker'                     => $ticker,
                'sector'                     => $sector,
                'entry_price'                => $entryPrice,
                'stop_price'                 => $stopPrice,
                'target_price'               => $candidate['target_price'] ?? null,
                'score'                      => (float) ($candidate['score'] ?? 0.0),
                'expectancy'                 => $expectancy,
                'stop_distance_percent'      => round($stopDistance, 4),
                'suggested_position_value'   => round($positionValue, 2),
                'suggested_shares_quantity'  => (float) $sizing->suggestedSharesQuantity,
                'allocation_percent'         => (float) $sizing->allocationPercent,
                'risk_amount'                => round($positionRiskAmount, 2),
                'reward_percent'             => round($rewardPercent, 4),
            ];
        }

        $projectedRiskPercent        = $capitalTotal > 0 ? ($projectedRiskAmount / $capitalTotal) * 100 : 0.0;
        $expectedReturnPercent       = $capitalTotal > 0 ? ($expectedReturnAmount / $capitalTotal) * 100 : 0.0;
        $optimisticScenarioPercent   = $capitalTotal > 0 ? ($optimisticAmount / $capitalTotal) * 100 : 0.0;
        $conservativeScenarioPercent = $capitalTotal > 0 ? ($conservativeAmount / $capitalTotal) * 100 : 0.0;

        $normalize = static fn (array $rows) => array_map(
            static fn (float $value) => round($value, 2),
            $rows,
        );

        return new PortfolioSimulationResultDTO(
            projectedRiskPercent:        round($projectedRiskPercent, 4),
            projectedAllocatedCapital:   round($projectedAllocated, 2),
            projectedFreeCapital:        round(max(0.0, $capitalTotal - $projectedAllocated), 2),
            expectedReturnPercent:       round($expectedReturnPercent, 4),
            optimisticScenarioPercent:   round($optimisticScenarioPercent, 4),
            conservativeScenarioPercent: round($conservativeScenarioPercent, 4),
            exposureBySector:            $normalize($exposureBySector),
            exposureByAsset:             $normalize($exposureByAsset),
            calls:                       $calls,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function resolveCandidates(array $payload): array
    {
        $candidates = [];

        if (isset($payload['call_ids']) && is_array($payload['call_ids'])) {
            $ids = array_values(array_filter(
                array_map(static fn ($item): int => (int) $item, $payload['call_ids']),
                static fn (int $item): bool => $item > 0,
            ));

            if ($ids !== []) {
                $calls = $this->tradeCallRepository->findByIds($ids);

                foreach ($calls as $call) {
                    $asset = $call->monitoredAsset;

                    $candidates[] = [
                        'ticker'      => strtoupper((string) ($asset?->ticker ?? '')),
                        'sector'      => (string) ($asset?->sector ?? 'Outros'),
                        'entry_price' => (float) $call->entry_price,
                        'stop_price'  => (float) $call->stop_price,
                        'target_price'=> (float) $call->target_price,
                        'score'       => (float) $call->score,
                        'expectancy'  => (float) ($call->expectancy_snapshot ?? 0.0),
                    ];
                }
            }
        }

        if (isset($payload['candidates']) && is_array($payload['candidates'])) {
            foreach ($payload['candidates'] as $candidate) {
                if (! is_array($candidate)) {
                    continue;
                }

                if (! isset($candidate['sector']) && isset($candidate['ticker'])) {
                    $asset               = $this->monitoredAssetRepository->findByTicker(strtoupper((string) $candidate['ticker']));
                    $candidate['sector'] = $asset?->sector ?? 'Outros';
                }

                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function stopDistance(array $candidate): float
    {
        if (isset($candidate['stop_distance_percent']) && (float) $candidate['stop_distance_percent'] > 0.0) {
            return (float) $candidate['stop_distance_percent'];
        }

        $entry = (float) ($candidate['entry_price'] ?? 0.0);
        $stop  = isset($candidate['stop_price']) ? (float) $candidate['stop_price'] : null;

        if ($entry <= 0.0 || $stop === null || $stop <= 0.0) {
            return 0.0;
        }

        return abs((($entry - $stop) / $entry) * 100);
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function rewardPercent(array $candidate): float
    {
        if (isset($candidate['reward_percent'])) {
            return (float) $candidate['reward_percent'];
        }

        $entry  = (float) ($candidate['entry_price'] ?? 0.0);
        $target = isset($candidate['target_price']) ? (float) $candidate['target_price'] : null;

        if ($entry <= 0.0 || $target === null) {
            return 0.0;
        }

        return (($target - $entry) / $entry) * 100;
    }
}
