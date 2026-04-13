<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\PerformanceAnalyticsServiceInterface;
use App\Contracts\RiskSettingsServiceInterface;
use App\DTOs\PerformanceSummaryDTO;
use App\Models\EquityCurvePoint;
use App\Models\PortfolioClosedPosition;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PerformanceAnalyticsService implements PerformanceAnalyticsServiceInterface
{
    public function __construct(private readonly RiskSettingsServiceInterface $riskSettingsService)
    {
    }

    public function summary(int $userId, array $filters = []): PerformanceSummaryDTO
    {
        $rows = $this->baseClosedQuery($userId, $filters)
            ->orderBy('exit_date')
            ->orderBy('created_at')
            ->get();

        $totalTrades = $rows->count();
        $wins = $rows->where('result', 'win')->count();
        $losses = $rows->where('result', 'loss')->count();
        $breakevens = $rows->where('result', 'breakeven')->count();

        $winrate = $totalTrades > 0 ? ($wins / $totalTrades) * 100 : 0.0;

        $avgGain = (float) ($rows->where('gross_pnl_percent', '>', 0)->avg('gross_pnl_percent') ?? 0.0);
        $avgLoss = abs((float) ($rows->where('gross_pnl_percent', '<', 0)->avg('gross_pnl_percent') ?? 0.0));

        $payoff = $avgLoss > 0.0 ? round($avgGain / $avgLoss, 4) : null;

        $expectancy = (($winrate / 100) * $avgGain) - ((1 - ($winrate / 100)) * $avgLoss);

        $profitFactor = $this->profitFactor($rows);

        $capitalTotal = $this->riskSettingsService->getForUser($userId)->totalCapital;
        $sumPnl = (float) $rows->sum('gross_pnl');

        $cumulativeReturnPercent = $capitalTotal > 0
            ? ($sumPnl / $capitalTotal) * 100
            : 0.0;

        $maxDrawdownPercent = $this->maxDrawdownPercent($rows, $capitalTotal);

        $avgDuration = (float) ($rows->avg('duration_days') ?? 0.0);

        $best = $rows->sortByDesc('gross_pnl')->first();
        $worst = $rows->sortBy('gross_pnl')->first();

        [$maxWinningStreak, $maxLosingStreak] = $this->streaks($rows);

        return new PerformanceSummaryDTO(
            totalTrades: $totalTrades,
            wins: $wins,
            losses: $losses,
            breakevens: $breakevens,
            winrate: round($winrate, 4),
            payoff: $payoff,
            expectancy: round($expectancy, 4),
            profitFactor: $profitFactor,
            cumulativeReturnPercent: round($cumulativeReturnPercent, 4),
            maxDrawdownPercent: round($maxDrawdownPercent, 4),
            avgTradeDurationDays: round($avgDuration, 2),
            bestTrade: $best !== null ? $this->closedToArray($best) : null,
            worstTrade: $worst !== null ? $this->closedToArray($worst) : null,
            maxWinningStreak: $maxWinningStreak,
            maxLosingStreak: $maxLosingStreak,
        );
    }

    public function equityCurve(int $userId, array $filters = []): array
    {
        $query = EquityCurvePoint::query()->where('user_id', $userId);

        if (isset($filters['from'])) {
            $query->whereDate('reference_date', '>=', (string) $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->whereDate('reference_date', '<=', (string) $filters['to']);
        }

        $points = $query
            ->orderBy('reference_date')
            ->get()
            ->map(static fn (EquityCurvePoint $row): array => [
                'reference_date' => $row->reference_date?->toDateString(),
                'equity_value' => (float) $row->equity_value,
                'cash_value' => (float) $row->cash_value,
                'invested_value' => (float) $row->invested_value,
                'open_risk_percent' => (float) $row->open_risk_percent,
                'cumulative_return_percent' => (float) $row->cumulative_return_percent,
            ])
            ->all();

        if ($points !== []) {
            return $points;
        }

        return $this->fallbackEquityCurveFromClosedTrades($userId, $filters);
    }

    public function bySetup(int $userId, array $filters = []): array
    {
        $rows = $this->baseClosedQuery($userId, $filters)->get();

        return $rows
            ->groupBy(static fn (PortfolioClosedPosition $item): string => (string) ($item->portfolioPosition?->tradeCall?->setup_code ?? 'N/A'))
            ->map(function (Collection $group, string $setupCode): array {
                $total = $group->count();
                $wins = $group->where('result', 'win')->count();

                return [
                    'setup_code' => $setupCode,
                    'total_trades' => $total,
                    'winrate' => $total > 0 ? round(($wins / $total) * 100, 4) : 0.0,
                    'avg_pnl_percent' => round((float) $group->avg('gross_pnl_percent'), 4),
                    'total_pnl' => round((float) $group->sum('gross_pnl'), 2),
                    'avg_duration_days' => round((float) $group->avg('duration_days'), 2),
                ];
            })
            ->sortByDesc('total_pnl')
            ->values()
            ->all();
    }

    public function byAsset(int $userId, array $filters = []): array
    {
        $rows = $this->baseClosedQuery($userId, $filters)->get();

        return $rows
            ->groupBy(static fn (PortfolioClosedPosition $item): string => (string) ($item->portfolioPosition?->monitoredAsset?->ticker ?? 'N/A'))
            ->map(function (Collection $group, string $ticker): array {
                $total = $group->count();
                $wins = $group->where('result', 'win')->count();

                return [
                    'ticker' => $ticker,
                    'total_trades' => $total,
                    'winrate' => $total > 0 ? round(($wins / $total) * 100, 4) : 0.0,
                    'avg_pnl_percent' => round((float) $group->avg('gross_pnl_percent'), 4),
                    'total_pnl' => round((float) $group->sum('gross_pnl'), 2),
                ];
            })
            ->sortByDesc('total_pnl')
            ->values()
            ->all();
    }

    public function bySector(int $userId, array $filters = []): array
    {
        $rows = $this->baseClosedQuery($userId, $filters)->get();

        return $rows
            ->groupBy(static function (PortfolioClosedPosition $item): string {
                $asset = $item->portfolioPosition?->monitoredAsset;

                return (string) ($asset?->sectorMapping?->sector ?? $asset?->sector ?? 'Outros');
            })
            ->map(function (Collection $group, string $sector): array {
                $total = $group->count();
                $wins = $group->where('result', 'win')->count();

                return [
                    'sector' => $sector,
                    'total_trades' => $total,
                    'winrate' => $total > 0 ? round(($wins / $total) * 100, 4) : 0.0,
                    'avg_pnl_percent' => round((float) $group->avg('gross_pnl_percent'), 4),
                    'total_pnl' => round((float) $group->sum('gross_pnl'), 2),
                ];
            })
            ->sortByDesc('total_pnl')
            ->values()
            ->all();
    }

    public function byRegime(int $userId, array $filters = []): array
    {
        $rows = $this->baseClosedQuery($userId, $filters)->get();

        return $rows
            ->groupBy(static function (PortfolioClosedPosition $item): string {
                return (string) ($item->portfolioPosition?->market_regime ?? 'unknown');
            })
            ->map(function (Collection $group, string $regime): array {
                $total = $group->count();
                $wins = $group->where('result', 'win')->count();

                return [
                    'regime' => $regime,
                    'total_trades' => $total,
                    'winrate' => $total > 0 ? round(($wins / $total) * 100, 4) : 0.0,
                    'avg_pnl_percent' => round((float) $group->avg('gross_pnl_percent'), 4),
                    'total_pnl' => round((float) $group->sum('gross_pnl'), 2),
                    'avg_duration_days' => round((float) $group->avg('duration_days'), 2),
                ];
            })
            ->sortByDesc('total_pnl')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseClosedQuery(int $userId, array $filters): Builder
    {
        $query = PortfolioClosedPosition::query()
            ->with([
                'portfolioPosition:id,user_id,monitored_asset_id,trade_call_id,entry_date,entry_price,market_regime',
                'portfolioPosition.monitoredAsset:id,ticker,name,sector',
                'portfolioPosition.monitoredAsset.sectorMapping:monitored_asset_id,sector',
                'portfolioPosition.tradeCall:id,setup_code',
            ])
            ->whereHas('portfolioPosition', static function (Builder $builder) use ($userId): void {
                $builder->where('user_id', $userId);
            });

        if (isset($filters['from'])) {
            $query->whereDate('exit_date', '>=', (string) $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->whereDate('exit_date', '<=', (string) $filters['to']);
        }

        return $query;
    }

    /**
     * @param  Collection<int, PortfolioClosedPosition>  $rows
     */
    private function profitFactor(Collection $rows): ?float
    {
        $gains = (float) $rows->where('gross_pnl', '>', 0)->sum('gross_pnl');
        $losses = abs((float) $rows->where('gross_pnl', '<', 0)->sum('gross_pnl'));

        if ($losses <= 0.0) {
            return $gains > 0.0 ? round($gains, 4) : null;
        }

        return round($gains / $losses, 4);
    }

    /**
     * @param  Collection<int, PortfolioClosedPosition>  $rows
     */
    private function maxDrawdownPercent(Collection $rows, float $capitalTotal): float
    {
        if ($capitalTotal <= 0.0 || $rows->isEmpty()) {
            return 0.0;
        }

        $equity = $capitalTotal;
        $peak = $capitalTotal;
        $maxDrawdown = 0.0;

        foreach ($rows as $row) {
            $equity += (float) $row->gross_pnl;
            $peak = max($peak, $equity);

            if ($peak <= 0.0) {
                continue;
            }

            $drawdown = (($peak - $equity) / $peak) * 100;
            $maxDrawdown = max($maxDrawdown, $drawdown);
        }

        return $maxDrawdown;
    }

    /**
     * @param  Collection<int, PortfolioClosedPosition>  $rows
     * @return array{0: int, 1: int}
     */
    private function streaks(Collection $rows): array
    {
        $currentWin = 0;
        $currentLoss = 0;
        $maxWin = 0;
        $maxLoss = 0;

        foreach ($rows as $row) {
            if ($row->result === 'win') {
                $currentWin++;
                $currentLoss = 0;
                $maxWin = max($maxWin, $currentWin);
                continue;
            }

            if ($row->result === 'loss') {
                $currentLoss++;
                $currentWin = 0;
                $maxLoss = max($maxLoss, $currentLoss);
                continue;
            }

            $currentWin = 0;
            $currentLoss = 0;
        }

        return [$maxWin, $maxLoss];
    }

    private function closedToArray(PortfolioClosedPosition $row): array
    {
        $asset = $row->portfolioPosition?->monitoredAsset;

        return [
            'id' => (int) $row->id,
            'ticker' => $asset?->ticker,
            'entry_date' => $row->portfolioPosition?->entry_date?->toDateString(),
            'exit_date' => $row->exit_date?->toDateString(),
            'gross_pnl' => (float) $row->gross_pnl,
            'gross_pnl_percent' => (float) $row->gross_pnl_percent,
            'result' => $row->result,
            'duration_days' => (int) $row->duration_days,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function fallbackEquityCurveFromClosedTrades(int $userId, array $filters): array
    {
        $rows = $this->baseClosedQuery($userId, $filters)
            ->orderBy('exit_date')
            ->orderBy('created_at')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $capitalTotal = $this->riskSettingsService->getForUser($userId)->totalCapital;

        $equity = $capitalTotal;
        $points = [];

        foreach ($rows as $row) {
            $equity += (float) $row->gross_pnl;

            $points[] = [
                'reference_date' => $row->exit_date?->toDateString() ?? CarbonImmutable::today()->toDateString(),
                'equity_value' => round($equity, 2),
                'cash_value' => round($equity, 2),
                'invested_value' => 0.0,
                'open_risk_percent' => 0.0,
                'cumulative_return_percent' => $capitalTotal > 0 ? round((($equity - $capitalTotal) / $capitalTotal) * 100, 4) : 0.0,
            ];
        }

        return $points;
    }
}
