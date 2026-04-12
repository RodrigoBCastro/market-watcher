<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\EvaluateOpenTradesJob;
use Illuminate\Console\Command;

class EvaluateOpenTradesCommand extends Command
{
    protected $signature = 'market:evaluate-open-trades {--now : Executa imediatamente sem fila}';

    protected $description = 'Avalia calls abertas e registra outcomes quando stop/alvo/timeout forem atingidos';

    public function handle(): int
    {
        $runNow = (bool) $this->option('now');

        if ($runNow) {
            EvaluateOpenTradesJob::dispatchSync();
            $this->info('Avaliação de trades abertos executada em modo síncrono.');

            return self::SUCCESS;
        }

        EvaluateOpenTradesJob::dispatch();
        $this->info('Job de avaliação de trades abertos enfileirado.');

        return self::SUCCESS;
    }
}
