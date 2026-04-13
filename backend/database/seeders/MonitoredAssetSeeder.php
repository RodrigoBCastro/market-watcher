<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AssetMaster;
use App\Models\MonitoredAsset;
use Illuminate\Database\Seeder;

class MonitoredAssetSeeder extends Seeder
{
    public function run(): void
    {
        $assets = AssetMaster::query()
            ->where('is_active', true)
            ->where('is_listed', true)
            ->where('asset_type', 'stock')
            ->orderBy('symbol')
            ->limit(30)
            ->get();

        foreach ($assets as $asset) {
            MonitoredAsset::query()->updateOrCreate([
                'ticker' => $asset->symbol,
            ], [
                'asset_master_id' => (int) $asset->id,
                'name' => $asset->name,
                'sector' => $asset->sector,
                'is_active' => true,
                'monitoring_enabled' => true,
                'collect_data' => true,
                'eligible_for_analysis' => false,
                'eligible_for_calls' => false,
                'eligible_for_execution' => false,
                'universe_type' => 'data_universe',
                'in_ibov' => false,
                'in_index_small_caps' => null,
                'metadata' => [
                    'seeded' => true,
                    'seeded_from' => 'asset_master',
                    'asset_type' => $asset->asset_type,
                    'source' => $asset->source,
                ],
            ]);
        }
    }
}
