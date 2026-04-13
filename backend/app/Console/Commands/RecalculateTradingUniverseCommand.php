<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RecalculateTradingUniverseJob;
use Illuminate\Console\Command;

class RecalculateTradingUniverseCommand extends Command
{
    protected $signature = 'market:recalculate-trading-universe {--now : Executa imediatamente sem fila}';

    protected $description = 'Prioriza ativos elegíveis para o Trading Universe';

    public function handle(): int
    {
        $runNow = (bool) $this->option('now');

        if ($runNow) {
            RecalculateTradingUniverseJob::dispatchSync();
            $this->info('Recálculo do Trading Universe executado em modo síncrono.');

            return self::SUCCESS;
        }

        RecalculateTradingUniverseJob::dispatch();
        $this->info('Job de recálculo do Trading Universe enfileirado.');

        return self::SUCCESS;
    }
}

