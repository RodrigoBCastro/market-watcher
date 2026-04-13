<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class PositionSizingResultDTO
{
    /**
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public float $capitalTotal,
        public float $riskPerTradePercent,
        public float $riskAmount,
        public float $stopDistancePercent,
        public float $suggestedPositionValue,
        public float $suggestedSharesQuantity,
        public float $allocationPercent,
        public bool $cappedByRiskRules,
        public array $warnings = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'capital_total' => $this->capitalTotal,
            'risk_per_trade_percent' => $this->riskPerTradePercent,
            'risk_amount' => $this->riskAmount,
            'stop_distance_percent' => $this->stopDistancePercent,
            'suggested_position_value' => $this->suggestedPositionValue,
            'suggested_shares_quantity' => $this->suggestedSharesQuantity,
            'allocation_percent' => $this->allocationPercent,
            'capped_by_risk_rules' => $this->cappedByRiskRules,
            'warnings' => $this->warnings,
        ];
    }
}
