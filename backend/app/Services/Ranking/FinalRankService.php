<?php

declare(strict_types=1);

namespace App\Services\Ranking;

use App\Models\SetupMetric;
use Illuminate\Support\Facades\Cache;

class FinalRankService
{
    public function compute(float $technicalScore, float $expectancy): float
    {
        $weights = $this->weights();

        return round(($technicalScore * $weights['technical_weight']) + ($expectancy * $weights['expectancy_weight']), 4);
    }

    public function classify(?SetupMetric $metric, float $technicalScore): string
    {
        $minHistory = (int) config('market.calls.min_history', 8);

        if ($metric === null || $metric->total_trades < $minHistory) {
            return 'setup experimental';
        }

        if ($metric->expectancy > 0 && $metric->winrate >= 60 && $technicalScore >= 70) {
            return 'alta probabilidade';
        }

        if ($metric->expectancy > 0 && $metric->edge >= 0.8) {
            return 'alto edge';
        }

        if ($metric->expectancy > 0 && $metric->winrate > 50 && $metric->is_enabled) {
            return 'setup validado';
        }

        return 'setup experimental';
    }

    /**
     * @return array<string, float>
     */
    public function weights(): array
    {
        /** @var array<string, float>|null $cached */
        $cached = Cache::get('market:ranking_weights');

        $technical = (float) ($cached['technical_weight'] ?? config('market.ranking.technical_weight', 0.6));
        $expectancy = (float) ($cached['expectancy_weight'] ?? config('market.ranking.expectancy_weight', 0.4));

        $sum = $technical + $expectancy;

        if ($sum <= 0.0) {
            return [
                'technical_weight' => 0.6,
                'expectancy_weight' => 0.4,
            ];
        }

        return [
            'technical_weight' => $technical / $sum,
            'expectancy_weight' => $expectancy / $sum,
        ];
    }
}
