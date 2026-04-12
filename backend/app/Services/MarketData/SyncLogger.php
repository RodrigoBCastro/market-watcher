<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Models\SyncRun;

class SyncLogger
{
    public function start(string $type): SyncRun
    {
        return SyncRun::query()->create([
            'type' => $type,
            'status' => 'running',
            'started_at' => now(),
            'records_processed' => 0,
            'records_failed' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function log(SyncRun $run, string $level, string $message, array $context = []): void
    {
        $run->logs()->create([
            'level' => strtolower($level),
            'message' => $message,
            'context' => $context === [] ? null : $context,
        ]);
    }

    public function finish(SyncRun $run, string $status, int $processed, int $failed, ?string $notes = null): void
    {
        $run->update([
            'status' => $status,
            'finished_at' => now(),
            'records_processed' => $processed,
            'records_failed' => $failed,
            'notes' => $notes,
        ]);
    }
}
