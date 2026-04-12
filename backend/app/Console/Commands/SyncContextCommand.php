<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncMarketContextJob;
use Illuminate\Console\Command;

class SyncContextCommand extends Command
{
    protected $signature = 'market:sync-context {--now : Executa imediatamente sem fila}';

    protected $description = 'Sincroniza contexto de mercado (IBOV e dólar)';

    public function handle(): int
    {
        $runNow = (bool) $this->option('now');

        if ($runNow) {
            SyncMarketContextJob::dispatchSync();
            $this->info('Sincronização de contexto executada em modo síncrono.');

            return self::SUCCESS;
        }

        SyncMarketContextJob::dispatch();
        $this->info('Job de sincronização de contexto enfileirado.');

        return self::SUCCESS;
    }
}
