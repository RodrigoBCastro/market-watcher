<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RecalculateIndicatorsJob;
use Illuminate\Console\Command;

class RecalculateIndicatorsCommand extends Command
{
    protected $signature = 'market:recalculate-indicators {ticker? : Ticker opcional para recálculo pontual} {--now : Executa imediatamente sem fila}';

    protected $description = 'Recalcula indicadores técnicos para os ativos monitorados';

    public function handle(): int
    {
        $ticker = $this->argument('ticker');
        $runNow = (bool) $this->option('now');

        if ($runNow) {
            RecalculateIndicatorsJob::dispatchSync($ticker);
            $this->info('Recálculo de indicadores executado em modo síncrono.');

            return self::SUCCESS;
        }

        RecalculateIndicatorsJob::dispatch($ticker);
        $this->info('Job de recálculo de indicadores enfileirado.');

        return self::SUCCESS;
    }
}
