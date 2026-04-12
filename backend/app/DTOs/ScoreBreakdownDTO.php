<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class ScoreBreakdownDTO
{
    public function __construct(
        public float $trendScore,
        public float $movingAverageScore,
        public float $structureScore,
        public float $momentumScore,
        public float $volumeScore,
        public float $riskScore,
        public float $marketContextScore,
        public float $finalScore,
        public string $classification,
    ) {
    }

    /**
     * @return array<string, float|string>
     */
    public function toArray(): array
    {
        return [
            'trend_score' => round($this->trendScore, 2),
            'moving_average_score' => round($this->movingAverageScore, 2),
            'structure_score' => round($this->structureScore, 2),
            'momentum_score' => round($this->momentumScore, 2),
            'volume_score' => round($this->volumeScore, 2),
            'risk_score' => round($this->riskScore, 2),
            'market_context_score' => round($this->marketContextScore, 2),
            'final_score' => round($this->finalScore, 2),
            'classification' => $this->classification,
        ];
    }
}
