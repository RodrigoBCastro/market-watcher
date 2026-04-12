<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncAssetQuotesJob;
use Illuminate\Console\Command;

class SyncAssetsCommand extends Command
{
    protected $signature = 'market:sync-assets {ticker? : Ticker opcional para sync pontual} {--now : Executa imediatamente sem fila}';

    protected $description = 'Sincroniza cotações dos ativos monitorados';

    public function handle(): int
    {
        $ticker = $this->argument('ticker');
        $runNow = (bool) $this->option('now');

        if ($runNow) {
            SyncAssetQuotesJob::dispatchSync($ticker);
            $this->info('Sincronização de ativos executada em modo síncrono.');

            return self::SUCCESS;
        }

        SyncAssetQuotesJob::dispatch($ticker);
        $this->info('Job de sincronização de ativos enfileirado.');

        return self::SUCCESS;
    }
}
