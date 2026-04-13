<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\PortfolioServiceInterface;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MarkToMarketPortfolioJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ?int $userId = null)
    {
    }

    public function handle(PortfolioServiceInterface $portfolioService): void
    {
        if ($this->userId !== null) {
            $portfolioService->refreshMarkToMarket($this->userId);

            return;
        }

        User::query()->orderBy('id')->chunkById(100, static function ($users) use ($portfolioService): void {
            foreach ($users as $user) {
                $portfolioService->refreshMarkToMarket((int) $user->id);
            }
        });
    }
}
