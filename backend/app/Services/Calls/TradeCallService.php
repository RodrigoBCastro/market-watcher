<?php

declare(strict_types=1);

namespace App\Services\Calls;

use App\Contracts\AssetAnalysisScoreRepositoryInterface;
use App\Contracts\ConfidenceScoreServiceInterface;
use App\Contracts\MarketRegimeServiceInterface;
use App\Contracts\SetupMetricRepositoryInterface;
use App\Contracts\TradeCallRepositoryInterface;
use App\Contracts\TradeCallServiceInterface;
use App\Contracts\TradeOutcomeRepositoryInterface;
use App\DTOs\TradeCallDTO;
use App\Enums\CallReviewDecision;
use App\Enums\TradeCallStatus;
use App\Models\SetupMetric;
use App\Models\TradeCall;
use App\Services\Ranking\FinalRankService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TradeCallService implements TradeCallServiceInterface
{
    public function __construct(
        private readonly TradeCallFilterService                $tradeCallFilterService,
        private readonly FinalRankService                      $finalRankService,
        private readonly MarketRegimeServiceInterface          $marketRegimeService,
        private readonly ConfidenceScoreServiceInterface       $confidenceScoreService,
        private readonly AssetAnalysisScoreRepositoryInterface $scoreRepository,
        private readonly SetupMetricRepositoryInterface        $setupMetricRepository,
        private readonly TradeCallRepositoryInterface          $tradeCallRepository,
        private readonly TradeOutcomeRepositoryInterface       $tradeOutcomeRepository,
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

        $regime      = $this->marketRegimeService->current();
        $regimeRules = $this->marketRegimeService->rulesForRegime($regime->regime);

        $maxCalls = max(1, (int) ($regimeRules['max_calls'] ?? config('market.calls.max_calls_per_cycle', 8)));
        $minScore = (float) ($regimeRules['min_score'] ?? config('market.calls.min_score', 70));

        $scores  = $this->scoreRepository->findEligibleByDate($tradeDate->toDateString(), $minScore);
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

            $metric   = $this->setupMetricRepository->findBySetupCode((string) $score->setup_code);
            $filter   = $this->tradeCallFilterService->evaluate($score, $metric, $minScore);

            if (! $filter['eligible']) {
                continue;
            }

            $existing = $this->tradeCallRepository->findByAssetDateSetup(
                (int) $score->monitored_asset_id,
                $score->trade_date?->toDateString() ?? '',
                (string) $score->setup_code,
            );

            if ($existing !== null && $existing->status !== TradeCallStatus::DRAFT->value) {
                continue;
            }

            $finalRank      = $this->finalRankService->compute((float) $score->final_score, (float) ($metric?->expectancy ?? 0.0));
            $classification = $this->finalRankService->classify($metric, (float) $score->final_score);
            $confidence     = $this->confidenceScoreService->calculate(
                technicalScore: (float) $score->final_score,
                expectancy:     (float) ($metric?->expectancy ?? 0.0),
                marketRegime:   $regime->regime,
            );

            $match = [
                'monitored_asset_id' => $score->monitored_asset_id,
                'trade_date'         => $score->trade_date?->toDateString(),
                'setup_code'         => $score->setup_code,
            ];

            $data = [
                'setup_label'                    => $score->setup_label,
                'entry_price'                    => (float) $score->suggested_entry,
                'stop_price'                     => (float) $score->suggested_stop,
                'target_price'                   => (float) $score->suggested_target,
                'risk_percent'                   => (float) ($score->risk_percent ?? 0.0),
                'reward_percent'                 => (float) ($score->reward_percent ?? 0.0),
                'rr_ratio'                       => (float) ($score->rr_ratio ?? 0.0),
                'score'                          => (float) $score->final_score,
                'final_rank_score'               => $finalRank,
                'advanced_classification'        => $classification,
                'confidence_score'               => $confidence->score,
                'confidence_label'               => $confidence->label,
                'market_regime'                  => $regime->regime,
                'expectancy_snapshot'            => round((float) ($metric?->expectancy ?? 0.0), 4),
                'market_context_score_snapshot'  => round((float) $regime->contextScore, 4),
                'status'                         => TradeCallStatus::DRAFT->value,
                'generated_by_engine'            => true,
                'published_at'                   => null,
            ];

            $model     = $this->tradeCallRepository->upsertDraft($match, $data);
            $created[] = $this->toDto($model->load('monitoredAsset:id,ticker'), $metric);
        }

        return $created;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCalls(?string $status = null, int $limit = 100): array
    {
        $calls       = $this->tradeCallRepository->listFiltered($status, $limit);
        $setupCodes  = $calls->pluck('setup_code')->filter()->unique()->values()->all();
        $metricsMap  = $this->setupMetricRepository->findByCodes($setupCodes);

        return $calls
            ->map(function ($call) use ($metricsMap): array {
                $metric = $metricsMap->get((string) $call->setup_code);

                return [
                    ...$this->toDto($call, $metric)->toArray(),
                    'has_outcome' => $call->outcome !== null,
                ];
            })
            ->all();
    }

    public function getCall(int $id): TradeCallDTO
    {
        $call = $this->tradeCallRepository->findByIdWithAsset($id);

        if ($call === null) {
            throw (new ModelNotFoundException())->setModel(TradeCall::class, [$id]);
        }

        $metric = $this->setupMetricRepository->findBySetupCode((string) $call->setup_code);

        return $this->toDto($call, $metric);
    }

    public function approve(int $id, int $reviewerId, ?string $comments = null): TradeCallDTO
    {
        $call = $this->tradeCallRepository->findOrFailById($id);

        $this->tradeCallRepository->addReview(
            $call,
            $reviewerId,
            CallReviewDecision::APPROVE->value,
            $comments,
        );

        $refreshed = $this->tradeCallRepository->updateStatus($call, TradeCallStatus::APPROVED->value);
        $metric    = $this->setupMetricRepository->findBySetupCode((string) $refreshed->setup_code);

        return $this->toDto($refreshed, $metric);
    }

    public function reject(int $id, int $reviewerId, ?string $comments = null): TradeCallDTO
    {
        $call = $this->tradeCallRepository->findOrFailById($id);

        $this->tradeCallRepository->addReview(
            $call,
            $reviewerId,
            CallReviewDecision::REJECT->value,
            $comments,
        );

        $refreshed = $this->tradeCallRepository->updateStatus($call, TradeCallStatus::REJECTED->value);
        $metric    = $this->setupMetricRepository->findBySetupCode((string) $refreshed->setup_code);

        return $this->toDto($refreshed, $metric);
    }

    public function publish(int $id): TradeCallDTO
    {
        $call      = $this->tradeCallRepository->findOrFailById($id);
        $refreshed = $this->tradeCallRepository->updateStatus($call, TradeCallStatus::PUBLISHED->value, [
            'published_at' => now(),
        ]);
        $metric = $this->setupMetricRepository->findBySetupCode((string) $refreshed->setup_code);

        return $this->toDto($refreshed, $metric);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOutcomes(int $limit = 100): array
    {
        return $this->tradeOutcomeRepository
            ->listRecentWithRelations($limit)
            ->map(static fn ($item): array => [
                'id'          => $item->id,
                'symbol'      => $item->monitoredAsset?->ticker,
                'setup_code'  => $item->setup_code,
                'entry_price' => (float) $item->entry_price,
                'stop_price'  => (float) $item->stop_price,
                'target_price'=> (float) $item->target_price,
                'exit_price'  => (float) $item->exit_price,
                'result'      => $item->result,
                'pnl_percent' => (float) $item->pnl_percent,
                'duration_days' => (int) $item->duration_days,
                'created_at'  => $item->created_at?->toIso8601String(),
                'trade_call'  => [
                    'id'         => $item->trade_call_id,
                    'status'     => $item->tradeCall?->status,
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

        $latest = $this->scoreRepository->latestTradeDate();

        return $latest !== null ? CarbonImmutable::parse($latest) : null;
    }

    private function toDto(TradeCall $call, ?SetupMetric $metric): TradeCallDTO
    {
        return new TradeCallDTO(
            id:                     (int) $call->id,
            symbol:                 (string) ($call->monitoredAsset?->ticker ?? ''),
            tradeDate:              CarbonImmutable::parse((string) $call->trade_date?->toDateString()),
            setupCode:              (string) $call->setup_code,
            setupLabel:             (string) $call->setup_label,
            entryPrice:             (float) $call->entry_price,
            stopPrice:              (float) $call->stop_price,
            targetPrice:            (float) $call->target_price,
            riskPercent:            (float) $call->risk_percent,
            rewardPercent:          (float) $call->reward_percent,
            rrRatio:                (float) $call->rr_ratio,
            score:                  (float) $call->score,
            finalRankScore:         (float) $call->final_rank_score,
            advancedClassification: $call->advanced_classification,
            confidenceScore:        $call->confidence_score !== null ? (float) $call->confidence_score : null,
            confidenceLabel:        $call->confidence_label,
            marketRegime:           $call->market_regime,
            status:                 (string) $call->status,
            generatedByEngine:      (bool) $call->generated_by_engine,
            publishedAt:            $call->published_at !== null ? CarbonImmutable::parse($call->published_at) : null,
            expectancy:             $metric?->expectancy,
            winrate:                $metric?->winrate,
            edge:                   $metric?->edge,
        );
    }
}
