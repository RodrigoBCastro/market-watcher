<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\AssetMasterRegistryServiceInterface;
use App\Services\MarketData\SyncLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncAssetMasterFromBrapiJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function handle(
        AssetMasterRegistryServiceInterface $assetMasterRegistryService,
        SyncLogger $syncLogger,
    ): void {
        $run = $syncLogger->start('sync_asset_master');

        try {
            $result = $assetMasterRegistryService->synchronizeFromProvider();

            $syncLogger->log($run, 'info', 'Cadastro mestre sincronizado com sucesso.', $result);
            if (($result['errors_count'] ?? 0) > 0) {
                $syncLogger->log($run, 'warning', 'Sincronização com erros por símbolo.', [
                    'errors_count' => $result['errors_count'],
                    'errors_by_symbol' => $result['errors_by_symbol'] ?? [],
                ]);
            }

            $syncLogger->finish(
                run: $run,
                status: 'success',
                processed: (int) ($result['received'] ?? 0),
                failed: (int) ($result['errors_count'] ?? 0),
                notes: sprintf(
                    'Inseridos: %d; Atualizados: %d; Ignorados: %d; Inativados: %d; Erros: %d',
                    (int) ($result['inserted'] ?? 0),
                    (int) ($result['updated'] ?? 0),
                    (int) ($result['ignored'] ?? 0),
                    (int) ($result['inactivated'] ?? 0),
                    (int) ($result['errors_count'] ?? 0),
                ),
            );
        } catch (Throwable $exception) {
            $syncLogger->log($run, 'error', 'Falha na sincronização do cadastro mestre.', [
                'error' => $exception->getMessage(),
            ]);

            $syncLogger->finish($run, 'failed', 0, 1, 'Erro ao sincronizar cadastro mestre via brapi.');
        }
    }
}
