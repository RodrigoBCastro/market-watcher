<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\AssetMaster;
use Illuminate\Support\Collection;

interface AssetMasterRepositoryInterface
{
    public function findBySymbol(string $symbol): ?AssetMaster;

    /** Find by symbol with monitoredAsset + universeMemberships eager-loaded. */
    public function findBySymbolWithRelations(string $symbol): ?AssetMaster;

    /** First-or-new by symbol (unsaved if new). */
    public function findOrNewBySymbol(string $symbol): AssetMaster;

    public function save(AssetMaster $asset): void;

    /**
     * Returns all AssetMaster records that match the given bootstrap filters.
     * Filters: asset_types, limit, price_min, market_cap_min, volume_min, sectors.
     *
     * @param  array<string, mixed>  $filters
     */
    public function findEligibleForBootstrap(array $filters): Collection;

    /**
     * Assets from $source that are currently listed but NOT in $seenSymbols.
     * Used for missing-sync detection.
     *
     * @param  string[]  $seenSymbols
     * @return Collection<int, AssetMaster>
     */
    public function findMissingListed(string $source, array $seenSymbols): Collection;

    /**
     * Aggregate count summary: total + by asset_type + listed + active.
     *
     * @return array<string, int>
     */
    public function summaryCounts(): array;

    /**
     * Filtered listing with monitoredAsset + universeMemberships eager-loaded.
     * Supports keys: type, sector, active, listed, universe, search.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AssetMaster>
     */
    public function listFiltered(array $filters, int $limit): Collection;
}
