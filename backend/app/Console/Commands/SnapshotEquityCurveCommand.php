<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SnapshotEquityCurveJob;
use Illuminate\Console\Command;

class SnapshotEquityCurveCommand extends Command
{
    protected $signature = 'market:snapshot-equity {--user=} {--now}';

    protected $description = 'Salva snapshot diário da curva de capital.';

    public function handle(): int
    {
        $userId = $this->option('user');
        $userId = $userId !== null ? (int) $userId : null;
        $now = (bool) $this->option('now');

        if ($now) {
            SnapshotEquityCurveJob::dispatchSync($userId);
            $this->info('Snapshot de equity executado em modo síncrono.');

            return self::SUCCESS;
        }

        SnapshotEquityCurveJob::dispatch($userId);
        $this->info('Snapshot de equity enfileirado.');

        return self::SUCCESS;
    }
}
