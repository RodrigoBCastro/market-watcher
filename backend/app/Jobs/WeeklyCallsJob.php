<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\ProbabilisticEngineInterface;
use App\Contracts\TradeCallServiceInterface;
use App\Services\MarketData\SyncLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class WeeklyCallsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function handle(
        ProbabilisticEngineInterface $probabilisticEngine,
        TradeCallServiceInterface $tradeCallService,
        SyncLogger $syncLogger,
    ): void {
        $run = $syncLogger->start('weekly_calls');

        try {
            $probabilisticEngine->rebuildSetupMetrics();
            $disabledSetups = $probabilisticEngine->disableDeterioratingSetups();

            $calls = $tradeCallService->generateDraftCalls();

            foreach ($calls as $call) {
                $syncLogger->log($run, 'info', 'Call em draft gerada', [
                    'symbol' => $call->symbol,
                    'setup_code' => $call->setupCode,
                    'score' => $call->score,
                    'final_rank_score' => $call->finalRankScore,
                ]);
            }

            $syncLogger->finish(
                run: $run,
                status: 'success',
                processed: count($calls),
                failed: 0,
                notes: "Ciclo semanal concluído. Setups desativados automaticamente: {$disabledSetups}",
            );
        } catch (Throwable $exception) {
            $syncLogger->log($run, 'error', 'Falha no WeeklyCallsJob', [
                'error' => $exception->getMessage(),
            ]);

            $syncLogger->finish($run, 'failed', 0, 1, 'Erro ao gerar calls semanais.');
        }
    }
}
