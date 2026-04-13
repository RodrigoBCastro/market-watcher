<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\PerformanceAnalyticsServiceInterface;
use App\Contracts\PortfolioRiskServiceInterface;
use App\Contracts\PortfolioServiceInterface;
use App\Contracts\TradingAlertServiceInterface;
use App\Models\TradeCall;
use App\Models\TradingAlert;

class TradingDashboardService
{
    public function __construct(
        private readonly PortfolioServiceInterface $portfolioService,
        private readonly PortfolioRiskServiceInterface $portfolioRiskService,
        private readonly PerformanceAnalyticsServiceInterface $performanceAnalyticsService,
        private readonly TradingAlertServiceInterface $tradingAlertService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(int $userId): array
    {
        $risk = $this->portfolioRiskService->summary($userId);
        $openPositions = $this->portfolioService->listOpen($userId);

        $performanceSummary = $this->performanceAnalyticsService->summary($userId)->toArray();
        $equityCurve = $this->performanceAnalyticsService->equityCurve($userId, []);

        $callsByStatus = TradeCall::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $topCalls = TradeCall::query()
            ->with('monitoredAsset:id,ticker')
            ->orderByDesc('confidence_score')
            ->orderByDesc('final_rank_score')
            ->limit(8)
            ->get()
            ->map(static fn (TradeCall $call): array => [
                'id' => (int) $call->id,
                'ticker' => $call->monitoredAsset?->ticker,
                'status' => $call->status,
                'setup_code' => $call->setup_code,
                'setup_label' => $call->setup_label,
                'score' => (float) $call->score,
                'confidence_score' => $call->confidence_score !== null ? (float) $call->confidence_score : null,
                'confidence_label' => $call->confidence_label,
                'final_rank_score' => (float) $call->final_rank_score,
                'market_regime' => $call->market_regime,
            ])
            ->all();

        $alerts = $this->tradingAlertService->listForUser($userId, onlyUnread: false, limit: 8);
        $alertsUnread = TradingAlert::query()->where('user_id', $userId)->where('is_read', false)->count();

        $pnlOpen = array_sum(array_map(static fn (array $item): float => (float) ($item['unrealized_pnl'] ?? 0.0), $openPositions));

        return [
            'summary' => [
                'capital_total' => $risk['capital_total'],
                'capital_allocated' => $risk['capital_allocated'],
                'capital_free' => $risk['capital_free'],
                'open_risk_percent' => $risk['open_risk_percent'],
                'pnl_open' => round($pnlOpen, 2),
                'pnl_cumulative_percent' => $performanceSummary['cumulative_return_percent'] ?? 0.0,
                'open_positions' => $risk['open_positions'],
            ],
            'positions_open' => array_slice($openPositions, 0, 20),
            'calls_ideas' => [
                'draft' => (int) ($callsByStatus['draft'] ?? 0),
                'approved' => (int) ($callsByStatus['approved'] ?? 0),
                'rejected' => (int) ($callsByStatus['rejected'] ?? 0),
                'published' => (int) ($callsByStatus['published'] ?? 0),
                'top_ranked' => $topCalls,
            ],
            'risk_exposure' => [
                'open_risk_percent' => $risk['open_risk_percent'],
                'violations' => $risk['violations'] ?? [],
                'blocked' => (bool) ($risk['blocked'] ?? false),
                'exposure_by_sector' => $risk['exposure']['by_sector'] ?? [],
                'exposure_by_asset' => $risk['exposure']['by_asset'] ?? [],
                'correlations' => $risk['correlations'] ?? [],
            ],
            'performance' => [
                'summary' => $performanceSummary,
                'equity_curve' => array_slice($equityCurve, -60),
            ],
            'alerts' => [
                'unread_count' => $alertsUnread,
                'latest' => $alerts,
            ],
        ];
    }
}
