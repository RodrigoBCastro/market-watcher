<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RecalculateEligibleUniverseJob;
use Illuminate\Console\Command;

class RecalculateEligibleUniverseCommand extends Command
{
    protected $signature = 'market:recalculate-eligible-universe {--now : Executa imediatamente sem fila}';

    protected $description = 'Recalcula critérios de elegibilidade e promove/rebaixa ativos no Eligible Universe';

    public function handle(): int
    {
        $runNow = (bool) $this->option('now');

        if ($runNow) {
            RecalculateEligibleUniverseJob::dispatchSync();
            $this->info('Recálculo do Eligible Universe executado em modo síncrono.');

            return self::SUCCESS;
        }

        RecalculateEligibleUniverseJob::dispatch();
        $this->info('Job de recálculo do Eligible Universe enfileirado.');

        return self::SUCCESS;
    }
}

