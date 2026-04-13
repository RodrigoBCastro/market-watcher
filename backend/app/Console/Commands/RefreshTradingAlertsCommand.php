<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RefreshTradingAlertsJob;
use Illuminate\Console\Command;

class RefreshTradingAlertsCommand extends Command
{
    protected $signature = 'market:refresh-alerts {--user=} {--now}';

    protected $description = 'Recalcula alertas inteligentes de trading.';

    public function handle(): int
    {
        $userId = $this->option('user');
        $userId = $userId !== null ? (int) $userId : null;
        $now = (bool) $this->option('now');

        if ($now) {
            RefreshTradingAlertsJob::dispatchSync($userId);
            $this->info('Atualização de alertas executada em modo síncrono.');

            return self::SUCCESS;
        }

        RefreshTradingAlertsJob::dispatch($userId);
        $this->info('Atualização de alertas enfileirada.');

        return self::SUCCESS;
    }
}
