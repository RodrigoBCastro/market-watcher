<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class SetupMetricDTO
{
    public function __construct(
        public string $setupCode,
        public int $totalTrades,
        public int $wins,
        public int $losses,
        public float $winrate,
        public float $avgGain,
        public float $avgLoss,
        public float $expectancy,
        public float $edge,
        public bool $isEnabled,
        public string $classification,
    ) {
    }

    /**
     * @return array<string, int|float|string|bool>
     */
    public function toArray(): array
    {
        return [
            'setup_code' => $this->setupCode,
            'total_trades' => $this->totalTrades,
            'wins' => $this->wins,
            'losses' => $this->losses,
            'winrate' => $this->winrate,
            'avg_gain' => $this->avgGain,
            'avg_loss' => $this->avgLoss,
            'expectancy' => $this->expectancy,
            'edge' => $this->edge,
            'is_enabled' => $this->isEnabled,
            'classification' => $this->classification,
        ];
    }
}
