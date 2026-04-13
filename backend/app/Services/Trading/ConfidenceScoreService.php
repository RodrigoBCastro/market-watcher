<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\ConfidenceScoreServiceInterface;
use App\Contracts\MarketRegimeServiceInterface;
use App\DTOs\ConfidenceScoreDTO;

class ConfidenceScoreService implements ConfidenceScoreServiceInterface
{
    public function __construct(private readonly MarketRegimeServiceInterface $marketRegimeService)
    {
    }

    public function calculate(float $technicalScore, float $expectancy, string $marketRegime): ConfidenceScoreDTO
    {
        $technicalScore = $this->clamp($technicalScore, 0.0, 100.0);
        $expectancyNormalized = $this->normalizeExpectancy($expectancy);
        $marketContextScore = $this->clamp($this->marketRegimeService->contextScoreFromRegime($marketRegime), 0.0, 100.0);

        $technicalWeight = (float) config('market.confidence.weights.technical', 0.5);
        $expectancyWeight = (float) config('market.confidence.weights.expectancy', 0.3);
        $marketWeight = (float) config('market.confidence.weights.market_context', 0.2);

        $score =
            ($technicalScore * $technicalWeight)
            + ($expectancyNormalized * $expectancyWeight)
            + ($marketContextScore * $marketWeight);

        $score = round($this->clamp($score, 0.0, 100.0), 4);

        return new ConfidenceScoreDTO(
            score: $score,
            label: $this->labelForScore($score),
            components: [
                'technical_score' => round($technicalScore, 4),
                'expectancy_normalized' => round($expectancyNormalized, 4),
                'market_context_score' => round($marketContextScore, 4),
                'technical_component' => round($technicalScore * $technicalWeight, 4),
                'expectancy_component' => round($expectancyNormalized * $expectancyWeight, 4),
                'market_context_component' => round($marketContextScore * $marketWeight, 4),
            ],
        );
    }

    private function normalizeExpectancy(float $expectancy): float
    {
        $min = (float) config('market.confidence.expectancy_min', -5.0);
        $max = (float) config('market.confidence.expectancy_max', 5.0);

        if ($max <= $min) {
            return 50.0;
        }

        $normalized = (($expectancy - $min) / ($max - $min)) * 100;

        return $this->clamp($normalized, 0.0, 100.0);
    }

    private function labelForScore(float $score): string
    {
        if ($score >= 85.0) {
            return 'Convicção muito alta';
        }

        if ($score >= 70.0) {
            return 'Convicção alta';
        }

        if ($score >= 55.0) {
            return 'Convicção moderada';
        }

        return 'Convicção baixa';
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($value, $max));
    }
}
