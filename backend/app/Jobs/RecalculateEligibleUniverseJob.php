<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\MarketUniverseServiceInterface;
use App\Services\MarketData\SyncLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RecalculateEligibleUniverseJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 240;

    public function __construct(public readonly ?int $changedByUserId = null)
    {
    }

    public function handle(MarketUniverseServiceInterface $marketUniverseService, SyncLogger $syncLogger): void
    {
        $run = $syncLogger->start('recalculate_eligible_universe');

        try {
            $result = $marketUniverseService->recalculateEligibleUniverse($this->changedByUserId);

            $syncLogger->log($run, 'info', 'Eligible Universe recalculado.', $result);

            $syncLogger->finish(
                run: $run,
                status: 'success',
                processed: (int) ($result['reviewed_assets'] ?? 0),
                failed: 0,
                notes: "Promovidos: {$result['promoted']}; Rebaixados: {$result['demoted']}",
            );
        } catch (Throwable $exception) {
            $syncLogger->log($run, 'error', 'Falha ao recalcular Eligible Universe.', [
                'error' => $exception->getMessage(),
            ]);

            $syncLogger->finish($run, 'failed', 0, 1, 'Erro no recálculo do Eligible Universe.');
        }
    }
}

