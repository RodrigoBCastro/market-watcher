<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\TradeOutcome;
use Illuminate\Support\Collection;

interface TradeOutcomeRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertForCall(int $tradeCallId, array $data): void;

    /**
     * Returns all trade outcomes with their tradeCall relation eager-loaded.
     *
     * @return Collection<int, TradeOutcome>
     */
    public function allWithTradeCall(): Collection;

    /**
     * All outcomes ordered chronologically (created_at asc).
     * Used by SetupMetricsService to rebuild aggregations.
     */
    public function findAllChronological(): Collection;

    /**
     * Most recent $limit outcomes with monitoredAsset:id,ticker and
     * tradeCall:id,status,trade_date eager-loaded, descending created_at.
     */
    public function listRecentWithRelations(int $limit): Collection;
}
