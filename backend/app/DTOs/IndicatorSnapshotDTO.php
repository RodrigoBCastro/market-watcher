<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonImmutable;

readonly class IndicatorSnapshotDTO
{
    /**
     * @param  array<string, float|null>  $movingAverages
     * @param  array<string, float|null>  $oscillators
     * @param  array<string, float|null>  $priceStructure
     * @param  array<string, float|null>  $volatilityMetrics
     * @param  array<string, float|null>  $volumeMetrics
     */
    public function __construct(
        public string $symbol,
        public CarbonImmutable $tradeDate,
        public array $movingAverages,
        public array $oscillators,
        public array $priceStructure,
        public array $volatilityMetrics,
        public array $volumeMetrics,
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
            'moving_averages' => $this->movingAverages,
            'oscillators' => $this->oscillators,
            'price_structure' => $this->priceStructure,
            'volatility_metrics' => $this->volatilityMetrics,
            'volume_metrics' => $this->volumeMetrics,
        ];
    }
}
