<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class PortfolioPositionSnapshotDTO
{
    /**
     * @param  array<string, mixed>|null  $tradeCall
     */
    public function __construct(
        public int $id,
        public string $ticker,
        public string $assetName,
        public string $sector,
        public string $status,
        public string $entryDate,
        public float $entryPrice,
        public float $quantity,
        public float $investedAmount,
        public ?float $currentPrice,
        public ?float $stopPrice,
        public ?float $targetPrice,
        public ?float $confidenceScore,
        public ?string $confidenceLabel,
        public ?string $marketRegime,
        public float $currentValue,
        public float $unrealizedPnl,
        public float $unrealizedPnlPercent,
        public float $daysInTrade,
        public ?float $distanceToStopPercent,
        public ?float $distanceToTargetPercent,
        public ?array $tradeCall = null,
        public ?string $notes = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ticker' => $this->ticker,
            'asset_name' => $this->assetName,
            'sector' => $this->sector,
            'status' => $this->status,
            'entry_date' => $this->entryDate,
            'entry_price' => $this->entryPrice,
            'quantity' => $this->quantity,
            'invested_amount' => $this->investedAmount,
            'current_price' => $this->currentPrice,
            'stop_price' => $this->stopPrice,
            'target_price' => $this->targetPrice,
            'confidence_score' => $this->confidenceScore,
            'confidence_label' => $this->confidenceLabel,
            'market_regime' => $this->marketRegime,
            'current_value' => $this->currentValue,
            'unrealized_pnl' => $this->unrealizedPnl,
            'unrealized_pnl_percent' => $this->unrealizedPnlPercent,
            'days_in_trade' => $this->daysInTrade,
            'distance_to_stop_percent' => $this->distanceToStopPercent,
            'distance_to_target_percent' => $this->distanceToTargetPercent,
            'trade_call' => $this->tradeCall,
            'notes' => $this->notes,
        ];
    }
}
