<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\AssetMasterRepositoryInterface;
use App\Contracts\AssetUniverseBootstrapServiceInterface;
use App\Contracts\MarketUniverseServiceInterface;
use App\Contracts\MonitoredAssetRepositoryInterface;
use App\Enums\AssetType;
use App\Models\MonitoredAsset;

class AssetUniverseBootstrapService implements AssetUniverseBootstrapServiceInterface
{
    public function __construct(
        private readonly MarketUniverseServiceInterface   $marketUniverseService,
        private readonly AssetMasterRepositoryInterface   $assetMasterRepository,
        private readonly MonitoredAssetRepositoryInterface $monitoredAssetRepository,
    ) {
    }

    public function bootstrapDataUniverse(array $filters = [], ?int $changedByUserId = null): array
    {
        $assetTypes = $filters['asset_types'] ?? config('market.bootstrap.default_asset_types', [AssetType::STOCK->value]);
        if (! is_array($assetTypes) || $assetTypes === []) {
            $assetTypes = [AssetType::STOCK->value];
        }

        $filters['asset_types'] = $assetTypes;
        $filters['limit']       = max(1, min((int) ($filters['limit'] ?? config('market.bootstrap.default_limit', 1000)), 5000));

        $priceMin     = isset($filters['price_min'])      ? (float) $filters['price_min']      : null;
        $marketCapMin = isset($filters['market_cap_min']) ? (float) $filters['market_cap_min'] : null;
        $volumeMin    = isset($filters['volume_min'])     ? (float) $filters['volume_min']     : null;
        $sectors      = is_array($filters['sectors'] ?? null)
            ? array_values(array_filter(array_map(static fn ($s): string => trim((string) $s), $filters['sectors'])))
            : [];

        $selected = $this->assetMasterRepository->findEligibleForBootstrap($filters);

        $inserted               = 0;
        $updated                = 0;
        $promotedToDataUniverse = 0;
        $errors                 = 0;

        foreach ($selected as $assetMaster) {
            try {
                $existing = $this->monitoredAssetRepository->findByTicker($assetMaster->symbol);
                $model    = $existing ?? new MonitoredAsset(['ticker' => strtoupper($assetMaster->symbol)]);
                $isNew    = $existing === null;

                $wasInDataUniverse = (bool) $model->collect_data;
                $currentMetadata   = is_array($model->metadata) ? $model->metadata : [];

                $model->fill([
                    'asset_master_id'    => (int) $assetMaster->id,
                    'name'               => $assetMaster->name,
                    'sector'             => $assetMaster->sector,
                    'metadata'           => array_merge($currentMetadata, [
                        'asset_master' => [
                            'source'     => $assetMaster->source,
                            'asset_type' => $assetMaster->asset_type,
                            'last_sync'  => now()->toIso8601String(),
                        ],
                    ]),
                ]);

                if ($isNew) {
                    $this->monitoredAssetRepository->save($model);
                    $inserted++;
                } elseif ($model->isDirty()) {
                    $this->monitoredAssetRepository->save($model);
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
            'selected'                  => $selected->count(),
            'inserted'                  => $inserted,
            'updated'                   => $updated,
            'promoted_to_data_universe' => $promotedToDataUniverse,
            'errors'                    => $errors,
            'filters'                   => [
                'asset_types'    => $assetTypes,
                'price_min'      => $priceMin,
                'market_cap_min' => $marketCapMin,
                'volume_min'     => $volumeMin,
                'sectors'        => $sectors,
                'limit'          => $filters['limit'],
            ],
        ];
    }
}
