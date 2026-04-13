<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\PerformanceAnalyticsServiceInterface;
use App\Contracts\PortfolioRiskServiceInterface;
use App\Contracts\PortfolioServiceInterface;
use App\Contracts\RiskSettingsServiceInterface;
use App\Models\EquityCurvePoint;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SnapshotEquityCurveJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ?int $userId = null)
    {
    }

    public function handle(
        PortfolioRiskServiceInterface $portfolioRiskService,
        PortfolioServiceInterface $portfolioService,
        PerformanceAnalyticsServiceInterface $performanceAnalyticsService,
        RiskSettingsServiceInterface $riskSettingsService,
    ): void {
        $runForUser = function (int $userId) use (
            $performanceAnalyticsService,
            $portfolioRiskService,
            $portfolioService,
            $riskSettingsService,
        ): void {
            $settings = $riskSettingsService->getForUser($userId);
            $risk = $portfolioRiskService->summary($userId);
            $performance = $performanceAnalyticsService->summary($userId);
            $open = $portfolioService->listOpen($userId);

            $openPnl = array_sum(array_map(static fn (array $item): float => (float) ($item['unrealized_pnl'] ?? 0.0), $open));
            $realizedPnlAmount = ($performance->cumulativeReturnPercent / 100) * $settings->totalCapital;
            $equityValue = $settings->totalCapital + $realizedPnlAmount + $openPnl;
            $investedValue = (float) ($risk['capital_allocated'] ?? 0.0);
            $cashValue = $equityValue - $investedValue;

            EquityCurvePoint::query()->updateOrCreate([
                'user_id' => $userId,
                'reference_date' => CarbonImmutable::today()->toDateString(),
            ], [
                'equity_value' => round($equityValue, 2),
                'cash_value' => round($cashValue, 2),
                'invested_value' => round($investedValue, 2),
                'open_risk_percent' => round((float) ($risk['open_risk_percent'] ?? 0.0), 4),
                'cumulative_return_percent' => round((float) $performance->cumulativeReturnPercent, 4),
            ]);
        };

        if ($this->userId !== null) {
            $runForUser($this->userId);

            return;
        }

        User::query()->orderBy('id')->chunkById(100, static function ($users) use ($runForUser): void {
            foreach ($users as $user) {
                $runForUser((int) $user->id);
            }
        });
    }
}
