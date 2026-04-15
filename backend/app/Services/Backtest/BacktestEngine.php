<?php

declare(strict_types=1);

namespace App\Services\Backtest;

use App\Contracts\AssetAnalysisScoreRepositoryInterface;
use App\Contracts\AssetQuoteRepositoryInterface;
use App\Contracts\BacktestEngineInterface;
use App\Contracts\BacktestResultRepositoryInterface;
use App\Contracts\SetupMetricRepositoryInterface;
use App\DTOs\BacktestResultDTO;
use App\Models\AssetQuote;
use App\Models\BacktestResult;
use App\Services\Calls\TradeCallFilterService;
use App\Services\Calls\TradeOutcomeEvaluatorService;
use Carbon\CarbonImmutable;

class BacktestEngine implements BacktestEngineInterface
{
    public function __construct(
        private readonly TradeCallFilterService $tradeCallFilterService,
        private readonly TradeOutcomeEvaluatorService $tradeOutcomeEvaluatorService,
        private readonly BacktestResultRepositoryInterface $backtestResultRepository,
        private readonly AssetAnalysisScoreRepositoryInterface $assetAnalysisScoreRepository,
        private readonly AssetQuoteRepositoryInterface $assetQuoteRepository,
        private readonly SetupMetricRepositoryInterface $setupMetricRepository,
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function run(string $strategyName, array $options = []): BacktestResultDTO
    {
        $maxHoldingDays = (int) ($options['max_holding_days'] ?? config('market.calls.max_holding_days', 20));
        $from = isset($options['from']) ? CarbonImmutable::parse((string) $options['from'])->toDateString() : null;
        $to = isset($options['to']) ? CarbonImmutable::parse((string) $options['to'])->toDateString() : null;

        $scores = $this->assetAnalysisScoreRepository->queryInDateRange($from, $to);

        $pnls = [];
        $totalTrades = 0;
        $wins = 0;

        foreach ($scores as $score) {
            if ($score->setup_code === null || $score->suggested_entry === null || $score->suggested_stop === null || $score->suggested_target === null) {
                continue;
            }

            $metric = $this->setupMetricRepository->findBySetupCode((string) $score->setup_code);
            $filter = $this->tradeCallFilterService->evaluate($score, $metric);
            if (! $filter['eligible']) {
                continue;
            }

            $quotes = $this->assetQuoteRepository
                ->findByAssetAfterDate(
                    assetId: (int) $score->monitored_asset_id,
                    afterDate: $score->trade_date->toDateString(),
                    limit: $maxHoldingDays,
                )
                ->map(static fn (AssetQuote $quote): array => [
                    'trade_date' => $quote->trade_date->toDateString(),
                    'high' => (float) $quote->high,
                    'low' => (float) $quote->low,
                    'close' => (float) $quote->close,
                ])
                ->all();

            $result = $this->tradeOutcomeEvaluatorService->evaluate(
                quotes: $quotes,
                entry: (float) $score->suggested_entry,
                stop: (float) $score->suggested_stop,
                target: (float) $score->suggested_target,
                maxHoldingDays: $maxHoldingDays,
            );

            if ($result === null) {
                continue;
            }

            $totalTrades++;
            $pnl = (float) ($result['pnl_percent'] ?? 0.0);
            $pnls[] = $pnl;

            if ((string) ($result['result'] ?? 'loss') === 'win') {
                $wins++;
            }
        }

        $winrate = $totalTrades > 0 ? ($wins / $totalTrades) * 100 : 0.0;
        $totalReturn = array_sum($pnls);
        $maxDrawdown = $this->maxDrawdown($pnls);
        $profitFactor = $this->profitFactor($pnls);

        $model = $this->backtestResultRepository->create([
            'strategy_name' => $strategyName,
            'total_trades' => $totalTrades,
            'winrate' => round($winrate, 3),
            'total_return' => round($totalReturn, 4),
            'max_drawdown' => round($maxDrawdown, 4),
            'profit_factor' => $profitFactor,
            'metadata' => [
                'from' => $from,
                'to' => $to,
                'max_holding_days' => $maxHoldingDays,
                'evaluated_signals' => $scores->count(),
            ],
        ]);

        return new BacktestResultDTO(
            strategyName: $model->strategy_name,
            totalTrades: (int) $model->total_trades,
            winrate: (float) $model->winrate,
            totalReturn: (float) $model->total_return,
            maxDrawdown: (float) $model->max_drawdown,
            profitFactor: $model->profit_factor !== null ? (float) $model->profit_factor : null,
            metadata: (array) ($model->metadata ?? []),
            createdAt: $model->created_at !== null ? CarbonImmutable::parse($model->created_at) : null,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listResults(int $limit = 30): array
    {
        return $this->backtestResultRepository
            ->listByUser($limit)
            ->map(static fn (BacktestResult $result): array => [
                'id' => $result->id,
                'strategy_name' => $result->strategy_name,
                'total_trades' => (int) $result->total_trades,
                'winrate' => (float) $result->winrate,
                'total_return' => (float) $result->total_return,
                'max_drawdown' => (float) $result->max_drawdown,
                'profit_factor' => $result->profit_factor !== null ? (float) $result->profit_factor : null,
                'metadata' => $result->metadata,
                'created_at' => $result->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @param  array<int, float>  $pnls
     */
    private function profitFactor(array $pnls): ?float
    {
        $gains = 0.0;
        $losses = 0.0;

        foreach ($pnls as $pnl) {
            if ($pnl >= 0) {
                $gains += $pnl;
            } else {
                $losses += abs($pnl);
            }
        }

        if ($losses <= 0.0) {
            return $gains > 0 ? round($gains, 4) : null;
        }

        return round($gains / $losses, 4);
    }

    /**
     * @param  array<int, float>  $pnls
     */
    private function maxDrawdown(array $pnls): float
    {
        $equity = 0.0;
        $peak = 0.0;
        $maxDrawdown = 0.0;

        foreach ($pnls as $pnl) {
            $equity += $pnl;
            $peak = max($peak, $equity);

            if ($peak <= 0.0) {
                continue;
            }

            $drawdown = (($peak - $equity) / $peak) * 100;
            $maxDrawdown = max($maxDrawdown, $drawdown);
        }

        return $maxDrawdown;
    }
}
