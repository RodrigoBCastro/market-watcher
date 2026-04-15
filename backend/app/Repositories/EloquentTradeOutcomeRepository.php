<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\TradeOutcomeRepositoryInterface;
use App\Models\TradeOutcome;
use Illuminate\Support\Collection;

class EloquentTradeOutcomeRepository implements TradeOutcomeRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertForCall(int $tradeCallId, array $data): void
    {
        TradeOutcome::query()->updateOrCreate(
            ['trade_call_id' => $tradeCallId],
            $data,
        );
    }

    /**
     * @return Collection<int, TradeOutcome>
     */
    public function allWithTradeCall(): Collection
    {
        return TradeOutcome::query()->with('tradeCall')->get();
    }

    /**
     * @return Collection<int, TradeOutcome>
     */
    public function findAllChronological(): Collection
    {
        return TradeOutcome::query()
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @return Collection<int, TradeOutcome>
     */
    public function listRecentWithRelations(int $limit): Collection
    {
        return TradeOutcome::query()
            ->with(['monitoredAsset:id,ticker', 'tradeCall:id,status,trade_date'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
