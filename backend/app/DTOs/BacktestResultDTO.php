<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonImmutable;

readonly class BacktestResultDTO
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $strategyName,
        public int $totalTrades,
        public float $winrate,
        public float $totalReturn,
        public float $maxDrawdown,
        public ?float $profitFactor,
        public array $metadata,
        public ?CarbonImmutable $createdAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'strategy_name' => $this->strategyName,
            'total_trades' => $this->totalTrades,
            'winrate' => $this->winrate,
            'total_return' => $this->totalReturn,
            'max_drawdown' => $this->maxDrawdown,
            'profit_factor' => $this->profitFactor,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt?->toIso8601String(),
        ];
    }
}
