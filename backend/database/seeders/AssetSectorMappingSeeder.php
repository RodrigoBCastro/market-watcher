<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AssetSectorMapping;
use App\Models\MonitoredAsset;
use Illuminate\Database\Seeder;

class AssetSectorMappingSeeder extends Seeder
{
    public function run(): void
    {
        MonitoredAsset::query()
            ->get(['id', 'sector'])
            ->each(static function (MonitoredAsset $asset): void {
                AssetSectorMapping::query()->updateOrCreate([
                    'monitored_asset_id' => $asset->id,
                ], [
                    'sector' => (string) ($asset->sector ?? 'Outros'),
                    'subsector' => null,
                    'segment' => null,
                ]);
            });
    }
}
