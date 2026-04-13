<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\MarkToMarketPortfolioJob;
use App\Jobs\RefreshTradingAlertsJob;
use App\Jobs\SnapshotEquityCurveJob;
use Illuminate\Console\Command;

class TradingPipelineCommand extends Command
{
    protected $signature = 'market:trading-pipeline {--user=} {--now}';

    protected $description = 'Executa pipeline completo de gestão de trading (MTM, alertas, equity).';

    public function handle(): int
    {
        $userId = $this->option('user');
        $userId = $userId !== null ? (int) $userId : null;
        $now = (bool) $this->option('now');

        if ($now) {
            MarkToMarketPortfolioJob::dispatchSync($userId);
            RefreshTradingAlertsJob::dispatchSync($userId);
            SnapshotEquityCurveJob::dispatchSync($userId);
            $this->info('Pipeline completo executado em modo síncrono.');

            return self::SUCCESS;
        }

        MarkToMarketPortfolioJob::dispatch($userId);
        RefreshTradingAlertsJob::dispatch($userId);
        SnapshotEquityCurveJob::dispatch($userId);

        $this->info('Pipeline completo enfileirado.');

        return self::SUCCESS;
    }
}
