<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AssetHistorySyncStateRepositoryInterface;
use App\Models\AssetHistorySyncState;
use Illuminate\Support\Collection;

class EloquentAssetHistorySyncStateRepository implements AssetHistorySyncStateRepositoryInterface
{
    /**
     * @param  array<int, int>  $assetIds
     * @return Collection<int, AssetHistorySyncState>
     */
    public function loadByAssetIds(array $assetIds): Collection
    {
        if ($assetIds === []) {
            return collect();
        }

        return AssetHistorySyncState::query()
            ->whereIn('monitored_asset_id', $assetIds)
            ->get()
            ->keyBy('monitored_asset_id');
    }

    public function save(AssetHistorySyncState $state): void
    {
        $state->save();
    }
}
