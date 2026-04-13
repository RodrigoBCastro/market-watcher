<?php

declare(strict_types=1);

namespace App\Services\Calls;

use App\Contracts\ConfidenceScoreServiceInterface;
use App\Contracts\MarketRegimeServiceInterface;
use App\Contracts\TradeCallServiceInterface;
use App\DTOs\TradeCallDTO;
use App\Enums\CallReviewDecision;
use App\Enums\TradeCallStatus;
use App\Models\AssetAnalysisScore;
use App\Models\SetupMetric;
use App\Models\TradeCall;
use App\Models\TradeOutcome;
use App\Services\Ranking\FinalRankService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TradeCallService implements TradeCallServiceInterface
{
    public function __construct(
        private readonly TradeCallFilterService $tradeCallFilterService,
        private readonly FinalRankService $finalRankService,
        private readonly MarketRegimeServiceInterface $marketRegimeService,
        private readonly ConfidenceScoreServiceInterface $confidenceScoreService,
    ) {
    }

    /**
     * @return array<int, TradeCallDTO>
     */
    public function generateDraftCalls(?\DateTimeInterface $referenceDate = null): array
    {
        $tradeDate = $this->resolveTradeDate($referenceDate);

        if ($tradeDate === null) {
            return [];
        }

        $regime = $this->marketRegimeService->current();
        $regimeRules = $this->marketRegimeService->rulesForRegime($regime->regime);

        $maxCalls = max(1, (int) ($regimeRules['max_calls'] ?? config('market.calls.max_calls_per_cycle', 8)));
        $minScore = (float) ($regimeRules['min_score'] ?? config('market.calls.min_score', 70));

        $scores = AssetAnalysisScore::query()
            ->with('monitoredAsset:id,ticker')
            ->whereDate('trade_date', $tradeDate->toDateString())
            ->where('final_score', '>=', $minScore)
            ->whereHas('monitoredAsset', static function ($query): void {
                $query->where('eligible_for_calls', true)->where('is_active', true);
            })
            ->orderByDesc('final_score')
            ->get();

        $created = [];

        foreach ($scores as $score) {
            if (count($created) >= $maxCalls) {
                break;
            }

            if ($score->setup_code === null || $score->setup_label === null) {
                continue;
            }

            if ($score->suggested_entry === null || $score->suggested_stop === null || $score->suggested_target === null) {
                continue;
            }

            $metric = SetupMetric::query()->where('setup_code', $score->setup_code)->first();
            $filter = $this->tradeCallFilterService->evaluate($score, $metric, $minScore);

            if (! $filter['eligible']) {
                continue;
            }

            $existing = TradeCall::query()->where([
                'monitored_asset_id' => $score->monitored_asset_id,
                'trade_date' => $score->trade_date?->toDateString(),
                'setup_code' => $score->setup_code,
            ])->first();

            if ($existing !== null && $existing->status !== TradeCallStatus::DRAFT->value) {
                continue;
            }

            $finalRank = $this->finalRankService->compute((float) $score->final_score, (float) ($metric?->expectancy ?? 0.0));
            $classification = $this->finalRankService->classify($metric, (float) $score->final_score);
            $confidence = $this->confidenceScoreService->calculate(
                technicalScore: (float) $score->final_score,
                expectancy: (float) ($metric?->expectancy ?? 0.0),
                marketRegime: $regime->regime,
            );

            $model = TradeCall::query()->updateOrCreate([
                'monitored_asset_id' => $score->monitored_asset_id,
                'trade_date' => $score->trade_date?->toDateString(),
                'setup_code' => $score->setup_code,
            ], [
                'setup_label' => $score->setup_label,
                'entry_price' => (float) $score->suggested_entry,
                'stop_price' => (float) $score->suggested_stop,
                'target_price' => (float) $score->suggested_target,
                'risk_percent' => (float) ($score->risk_percent ?? 0.0),
                'reward_percent' => (float) ($score->reward_percent ?? 0.0),
                'rr_ratio' => (float) ($score->rr_ratio ?? 0.0),
                'score' => (float) $score->final_score,
                'final_rank_score' => $finalRank,
                'advanced_classification' => $classification,
                'confidence_score' => $confidence->score,
                'confidence_label' => $confidence->label,
                'market_regime' => $regime->regime,
                'expectancy_snapshot' => round((float) ($metric?->expectancy ?? 0.0), 4),
                'market_context_score_snapshot' => round((float) $regime->contextScore, 4),
                'status' => TradeCallStatus::DRAFT->value,
                'generated_by_engine' => true,
                'published_at' => null,
            ]);

            $created[] = $this->toDto($model->load('monitoredAsset:id,ticker'), $metric);
        }

        return $created;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCalls(?string $status = null, int $limit = 100): array
    {
        return TradeCall::query()
            ->with(['monitoredAsset:id,ticker', 'outcome'])
            ->when($status !== null, static function ($query, string $status): void {
                $query->where('status', $status);
            })
            ->orderByDesc('trade_date')
            ->orderByDesc('final_rank_score')
            ->limit($limit)
            ->get()
            ->map(function (TradeCall $call): array {
                $metric = SetupMetric::query()->where('setup_code', $call->setup_code)->first();

                return [
                    ...$this->toDto($call, $metric)->toArray(),
                    'has_outcome' => $call->outcome !== null,
                ];
            })
            ->all();
    }

    public function getCall(int $id): TradeCallDTO
    {
        $call = TradeCall::query()->with('monitoredAsset:id,ticker')->find($id);

        if ($call === null) {
            throw (new ModelNotFoundException())->setModel(TradeCall::class, [$id]);
        }

        $metric = SetupMetric::query()->where('setup_code', $call->setup_code)->first();

        return $this->toDto($call, $metric);
    }

    public function approve(int $id, int $reviewerId, ?string $comments = null): TradeCallDTO
    {
        $call = TradeCall::query()->findOrFail($id);

        $call->reviews()->create([
            'reviewer_id' => $reviewerId,
            'decision' => CallReviewDecision::APPROVE->value,
            'comments' => $comments,
        ]);

        $call->update([
            'status' => TradeCallStatus::APPROVED->value,
        ]);

        $metric = SetupMetric::query()->where('setup_code', $call->setup_code)->first();

        return $this->toDto(($call->fresh()?->load('monitoredAsset:id,ticker')) ?? $call->load('monitoredAsset:id,ticker'), $metric);
    }

    public function reject(int $id, int $reviewerId, ?string $comments = null): TradeCallDTO
    {
        $call = TradeCall::query()->findOrFail($id);

        $call->reviews()->create([
            'reviewer_id' => $reviewerId,
            'decision' => CallReviewDecision::REJECT->value,
            'comments' => $comments,
        ]);

        $call->update([
            'status' => TradeCallStatus::REJECTED->value,
        ]);

        $metric = SetupMetric::query()->where('setup_code', $call->setup_code)->first();

        return $this->toDto(($call->fresh()?->load('monitoredAsset:id,ticker')) ?? $call->load('monitoredAsset:id,ticker'), $metric);
    }

    public function publish(int $id): TradeCallDTO
    {
        $call = TradeCall::query()->findOrFail($id);

        $call->update([
            'status' => TradeCallStatus::PUBLISHED->value,
            'published_at' => now(),
        ]);

        $metric = SetupMetric::query()->where('setup_code', $call->setup_code)->first();

        return $this->toDto(($call->fresh()?->load('monitoredAsset:id,ticker')) ?? $call->load('monitoredAsset:id,ticker'), $metric);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOutcomes(int $limit = 100): array
    {
        return TradeOutcome::query()
            ->with(['monitoredAsset:id,ticker', 'tradeCall:id,status,trade_date'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(static fn (TradeOutcome $item): array => [
                'id' => $item->id,
                'symbol' => $item->monitoredAsset?->ticker,
                'setup_code' => $item->setup_code,
                'entry_price' => (float) $item->entry_price,
                'stop_price' => (float) $item->stop_price,
                'target_price' => (float) $item->target_price,
                'exit_price' => (float) $item->exit_price,
                'result' => $item->result,
                'pnl_percent' => (float) $item->pnl_percent,
                'duration_days' => (int) $item->duration_days,
                'created_at' => $item->created_at?->toIso8601String(),
                'trade_call' => [
                    'id' => $item->trade_call_id,
                    'status' => $item->tradeCall?->status,
                    'trade_date' => $item->tradeCall?->trade_date?->toDateString(),
                ],
            ])
            ->all();
    }

    private function resolveTradeDate(?\DateTimeInterface $referenceDate): ?CarbonImmutable
    {
        if ($referenceDate !== null) {
            return CarbonImmutable::instance((new \DateTimeImmutable())->setTimestamp($referenceDate->getTimestamp()));
        }

        $latest = AssetAnalysisScore::query()->max('trade_date');

        return $latest !== null ? CarbonImmutable::parse((string) $latest) : null;
    }

    private function toDto(TradeCall $call, ?SetupMetric $metric): TradeCallDTO
    {
        return new TradeCallDTO(
            id: (int) $call->id,
            symbol: (string) ($call->monitoredAsset?->ticker ?? ''),
            tradeDate: CarbonImmutable::parse((string) $call->trade_date?->toDateString()),
            setupCode: (string) $call->setup_code,
            setupLabel: (string) $call->setup_label,
            entryPrice: (float) $call->entry_price,
            stopPrice: (float) $call->stop_price,
            targetPrice: (float) $call->target_price,
            riskPercent: (float) $call->risk_percent,
            rewardPercent: (float) $call->reward_percent,
            rrRatio: (float) $call->rr_ratio,
            score: (float) $call->score,
            finalRankScore: (float) $call->final_rank_score,
            advancedClassification: $call->advanced_classification,
            confidenceScore: $call->confidence_score !== null ? (float) $call->confidence_score : null,
            confidenceLabel: $call->confidence_label,
            marketRegime: $call->market_regime,
            status: (string) $call->status,
            generatedByEngine: (bool) $call->generated_by_engine,
            publishedAt: $call->published_at !== null ? CarbonImmutable::parse($call->published_at) : null,
            expectancy: $metric?->expectancy,
            winrate: $metric?->winrate,
            edge: $metric?->edge,
        );
    }
}
