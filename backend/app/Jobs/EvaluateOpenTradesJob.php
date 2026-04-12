<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\ProbabilisticEngineInterface;
use App\Enums\TradeCallStatus;
use App\Models\AssetQuote;
use App\Models\TradeCall;
use App\Models\TradeOutcome;
use App\Services\Calls\TradeOutcomeEvaluatorService;
use App\Services\MarketData\SyncLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class EvaluateOpenTradesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 240;

    public function handle(
        TradeOutcomeEvaluatorService $tradeOutcomeEvaluatorService,
        ProbabilisticEngineInterface $probabilisticEngine,
        SyncLogger $syncLogger,
    ): void {
        $run = $syncLogger->start('evaluate_open_trades');
        $maxHoldingDays = (int) config('market.calls.max_holding_days', 20);

        $processed = 0;
        $failed = 0;

        $calls = TradeCall::query()
            ->with('monitoredAsset:id,ticker')
            ->whereIn('status', [
                TradeCallStatus::APPROVED->value,
                TradeCallStatus::PUBLISHED->value,
            ])
            ->doesntHave('outcome')
            ->orderBy('trade_date')
            ->get();

        foreach ($calls as $call) {
            try {
                $quotes = AssetQuote::query()
                    ->where('monitored_asset_id', $call->monitored_asset_id)
                    ->whereDate('trade_date', '>', $call->trade_date)
                    ->orderBy('trade_date')
                    ->limit($maxHoldingDays)
                    ->get()
                    ->map(static fn (AssetQuote $quote): array => [
                        'trade_date' => $quote->trade_date->toDateString(),
                        'high' => (float) $quote->high,
                        'low' => (float) $quote->low,
                        'close' => (float) $quote->close,
                    ])
                    ->all();

                $daysOpen = (int) $call->trade_date->diffInDays(now());
                $allowTimeoutExit = $daysOpen >= $maxHoldingDays;

                $result = $tradeOutcomeEvaluatorService->evaluate(
                    quotes: $quotes,
                    entry: (float) $call->entry_price,
                    stop: (float) $call->stop_price,
                    target: (float) $call->target_price,
                    maxHoldingDays: $maxHoldingDays,
                    allowTimeoutExit: $allowTimeoutExit,
                );

                if ($result === null) {
                    continue;
                }

                TradeOutcome::query()->updateOrCreate([
                    'trade_call_id' => $call->id,
                ], [
                    'monitored_asset_id' => $call->monitored_asset_id,
                    'setup_code' => $call->setup_code,
                    'entry_price' => (float) $call->entry_price,
                    'stop_price' => (float) $call->stop_price,
                    'target_price' => (float) $call->target_price,
                    'exit_price' => (float) ($result['exit_price'] ?? 0.0),
                    'result' => (string) ($result['result'] ?? 'loss'),
                    'pnl_percent' => (float) ($result['pnl_percent'] ?? 0.0),
                    'duration_days' => (int) ($result['duration_days'] ?? 0),
                ]);

                $processed++;

                $syncLogger->log($run, 'info', 'Trade outcome registrado', [
                    'symbol' => $call->monitoredAsset?->ticker,
                    'trade_call_id' => $call->id,
                    'result' => $result['result'] ?? null,
                    'pnl_percent' => $result['pnl_percent'] ?? null,
                ]);
            } catch (Throwable $exception) {
                $failed++;

                $syncLogger->log($run, 'error', 'Falha ao avaliar trade aberto', [
                    'trade_call_id' => $call->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $probabilisticEngine->rebuildSetupMetrics();
        $probabilisticEngine->disableDeterioratingSetups();

        $status = $failed > 0 ? ($processed > 0 ? 'partial' : 'failed') : 'success';

        $syncLogger->finish($run, $status, $processed, $failed);
    }
}
