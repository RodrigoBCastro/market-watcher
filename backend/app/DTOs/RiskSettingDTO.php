<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class RiskSettingDTO
{
    public function __construct(
        public int $id,
        public int $userId,
        public float $totalCapital,
        public float $riskPerTradePercent,
        public float $maxPortfolioRiskPercent,
        public int $maxOpenPositions,
        public float $maxPositionSizePercent,
        public float $maxSectorExposurePercent,
        public int $maxCorrelatedPositions,
        public bool $allowPyramiding,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'total_capital' => $this->totalCapital,
            'risk_per_trade_percent' => $this->riskPerTradePercent,
            'max_portfolio_risk_percent' => $this->maxPortfolioRiskPercent,
            'max_open_positions' => $this->maxOpenPositions,
            'max_position_size_percent' => $this->maxPositionSizePercent,
            'max_sector_exposure_percent' => $this->maxSectorExposurePercent,
            'max_correlated_positions' => $this->maxCorrelatedPositions,
            'allow_pyramiding' => $this->allowPyramiding,
        ];
    }
}
