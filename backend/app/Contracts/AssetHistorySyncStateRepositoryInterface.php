<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\AssetHistorySyncState;
use Illuminate\Support\Collection;

interface AssetHistorySyncStateRepositoryInterface
{
    /**
     * Loads sync states for the given asset IDs, keyed by monitored_asset_id.
     *
     * @param  array<int, int>  $assetIds
     * @return Collection<int, AssetHistorySyncState>
     */
    public function loadByAssetIds(array $assetIds): Collection;

    public function save(AssetHistorySyncState $state): void;
}
