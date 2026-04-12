<?php

declare(strict_types=1);

namespace App\Services\Scoring;

use App\Contracts\ScoreEngineInterface;
use App\DTOs\ScoreBreakdownDTO;
use App\Enums\ScoreClassification;

class CompositeScoreEngine implements ScoreEngineInterface
{
    public function __construct(
        private readonly TrendScoreService $trendScoreService,
        private readonly MovingAverageAlignmentScoreService $movingAverageAlignmentScoreService,
        private readonly StructureScoreService $structureScoreService,
        private readonly MomentumScoreService $momentumScoreService,
        private readonly VolumeScoreService $volumeScoreService,
        private readonly RiskScoreService $riskScoreService,
        private readonly MarketContextScoreService $marketContextScoreService,
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $indicators
     * @param  array<string, mixed>  $setupContext
     * @param  array<string, mixed>  $marketContext
     */
    public function score(array $quotes, array $indicators, array $setupContext, array $marketContext): ScoreBreakdownDTO
    {
        $current = (array) ($indicators['current'] ?? $indicators);
        $history = (array) ($indicators['history'] ?? [$current]);

        $trend = $this->trendScoreService->score($quotes, $current, $history);
        $movingAverage = $this->movingAverageAlignmentScoreService->score($current);
        $structure = $this->structureScoreService->score($quotes, $current, $setupContext);
        $momentum = $this->momentumScoreService->score($current, $history);
        $volume = $this->volumeScoreService->score($quotes, $current, $setupContext);
        $risk = $this->riskScoreService->score($current, $setupContext);
        $marketContextScore = $this->marketContextScoreService->score($marketContext);

        $finalScore = max(0.0, min(100.0, $trend + $movingAverage + $structure + $momentum + $volume + $risk + $marketContextScore));
        $classification = ScoreClassification::fromScore($finalScore)->value;

        return new ScoreBreakdownDTO(
            trendScore: $trend,
            movingAverageScore: $movingAverage,
            structureScore: $structure,
            momentumScore: $momentum,
            volumeScore: $volume,
            riskScore: $risk,
            marketContextScore: $marketContextScore,
            finalScore: $finalScore,
            classification: $classification,
        );
    }
}
