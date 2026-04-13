<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncDataUniverseJob;
use Illuminate\Console\Command;

class SyncDataUniverseCommand extends Command
{
    protected $signature = 'market:sync-data-universe {ticker? : Ticker opcional para sync pontual} {--now : Executa imediatamente sem fila}';

    protected $description = 'Sincroniza histórico de preços do Data Universe';

    public function handle(): int
    {
        $ticker = $this->argument('ticker');
        $runNow = (bool) $this->option('now');

        if ($runNow) {
            SyncDataUniverseJob::dispatchSync($ticker);
            $this->info('Sincronização do Data Universe executada em modo síncrono.');

            return self::SUCCESS;
        }

        SyncDataUniverseJob::dispatch($ticker);
        $this->info('Job de sincronização do Data Universe enfileirado.');

        return self::SUCCESS;
    }
}

