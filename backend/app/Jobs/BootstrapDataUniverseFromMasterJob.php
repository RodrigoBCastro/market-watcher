<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\AssetUniverseBootstrapServiceInterface;
use App\Services\MarketData\SyncLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class BootstrapDataUniverseFromMasterJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public readonly array $filters = [],
        public readonly ?int $changedByUserId = null,
    ) {
    }

    public function handle(
        AssetUniverseBootstrapServiceInterface $assetUniverseBootstrapService,
        SyncLogger $syncLogger,
    ): void {
        $run = $syncLogger->start('bootstrap_data_universe');

        try {
            $result = $assetUniverseBootstrapService->bootstrapDataUniverse(
                filters: $this->filters,
                changedByUserId: $this->changedByUserId,
            );

            $syncLogger->log($run, 'info', 'Bootstrap do Data Universe concluído.', $result);

            $syncLogger->finish(
                run: $run,
                status: 'success',
                processed: (int) ($result['selected'] ?? 0),
                failed: (int) ($result['errors'] ?? 0),
                notes: "Inseridos: {$result['inserted']}; Atualizados: {$result['updated']}; Promovidos: {$result['promoted_to_data_universe']}",
            );
        } catch (Throwable $exception) {
            $syncLogger->log($run, 'error', 'Falha no bootstrap do Data Universe.', [
                'error' => $exception->getMessage(),
            ]);

            $syncLogger->finish($run, 'failed', 0, 1, 'Erro no bootstrap do Data Universe a partir do cadastro mestre.');
        }
    }
}

