<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RecalculateScoresJob;
use Illuminate\Console\Command;

class RecalculateScoresCommand extends Command
{
    protected $signature = 'market:recalculate-scores {ticker? : Ticker opcional para recálculo pontual} {--now : Executa imediatamente sem fila}';

    protected $description = 'Recalcula scores e decisão de trade para os ativos monitorados';

    public function handle(): int
    {
        $ticker = $this->argument('ticker');
        $runNow = (bool) $this->option('now');

        if ($runNow) {
            RecalculateScoresJob::dispatchSync($ticker);
            $this->info('Recálculo de scores executado em modo síncrono.');

            return self::SUCCESS;
        }

        RecalculateScoresJob::dispatch($ticker);
        $this->info('Job de recálculo de scores enfileirado.');

        return self::SUCCESS;
    }
}
