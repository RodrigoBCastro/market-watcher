<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class OptimizerResultDTO
{
    /**
     * @param  array<string, float>  $bestWeights
     * @param  array<int, array<string, mixed>>  $testedProfiles
     */
    public function __construct(
        public array $bestWeights,
        public array $testedProfiles,
        public int $selectedTrades,
        public float $performanceScore,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'best_weights' => $this->bestWeights,
            'tested_profiles' => $this->testedProfiles,
            'selected_trades' => $this->selectedTrades,
            'performance_score' => $this->performanceScore,
        ];
    }
}
