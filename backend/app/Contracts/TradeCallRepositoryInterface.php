<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\TradeCall;
use Illuminate\Support\Collection;

interface TradeCallRepositoryInterface
{
    /**
     * Returns approved/published calls that have no outcome yet,
     * with monitoredAsset eager-loaded.
     *
     * @return Collection<int, TradeCall>
     */
    public function findOpenWithoutOutcome(): Collection;

    public function findById(int $id): ?TradeCall;

    /**
     * Returns the count of calls grouped by status as an associative array.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array;

    /**
     * Returns the top N calls ordered by confidence_score desc, final_rank_score desc,
     * with monitoredAsset:id,ticker eager-loaded.
     *
     * @return Collection<int, TradeCall>
     */
    public function listTopRanked(int $limit): Collection;

    /**
     * Returns calls with monitoredAsset eager-loaded for the given IDs.
     *
     * @param  int[]  $ids
     * @return Collection<int, TradeCall>
     */
    public function findByIds(array $ids): Collection;

    /** Find by primary key with monitoredAsset:id,ticker eager-loaded. */
    public function findByIdWithAsset(int $id): ?TradeCall;

    /** Find by primary key, throws ModelNotFoundException if absent. */
    public function findOrFailById(int $id): TradeCall;

    /**
     * Filtered list ordered desc by trade_date, final_rank_score,
     * with monitoredAsset:id,ticker and outcome eager-loaded.
     */
    public function listFiltered(?string $status, int $limit): Collection;

    /**
     * Find an existing call by the unique business key:
     * monitored_asset_id + trade_date + setup_code.
     */
    public function findByAssetDateSetup(int $assetId, string $tradeDate, string $setupCode): ?TradeCall;

    /**
     * Update-or-create a call by the unique business key, returning the loaded model.
     *
     * @param  array<string, mixed>  $match
     * @param  array<string, mixed>  $data
     */
    public function upsertDraft(array $match, array $data): TradeCall;

    /** Append a review record to a call via its reviews() relationship. */
    public function addReview(TradeCall $call, int $reviewerId, string $decision, ?string $comments): void;

    /**
     * Update a call's status (and any extra scalar fields) and return the refreshed model
     * with monitoredAsset:id,ticker eager-loaded.
     *
     * @param  array<string, mixed>  $extra
     */
    public function updateStatus(TradeCall $call, string $status, array $extra = []): TradeCall;
}
