<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class MarketRegimeDTO
{
    /**
     * @param  array<string, float|int|string|null>  $metrics
     */
    public function __construct(
        public string $regime,
        public float $contextScore,
        public array $metrics,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'regime' => $this->regime,
            'context_score' => $this->contextScore,
            'metrics' => $this->metrics,
        ];
    }
}
