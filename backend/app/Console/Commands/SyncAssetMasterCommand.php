<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncAssetMasterFromBrapiJob;
use Illuminate\Console\Command;

class SyncAssetMasterCommand extends Command
{
    protected $signature = 'market:sync-asset-master {--now : Executa imediatamente sem fila}';

    protected $description = 'Sincroniza o cadastro mestre de ativos via brapi';

    public function handle(): int
    {
        $runNow = (bool) $this->option('now');

        if ($runNow) {
            SyncAssetMasterFromBrapiJob::dispatchSync();
            $this->info('Sincronização do cadastro mestre executada em modo síncrono.');

            return self::SUCCESS;
        }

        SyncAssetMasterFromBrapiJob::dispatch();
        $this->info('Job de sincronização do cadastro mestre enfileirado.');

        return self::SUCCESS;
    }
}

