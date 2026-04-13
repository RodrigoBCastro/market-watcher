<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class ConfidenceScoreDTO
{
    /**
     * @param  array<string, float>  $components
     */
    public function __construct(
        public float $score,
        public string $label,
        public array $components,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'confidence_score' => $this->score,
            'confidence_label' => $this->label,
            'components' => $this->components,
        ];
    }
}
