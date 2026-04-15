<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\TradeCallRepositoryInterface;
use App\Enums\CallReviewDecision;
use App\Enums\TradeCallStatus;
use App\Models\TradeCall;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class EloquentTradeCallRepository implements TradeCallRepositoryInterface
{
    /**
     * @return Collection<int, TradeCall>
     */
    public function findOpenWithoutOutcome(): Collection
    {
        return TradeCall::query()
            ->with('monitoredAsset:id,ticker')
            ->whereIn('status', [
                TradeCallStatus::APPROVED->value,
                TradeCallStatus::PUBLISHED->value,
            ])
            ->doesntHave('outcome')
            ->orderBy('trade_date')
            ->get();
    }

    public function findById(int $id): ?TradeCall
    {
        /** @var TradeCall|null $call */
        $call = TradeCall::query()->find($id);

        return $call;
    }

    /**
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        return TradeCall::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(static fn ($value): int => (int) $value)
            ->all();
    }

    /**
     * @return Collection<int, TradeCall>
     */
    public function listTopRanked(int $limit): Collection
    {
        return TradeCall::query()
            ->with('monitoredAsset:id,ticker')
            ->orderByDesc('confidence_score')
            ->orderByDesc('final_rank_score')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  int[]  $ids
     * @return Collection<int, TradeCall>
     */
    public function findByIds(array $ids): Collection
    {
        return TradeCall::query()
            ->with('monitoredAsset:id,ticker,sector')
            ->whereIn('id', $ids)
            ->get();
    }

    public function findByIdWithAsset(int $id): ?TradeCall
    {
        return TradeCall::query()
            ->with('monitoredAsset:id,ticker')
            ->find($id);
    }

    public function findOrFailById(int $id): TradeCall
    {
        /** @var TradeCall $call */
        $call = TradeCall::query()->findOrFail($id);

        return $call;
    }

    /**
     * @return Collection<int, TradeCall>
     */
    public function listFiltered(?string $status, int $limit): Collection
    {
        return TradeCall::query()
            ->with(['monitoredAsset:id,ticker', 'outcome'])
            ->when($status !== null, static function ($query, string $s): void {
                $query->where('status', $s);
            })
            ->orderByDesc('trade_date')
            ->orderByDesc('final_rank_score')
            ->limit($limit)
            ->get();
    }

    public function findByAssetDateSetup(int $assetId, string $tradeDate, string $setupCode): ?TradeCall
    {
        return TradeCall::query()
            ->where('monitored_asset_id', $assetId)
            ->where('trade_date', $tradeDate)
            ->where('setup_code', $setupCode)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $match
     * @param  array<string, mixed>  $data
     */
    public function upsertDraft(array $match, array $data): TradeCall
    {
        /** @var TradeCall $call */
        $call = TradeCall::query()->updateOrCreate($match, $data);

        return $call;
    }

    public function addReview(TradeCall $call, int $reviewerId, string $decision, ?string $comments): void
    {
        $call->reviews()->create([
            'reviewer_id' => $reviewerId,
            'decision'    => $decision,
            'comments'    => $comments,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function updateStatus(TradeCall $call, string $status, array $extra = []): TradeCall
    {
        $call->update(array_merge(['status' => $status], $extra));

        return ($call->fresh(['monitoredAsset:id,ticker'])) ?? $call->load('monitoredAsset:id,ticker');
    }

    public function listTopByDate(string $date, int $limit): Collection
    {
        return TradeCall::query()
            ->with('monitoredAsset:id,ticker')
            ->whereDate('trade_date', $date)
            ->orderByDesc('final_rank_score')
            ->limit($limit)
            ->get();
    }
}
