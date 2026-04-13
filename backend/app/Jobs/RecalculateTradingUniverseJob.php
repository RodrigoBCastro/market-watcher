<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\MarketUniverseServiceInterface;
use App\Services\MarketData\SyncLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RecalculateTradingUniverseJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 240;

    public function __construct(public readonly ?int $changedByUserId = null)
    {
    }

    public function handle(MarketUniverseServiceInterface $marketUniverseService, SyncLogger $syncLogger): void
    {
        $run = $syncLogger->start('recalculate_trading_universe');

        try {
            $result = $marketUniverseService->recalculateTradingUniverse($this->changedByUserId);

            $syncLogger->log($run, 'info', 'Trading Universe recalculado.', $result);

            $syncLogger->finish(
                run: $run,
                status: 'success',
                processed: (int) ($result['reviewed_assets'] ?? 0),
                failed: 0,
                notes: "Selecionados: {$result['selected_assets']}; Promovidos: {$result['promoted']}; Rebaixados: {$result['demoted']}",
            );
        } catch (Throwable $exception) {
            $syncLogger->log($run, 'error', 'Falha ao recalcular Trading Universe.', [
                'error' => $exception->getMessage(),
            ]);

            $syncLogger->finish($run, 'failed', 0, 1, 'Erro no recálculo do Trading Universe.');
        }
    }
}

