<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\WeeklyCallsJob;
use Illuminate\Console\Command;

class GenerateWeeklyCallsCommand extends Command
{
    protected $signature = 'market:generate-weekly-calls {--now : Executa imediatamente sem fila}';

    protected $description = 'Gera ciclo semanal de calls em status draft';

    public function handle(): int
    {
        $runNow = (bool) $this->option('now');

        if ($runNow) {
            WeeklyCallsJob::dispatchSync();
            $this->info('Ciclo semanal de calls executado em modo síncrono.');

            return self::SUCCESS;
        }

        WeeklyCallsJob::dispatch();
        $this->info('Job de ciclo semanal de calls enfileirado.');

        return self::SUCCESS;
    }
}
