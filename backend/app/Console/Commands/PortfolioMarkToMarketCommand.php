<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\MarkToMarketPortfolioJob;
use Illuminate\Console\Command;

class PortfolioMarkToMarketCommand extends Command
{
    protected $signature = 'market:portfolio-mark-to-market {--user=} {--now}';

    protected $description = 'Atualiza marcação a mercado das posições abertas.';

    public function handle(): int
    {
        $userId = $this->option('user');
        $userId = $userId !== null ? (int) $userId : null;
        $now = (bool) $this->option('now');

        if ($now) {
            MarkToMarketPortfolioJob::dispatchSync($userId);
            $this->info('Mark-to-market executado em modo síncrono.');

            return self::SUCCESS;
        }

        MarkToMarketPortfolioJob::dispatch($userId);
        $this->info('Mark-to-market enfileirado.');

        return self::SUCCESS;
    }
}
