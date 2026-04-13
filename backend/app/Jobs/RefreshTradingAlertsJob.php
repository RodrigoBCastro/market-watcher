<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\TradingAlertServiceInterface;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshTradingAlertsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ?int $userId = null)
    {
    }

    public function handle(TradingAlertServiceInterface $tradingAlertService): void
    {
        if ($this->userId !== null) {
            $tradingAlertService->refreshForUser($this->userId);

            return;
        }

        User::query()->orderBy('id')->chunkById(100, static function ($users) use ($tradingAlertService): void {
            foreach ($users as $user) {
                $tradingAlertService->refreshForUser((int) $user->id);
            }
        });
    }
}
