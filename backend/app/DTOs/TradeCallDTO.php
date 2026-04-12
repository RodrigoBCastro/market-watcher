<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonImmutable;

readonly class TradeCallDTO
{
    public function __construct(
        public int $id,
        public string $symbol,
        public CarbonImmutable $tradeDate,
        public string $setupCode,
        public string $setupLabel,
        public float $entryPrice,
        public float $stopPrice,
        public float $targetPrice,
        public float $riskPercent,
        public float $rewardPercent,
        public float $rrRatio,
        public float $score,
        public float $finalRankScore,
        public ?string $advancedClassification,
        public string $status,
        public bool $generatedByEngine,
        public ?CarbonImmutable $publishedAt,
        public ?float $expectancy = null,
        public ?float $winrate = null,
        public ?float $edge = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'trade_date' => $this->tradeDate->toDateString(),
            'setup_code' => $this->setupCode,
            'setup_label' => $this->setupLabel,
            'entry_price' => $this->entryPrice,
            'stop_price' => $this->stopPrice,
            'target_price' => $this->targetPrice,
            'risk_percent' => $this->riskPercent,
            'reward_percent' => $this->rewardPercent,
            'rr_ratio' => $this->rrRatio,
            'score' => $this->score,
            'final_rank_score' => $this->finalRankScore,
            'advanced_classification' => $this->advancedClassification,
            'status' => $this->status,
            'generated_by_engine' => $this->generatedByEngine,
            'published_at' => $this->publishedAt?->toIso8601String(),
            'expectancy' => $this->expectancy,
            'winrate' => $this->winrate,
            'edge' => $this->edge,
        ];
    }
}
