<?php

declare(strict_types=1);

namespace App\Services\Metrics;

use App\Contracts\ProbabilisticEngineInterface;
use App\Contracts\SetupMetricRepositoryInterface;
use App\Contracts\TradeOutcomeRepositoryInterface;
use App\DTOs\SetupMetricDTO;
use App\Enums\SetupCode;
use App\Models\SetupMetric;
use App\Models\TradeOutcome;

class SetupMetricsService implements ProbabilisticEngineInterface
{
    public function __construct(
        private readonly SetupMetricRepositoryInterface  $setupMetricRepository,
        private readonly TradeOutcomeRepositoryInterface $tradeOutcomeRepository,
    ) {
    }

    /**
     * @return array<int, SetupMetricDTO>
     */
    public function rebuildSetupMetrics(): array
    {
        $grouped    = $this->tradeOutcomeRepository->findAllChronological()->groupBy('setup_code');
        $minHistory = (int) config('market.calls.min_history', 8);
        $metrics    = [];

        foreach (SetupCode::cases() as $setupCode) {
            $rows   = $grouped->get($setupCode->value, collect());
            $total  = $rows->count();
            $wins   = $rows->where('result', 'win')->count();
            $losses = $rows->where('result', 'loss')->count();

            $winrate     = $total > 0 ? ($wins / $total) * 100 : 0.0;
            $avgGain     = (float) ($rows->where('result', 'win')->avg('pnl_percent') ?? 0.0);
            $avgLossRaw  = (float) ($rows->where('result', 'loss')->avg('pnl_percent') ?? 0.0);
            $avgLoss     = abs($avgLossRaw);
            $expectancy  = ($winrate / 100 * $avgGain) - ((1 - ($winrate / 100)) * $avgLoss);
            $edge        = $expectancy * ($winrate / 100);
            $isEnabled   = $total < $minHistory ? true : ($expectancy > 0 && $winrate > 50);

            $model = $this->setupMetricRepository->upsertBySetupCode($setupCode->value, [
                'total_trades' => $total,
                'wins'         => $wins,
                'losses'       => $losses,
                'winrate'      => round($winrate, 3),
                'avg_gain'     => round($avgGain, 4),
                'avg_loss'     => round($avgLoss, 4),
                'expectancy'   => round($expectancy, 4),
                'edge'         => round($edge, 4),
                'is_enabled'   => $isEnabled,
            ]);

            $metrics[] = $this->toDto($model);
        }

        return $metrics;
    }

    public function getSetupMetric(string $setupCode): ?SetupMetricDTO
    {
        $model = $this->setupMetricRepository->findBySetupCode(strtoupper($setupCode));

        return $model !== null ? $this->toDto($model) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSetupMetrics(): array
    {
        return $this->setupMetricRepository
            ->listAllOrderedByExpectancy()
            ->map(fn ($row): array => $this->toDto($row)->toArray())
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardMetrics(): array
    {
        $outcomes   = $this->tradeOutcomeRepository->findAllChronological();
        $totalTrades = $outcomes->count();

        $wins       = $outcomes->where('result', 'win')->count();
        $winrate    = $totalTrades > 0 ? ($wins / $totalTrades) * 100 : 0.0;
        $expectancy = (float) ($outcomes->avg('pnl_percent') ?? 0.0);

        $profitFactor = $this->profitFactor($outcomes->all());
        $maxDrawdown  = $this->maxDrawdown($outcomes->pluck('pnl_percent')->all());
        $setupRanking = $this->listSetupMetrics();

        return [
            'headline' => [
                'total_trades'  => $totalTrades,
                'winrate'       => round($winrate, 3),
                'expectancy'    => round($expectancy, 4),
                'profit_factor' => $profitFactor,
                'max_drawdown'  => $maxDrawdown,
            ],
            'setup_ranking' => $setupRanking,
            'alerts'        => $this->alerts($outcomes->all()),
        ];
    }

    public function disableDeterioratingSetups(): int
    {
        return $this->setupMetricRepository->disableDeteriorating(
            (int) config('market.calls.min_history', 8),
        );
    }

    private function toDto(SetupMetric $metric): SetupMetricDTO
    {
        return new SetupMetricDTO(
            setupCode:      (string) $metric->setup_code,
            totalTrades:    (int) $metric->total_trades,
            wins:           (int) $metric->wins,
            losses:         (int) $metric->losses,
            winrate:        round((float) $metric->winrate, 3),
            avgGain:        round((float) $metric->avg_gain, 4),
            avgLoss:        round((float) $metric->avg_loss, 4),
            expectancy:     round((float) $metric->expectancy, 4),
            edge:           round((float) $metric->edge, 4),
            isEnabled:      (bool) $metric->is_enabled,
            classification: $this->classification($metric),
        );
    }

    private function classification(SetupMetric $metric): string
    {
        $minHistory = (int) config('market.calls.min_history', 8);

        if ($metric->total_trades < $minHistory) {
            return 'setup experimental';
        }

        if ($metric->expectancy > 0 && $metric->winrate >= 60) {
            return 'alta probabilidade';
        }

        if ($metric->expectancy > 0 && $metric->edge >= 0.8) {
            return 'alto edge';
        }

        if ($metric->expectancy > 0 && $metric->winrate > 50 && $metric->is_enabled) {
            return 'setup validado';
        }

        return 'setup experimental';
    }

    /**
     * @param  array<int, TradeOutcome>  $outcomes
     */
    private function profitFactor(array $outcomes): ?float
    {
        $gains  = 0.0;
        $losses = 0.0;

        foreach ($outcomes as $outcome) {
            $pnl = (float) $outcome->pnl_percent;

            if ($pnl >= 0) {
                $gains += $pnl;
                continue;
            }

            $losses += abs($pnl);
        }

        if ($losses === 0.0) {
            return $gains > 0 ? round($gains, 4) : null;
        }

        return round($gains / $losses, 4);
    }

    /**
     * @param  array<int, float|int|null>  $pnls
     */
    private function maxDrawdown(array $pnls): float
    {
        $equity      = 0.0;
        $peak        = 0.0;
        $maxDrawdown = 0.0;

        foreach ($pnls as $value) {
            $equity  += (float) $value;
            $peak     = max($peak, $equity);

            if ($peak <= 0.0) {
                continue;
            }

            $drawdown    = (($peak - $equity) / $peak) * 100;
            $maxDrawdown = max($maxDrawdown, $drawdown);
        }

        return round($maxDrawdown, 4);
    }

    /**
     * @param  array<int, TradeOutcome>  $outcomes
     * @return array<int, array<string, string|float|int>>
     */
    private function alerts(array $outcomes): array
    {
        $alerts = [];

        $recent = array_slice($outcomes, -20);
        $prior  = array_slice($outcomes, -40, 20);

        if ($recent !== [] && $prior !== []) {
            $recentWins  = count(array_filter($recent, static fn (TradeOutcome $item): bool => $item->result === 'win'));
            $priorWins   = count(array_filter($prior, static fn (TradeOutcome $item): bool => $item->result === 'win'));
            $recentWinrate = ($recentWins / count($recent)) * 100;
            $priorWinrate  = ($priorWins / count($prior)) * 100;

            if (($priorWinrate - $recentWinrate) >= 8) {
                $alerts[] = [
                    'type'    => 'winrate_drop',
                    'level'   => 'warning',
                    'message' => 'Queda relevante de winrate no bloco mais recente.',
                    'value'   => round($priorWinrate - $recentWinrate, 3),
                ];
            }
        }

        $drawdown          = $this->maxDrawdown(array_map(static fn (TradeOutcome $item): float => (float) $item->pnl_percent, $outcomes));
        $drawdownThreshold = (float) config('market.quant.alert_drawdown_threshold', 8.0);

        if ($drawdown >= $drawdownThreshold) {
            $alerts[] = [
                'type'    => 'drawdown_rise',
                'level'   => 'warning',
                'message' => 'Aumento de drawdown acima do limite configurado.',
                'value'   => $drawdown,
            ];
        }

        $minHistory   = (int) config('market.calls.min_history', 8);
        $deteriorating = $this->setupMetricRepository->listDeterioratingSetupCodes($minHistory);

        if ($deteriorating !== []) {
            $alerts[] = [
                'type'    => 'setup_deteriorating',
                'level'   => 'warning',
                'message' => 'Setups com deterioração de edge detectada.',
                'value'   => implode(', ', $deteriorating),
            ];
        }

        return $alerts;
    }
}
