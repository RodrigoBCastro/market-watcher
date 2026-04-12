<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonImmutable;

readonly class TradeDecisionDTO
{
    /**
     * @param  array<int, string>  $alerts
     * @param  array<string, float|string>  $scoreBreakdown
     */
    public function __construct(
        public string $symbol,
        public CarbonImmutable $tradeDate,
        public string $recommendation,
        public string $classification,
        public ?string $setupCode,
        public ?string $setupLabel,
        public ?float $entry,
        public ?float $stop,
        public ?float $target,
        public ?float $riskPercent,
        public ?float $rewardPercent,
        public ?float $rrRatio,
        public array $alerts,
        public string $rationale,
        public array $scoreBreakdown,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'trade_date' => $this->tradeDate->toDateString(),
            'recommendation' => $this->recommendation,
            'classification' => $this->classification,
            'setup' => [
                'code' => $this->setupCode,
                'label' => $this->setupLabel,
            ],
            'prices' => [
                'entry' => $this->entry,
                'stop' => $this->stop,
                'target' => $this->target,
            ],
            'risk_metrics' => [
                'risk_percent' => $this->riskPercent,
                'reward_percent' => $this->rewardPercent,
                'rr_ratio' => $this->rrRatio,
            ],
            'alerts' => $this->alerts,
            'rationale' => $this->rationale,
            'score_breakdown' => $this->scoreBreakdown,
        ];
    }
}
