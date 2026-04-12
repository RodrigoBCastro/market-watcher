<?php

declare(strict_types=1);

namespace App\Services\Optimization;

use App\Contracts\ScoreOptimizerInterface;
use App\DTOs\OptimizerResultDTO;
use App\Models\SetupMetric;
use App\Models\TradeOutcome;
use App\Services\Ranking\FinalRankService;
use Illuminate\Support\Facades\Cache;

class ScoreOptimizerService implements ScoreOptimizerInterface
{
    public function __construct(private readonly FinalRankService $finalRankService)
    {
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function optimize(array $options = []): OptimizerResultDTO
    {
        $profiles = $options['profiles'] ?? $this->defaultProfiles();
        $minRank = (float) ($options['min_rank'] ?? config('market.optimizer.min_rank', 55));

        $outcomes = TradeOutcome::query()->with('tradeCall')->get();

        $setupExpectancy = SetupMetric::query()
            ->pluck('expectancy', 'setup_code')
            ->map(static fn ($value): float => (float) $value)
            ->all();

        $tested = [];
        $best = null;

        foreach ($profiles as $profile) {
            $technicalWeight = (float) ($profile['technical_weight'] ?? 0.6);
            $expectancyWeight = (float) ($profile['expectancy_weight'] ?? 0.4);

            $sum = $technicalWeight + $expectancyWeight;
            if ($sum <= 0.0) {
                continue;
            }

            $technicalWeight /= $sum;
            $expectancyWeight /= $sum;

            $selectedPnls = [];
            $wins = 0;

            foreach ($outcomes as $outcome) {
                $call = $outcome->tradeCall;
                if ($call === null) {
                    continue;
                }

                $expectancy = (float) ($setupExpectancy[$call->setup_code] ?? 0.0);
                $rank = ($call->score * $technicalWeight) + ($expectancy * $expectancyWeight);

                if ($rank < $minRank) {
                    continue;
                }

                $pnl = (float) $outcome->pnl_percent;
                $selectedPnls[] = $pnl;

                if ($outcome->result === 'win') {
                    $wins++;
                }
            }

            $selectedTrades = count($selectedPnls);
            $avgPnl = $selectedTrades > 0 ? array_sum($selectedPnls) / $selectedTrades : -INF;
            $winrate = $selectedTrades > 0 ? ($wins / $selectedTrades) * 100 : 0.0;
            $performance = $selectedTrades > 0 ? ($avgPnl + ($winrate / 100)) : -INF;

            $profileResult = [
                'technical_weight' => round($technicalWeight, 4),
                'expectancy_weight' => round($expectancyWeight, 4),
                'selected_trades' => $selectedTrades,
                'avg_pnl_percent' => $selectedTrades > 0 ? round($avgPnl, 4) : null,
                'winrate' => $selectedTrades > 0 ? round($winrate, 3) : null,
                'performance_score' => is_finite($performance) ? round($performance, 6) : null,
            ];

            $tested[] = $profileResult;

            if ($best === null || (($profileResult['performance_score'] ?? -INF) > ($best['performance_score'] ?? -INF))) {
                $best = $profileResult;
            }
        }

        $bestWeights = [
            'technical_weight' => (float) ($best['technical_weight'] ?? $this->finalRankService->weights()['technical_weight']),
            'expectancy_weight' => (float) ($best['expectancy_weight'] ?? $this->finalRankService->weights()['expectancy_weight']),
        ];

        return new OptimizerResultDTO(
            bestWeights: $bestWeights,
            testedProfiles: $tested,
            selectedTrades: (int) ($best['selected_trades'] ?? 0),
            performanceScore: (float) ($best['performance_score'] ?? 0.0),
        );
    }

    /**
     * @param  array<string, float>  $weights
     */
    public function apply(array $weights): void
    {
        $technical = (float) ($weights['technical_weight'] ?? 0.6);
        $expectancy = (float) ($weights['expectancy_weight'] ?? 0.4);

        $sum = $technical + $expectancy;

        if ($sum <= 0) {
            return;
        }

        Cache::forever('market:ranking_weights', [
            'technical_weight' => $technical / $sum,
            'expectancy_weight' => $expectancy / $sum,
        ]);
    }

    /**
     * @return array<string, float>
     */
    public function currentWeights(): array
    {
        /** @var array<string, float>|null $weights */
        $weights = Cache::get('market:ranking_weights');

        if ($weights === null) {
            return $this->finalRankService->weights();
        }

        return [
            'technical_weight' => (float) ($weights['technical_weight'] ?? 0.6),
            'expectancy_weight' => (float) ($weights['expectancy_weight'] ?? 0.4),
        ];
    }

    /**
     * @return array<int, array<string, float>>
     */
    private function defaultProfiles(): array
    {
        return [
            ['technical_weight' => 0.6, 'expectancy_weight' => 0.4],
            ['technical_weight' => 0.7, 'expectancy_weight' => 0.3],
            ['technical_weight' => 0.5, 'expectancy_weight' => 0.5],
            ['technical_weight' => 0.8, 'expectancy_weight' => 0.2],
            ['technical_weight' => 0.4, 'expectancy_weight' => 0.6],
        ];
    }
}
