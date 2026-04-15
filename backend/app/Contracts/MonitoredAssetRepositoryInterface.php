<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\MonitoredAsset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

interface MonitoredAssetRepositoryInterface
{
    /** Cursor for RecalculateIndicatorsJob (eligible_for_analysis). */
    public function cursorForAnalysis(?string $ticker = null): LazyCollection;

    /** Cursor for RecalculateScoresJob (eligible_for_calls). */
    public function cursorForCalls(?string $ticker = null): LazyCollection;

    /** Collection for SyncDataUniverseJob (collect_data). */
    public function findActiveForDataCollection(?string $ticker = null): Collection;

    /** All active assets, ordered by ticker. */
    public function findAllActive(): Collection;

    /**
     * Assets pending universe review (null or stale last_universe_review_at),
     * ordered by most recently updated.
     *
     * @return Collection<int, MonitoredAsset>
     */
    public function findStaleUniverseReview(int $staleAfterDays, int $limit): Collection;

    public function findByTicker(string $ticker): ?MonitoredAsset;

    public function findOrFailByTicker(string $ticker): MonitoredAsset;

    public function findById(int $id): ?MonitoredAsset;

    public function findOrFailById(int $id): MonitoredAsset;

    /**
     * Full listing with all display relationships eager-loaded.
     * Used by AssetController::index().
     */
    public function findAllForListing(): Collection;

    /**
     * Paginated listing with sorting support for watchlist table.
     *
     * @param  string  $sortBy  Allowed: ticker,name,sector,universe_type,is_active,monitoring_enabled,latest_score
     * @param  string  $sortDirection  asc|desc
     */
    public function paginateForListing(int $page, int $perPage, string $sortBy, string $sortDirection): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MonitoredAsset;

    public function save(MonitoredAsset $asset): void;

    public function delete(MonitoredAsset $asset): void;

    public function findIdByTicker(string $ticker): ?int;

    /**
     * Returns a map of TICKER => id for all given tickers.
     *
     * @param  array<int, string>  $tickers
     * @return array<string, int>
     */
    public function findIdsByTickers(array $tickers): array;

    /**
     * Aggregate average metrics across all assets with collect_data = true.
     * Returns avg of: liquidity_score, operability_score,
     * avg_daily_financial_volume_20, volatility_20.
     *
     * @return array<string, float|null>
     */
    public function averageMetricsForDataCollection(): array;
}
