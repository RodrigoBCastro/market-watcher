<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\GenerateDailyBriefJob;
use Illuminate\Console\Command;

class GenerateBriefCommand extends Command
{
    protected $signature = 'market:generate-brief {date? : Data do brief no formato YYYY-MM-DD} {--now : Executa imediatamente sem fila}';

    protected $description = 'Gera o brief diário operacional';

    public function handle(): int
    {
        $date = $this->argument('date');
        $runNow = (bool) $this->option('now');

        if ($runNow) {
            GenerateDailyBriefJob::dispatchSync($date);
            $this->info('Geração de brief executada em modo síncrono.');

            return self::SUCCESS;
        }

        GenerateDailyBriefJob::dispatch($date);
        $this->info('Job de geração de brief enfileirado.');

        return self::SUCCESS;
    }
}
