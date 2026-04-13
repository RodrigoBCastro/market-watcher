<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class PerformanceSummaryDTO
{
    /**
     * @param  array<string, mixed>|null  $bestTrade
     * @param  array<string, mixed>|null  $worstTrade
     */
    public function __construct(
        public int $totalTrades,
        public int $wins,
        public int $losses,
        public int $breakevens,
        public float $winrate,
        public ?float $payoff,
        public float $expectancy,
        public ?float $profitFactor,
        public float $cumulativeReturnPercent,
        public float $maxDrawdownPercent,
        public float $avgTradeDurationDays,
        public ?array $bestTrade,
        public ?array $worstTrade,
        public int $maxWinningStreak,
        public int $maxLosingStreak,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_trades' => $this->totalTrades,
            'wins' => $this->wins,
            'losses' => $this->losses,
            'breakevens' => $this->breakevens,
            'winrate' => $this->winrate,
            'payoff' => $this->payoff,
            'expectancy' => $this->expectancy,
            'profit_factor' => $this->profitFactor,
            'cumulative_return_percent' => $this->cumulativeReturnPercent,
            'max_drawdown_percent' => $this->maxDrawdownPercent,
            'avg_trade_duration_days' => $this->avgTradeDurationDays,
            'best_trade' => $this->bestTrade,
            'worst_trade' => $this->worstTrade,
            'max_winning_streak' => $this->maxWinningStreak,
            'max_losing_streak' => $this->maxLosingStreak,
        ];
    }
}
