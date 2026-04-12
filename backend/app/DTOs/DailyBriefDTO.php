<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\CarbonImmutable;

readonly class DailyBriefDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $rankedIdeas
     * @param  array<int, array<string, mixed>>  $avoidList
     */
    public function __construct(
        public CarbonImmutable $briefDate,
        public string $marketBias,
        public string $marketSummary,
        public string $ibovAnalysis,
        public ?string $riskNotes,
        public string $conclusion,
        public array $rankedIdeas,
        public array $avoidList,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'brief_date' => $this->briefDate->toDateString(),
            'market_bias' => $this->marketBias,
            'market_summary' => $this->marketSummary,
            'ibov_analysis' => $this->ibovAnalysis,
            'risk_notes' => $this->riskNotes,
            'conclusion' => $this->conclusion,
            'ranked_ideas' => $this->rankedIdeas,
            'avoid_list' => $this->avoidList,
        ];
    }
}
