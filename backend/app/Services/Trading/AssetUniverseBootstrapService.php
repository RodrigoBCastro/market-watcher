<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\AssetUniverseBootstrapServiceInterface;
use App\Contracts\MarketUniverseServiceInterface;
use App\Enums\AssetType;
use App\Models\AssetMaster;
use App\Models\MonitoredAsset;

class AssetUniverseBootstrapService implements AssetUniverseBootstrapServiceInterface
{
    public function __construct(private readonly MarketUniverseServiceInterface $marketUniverseService)
    {
    }

    public function bootstrapDataUniverse(array $filters = [], ?int $changedByUserId = null): array
    {
        $assetTypes = $filters['asset_types'] ?? config('market.bootstrap.default_asset_types', [AssetType::STOCK->value]);
        if (! is_array($assetTypes) || $assetTypes === []) {
            $assetTypes = [AssetType::STOCK->value];
        }

        $limit = max(1, min((int) ($filters['limit'] ?? config('market.bootstrap.default_limit', 1000)), 5000));
        $priceMin = isset($filters['price_min']) ? (float) $filters['price_min'] : null;
        $marketCapMin = isset($filters['market_cap_min']) ? (float) $filters['market_cap_min'] : null;
        $volumeMin = isset($filters['volume_min']) ? (float) $filters['volume_min'] : null;
        $sectors = is_array($filters['sectors'] ?? null) ? array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $filters['sectors']))) : [];

        $query = AssetMaster::query()
            ->where('is_active', true)
            ->where('is_listed', true)
            ->whereIn('asset_type', $assetTypes)
            ->orderBy('symbol');

        if ($priceMin !== null) {
            $query->where('last_close', '>=', $priceMin);
        }

        if ($marketCapMin !== null) {
            $query->where('market_cap', '>=', $marketCapMin);
        }

        if ($volumeMin !== null) {
            $query->where('last_volume', '>=', $volumeMin);
        }

        if ($sectors !== []) {
            $query->whereIn('sector', $sectors);
        }

        $selected = $query->limit($limit)->get();

        $inserted = 0;
        $updated = 0;
        $promotedToDataUniverse = 0;
        $errors = 0;

        foreach ($selected as $assetMaster) {
            try {
                $model = MonitoredAsset::query()->firstOrNew([
                    'ticker' => strtoupper($assetMaster->symbol),
                ]);

                $isNew = ! $model->exists;
                $wasInDataUniverse = (bool) $model->collect_data;

                $currentMetadata = is_array($model->metadata) ? $model->metadata : [];

                $model->fill([
                    'asset_master_id' => (int) $assetMaster->id,
                    'name' => $assetMaster->name,
                    'sector' => $assetMaster->sector,
                    'is_active' => true,
                    'monitoring_enabled' => true,
                    'collect_data' => true,
                    'metadata' => array_merge($currentMetadata, [
                        'asset_master' => [
                            'source' => $assetMaster->source,
                            'asset_type' => $assetMaster->asset_type,
                            'last_sync' => now()->toIso8601String(),
                        ],
                    ]),
                ]);

                if ($isNew) {
                    $model->save();
                    $inserted++;
                } elseif ($model->isDirty()) {
                    $model->save();
                    $updated++;
                }

                $status = $this->marketUniverseService->updateMembership(
                    assetId: (int) $model->id,
                    universeType: 'data_universe',
                    isActive: true,
                    manualReason: 'Bootstrap automático a partir de asset_master.',
                    changedByUserId: $changedByUserId,
                );

                if (! $wasInDataUniverse && (bool) ($status['watchlists']['full_market_watchlist'] ?? false)) {
                    $promotedToDataUniverse++;
                }
            } catch (\Throwable) {
                $errors++;
            }
        }

        return [
            'selected' => $selected->count(),
            'inserted' => $inserted,
            'updated' => $updated,
            'promoted_to_data_universe' => $promotedToDataUniverse,
            'errors' => $errors,
            'filters' => [
                'asset_types' => $assetTypes,
                'price_min' => $priceMin,
                'market_cap_min' => $marketCapMin,
                'volume_min' => $volumeMin,
                'sectors' => $sectors,
                'limit' => $limit,
            ],
        ];
    }
}
