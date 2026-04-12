<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonImmutable;

readonly class MarketQuoteDTO
{
    public function __construct(
        public string $symbol,
        public CarbonImmutable $tradeDate,
        public float $open,
        public float $high,
        public float $low,
        public float $close,
        public ?float $adjustedClose,
        public int $volume,
        public string $source,
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
            'open' => $this->open,
            'high' => $this->high,
            'low' => $this->low,
            'close' => $this->close,
            'adjusted_close' => $this->adjustedClose,
            'volume' => $this->volume,
            'source' => $this->source,
        ];
    }
}
