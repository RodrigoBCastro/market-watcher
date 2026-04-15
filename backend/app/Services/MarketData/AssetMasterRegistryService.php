<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Contracts\AssetMasterRegistryServiceInterface;
use App\Contracts\AssetMasterRepositoryInterface;
use App\Contracts\MarketDataProviderInterface;
use App\Contracts\MarketIndexMasterRepositoryInterface;
use App\Contracts\MarketUniverseServiceInterface;
use App\Models\AssetMaster;
use App\Models\MarketIndexMaster;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use RuntimeException;
use Throwable;

class AssetMasterRegistryService implements AssetMasterRegistryServiceInterface
{
    public function __construct(
        private readonly MarketDataProviderInterface $marketDataProvider,
        private readonly AssetMasterRepositoryInterface $assetMasterRepository,
        private readonly MarketIndexMasterRepositoryInterface $marketIndexMasterRepository,
        private readonly MarketUniverseServiceInterface $marketUniverseService,
    ) {
    }

    public function list(array $filters = []): array
    {
        $limit = max(1, min((int) ($filters['limit'] ?? 200), 1000));

        $items = $this->assetMasterRepository
            ->listFiltered($filters, $limit)
            ->map(fn (AssetMaster $asset): array => $this->toArray($asset))
            ->all();

        $summary = $this->assetMasterRepository->summaryCounts();

        return [
            'summary' => $summary,
            'items'   => $items,
        ];
    }

    public function getBySymbol(string $symbol): array
    {
        $asset = $this->assetMasterRepository->findBySymbolWithRelations(strtoupper($symbol));

        if ($asset === null) {
            throw (new ModelNotFoundException())->setModel(AssetMaster::class, [strtoupper($symbol)]);
        }

        return $this->toArray($asset);
    }

    public function synchronizeFromProvider(): array
    {
        $payload = $this->marketDataProvider->getAssetMasterList();
        $assets  = Arr::get($payload, 'assets', []);
        $indexes = Arr::get($payload, 'indexes', []);

        if (! is_array($assets)) {
            $assets = [];
        }

        if (! is_array($indexes)) {
            $indexes = [];
        }

        $received       = 0;
        $inserted       = 0;
        $updated        = 0;
        $ignored        = 0;
        $inactivated    = 0;
        $errorsBySymbol = [];

        $seenSymbols = [];
        $now         = now();

        foreach ($assets as $item) {
            if (! is_array($item)) {
                $ignored++;

                continue;
            }

            $symbol = strtoupper(trim((string) ($item['symbol'] ?? '')));

            if ($symbol === '') {
                $ignored++;

                continue;
            }

            if ((bool) config('market.asset_master.exclude_fractional_symbols', true) && $this->isFractionalSymbol($symbol)) {
                $ignored++;
                $this->deactivateFractionalSymbolIfExists($symbol, $now, $inactivated, $errorsBySymbol);

                continue;
            }

            try {
                $received++;
                $seenSymbols[] = $symbol;

                $model = $this->assetMasterRepository->findOrNewBySymbol($symbol);
                $isNew = ! $model->exists;

                $model->fill([
                    'name'                => (string) ($item['name'] ?? $symbol),
                    'asset_type'          => (string) ($item['asset_type'] ?? 'unknown'),
                    'sector'              => $item['sector'] ?? null,
                    'logo_url'            => $item['logo_url'] ?? null,
                    'last_close'          => $item['last_close'] ?? null,
                    'last_change_percent' => $item['last_change_percent'] ?? null,
                    'last_volume'         => $item['last_volume'] ?? null,
                    'market_cap'          => $item['market_cap'] ?? null,
                    'source'              => (string) ($item['source'] ?? 'brapi'),
                    'source_payload'      => $item['source_payload'] ?? null,
                    'is_listed'           => true,
                    'missing_sync_count'  => 0,
                    'last_seen_at'        => $now,
                    'delisted_at'         => null,
                    'delisting_reason'    => null,
                ]);

                if ($isNew) {
                    $model->first_seen_at = $now;
                }

                if ($isNew) {
                    $this->assetMasterRepository->save($model);
                    $inserted++;
                } elseif ($model->isDirty()) {
                    $this->assetMasterRepository->save($model);
                    $updated++;
                } else {
                    $ignored++;
                }
            } catch (Throwable $exception) {
                $errorsBySymbol[$symbol] = $exception->getMessage();
            }
        }

        if ($assets === []) {
            throw new RuntimeException('Listagem do cadastro mestre retornou vazia; sincronizacao interrompida para evitar inativacao indevida.');
        }

        $seenSymbols = array_values(array_unique($seenSymbols));

        if ($seenSymbols === []) {
            throw new RuntimeException('Nenhum simbolo valido foi processado na listagem do cadastro mestre.');
        }

        $delistThreshold = (int) config('market.asset_master.delist_after_missing_syncs', 3);

        $this->assetMasterRepository
            ->findMissingListed('brapi', $seenSymbols)
            ->each(function (AssetMaster $asset) use ($delistThreshold, &$inactivated, &$errorsBySymbol, $now): void {
                try {
                    $nextMissing  = ((int) $asset->missing_sync_count) + 1;
                    $shouldDelist = $nextMissing >= max(1, $delistThreshold);
                    $wasListed    = (bool) $asset->is_listed;

                    $asset->update([
                        'missing_sync_count' => $nextMissing,
                        'is_listed'          => $shouldDelist ? false : (bool) $asset->is_listed,
                        'delisted_at'        => $shouldDelist ? ($asset->delisted_at ?? $now) : $asset->delisted_at,
                        'delisting_reason'   => $shouldDelist ? 'Nao retornado pela fonte em sincronizacoes consecutivas.' : $asset->delisting_reason,
                    ]);

                    if ($shouldDelist && $wasListed) {
                        $inactivated++;
                    }
                } catch (Throwable $exception) {
                    $errorsBySymbol[$asset->symbol] = $exception->getMessage();
                }
            });

        $indexesInserted   = 0;
        $indexesUpdated    = 0;
        $indexesSeen       = [];

        foreach ($indexes as $item) {
            if (! is_array($item)) {
                continue;
            }

            $symbol = strtoupper(trim((string) ($item['symbol'] ?? '')));

            if ($symbol === '') {
                continue;
            }

            try {
                $indexesSeen[] = $symbol;

                $index = $this->marketIndexMasterRepository->findOrNewBySymbol($symbol);
                $isNew = ! $index->exists;

                $index->fill([
                    'name'           => (string) ($item['name'] ?? $symbol),
                    'source'         => (string) ($item['source'] ?? 'brapi'),
                    'source_payload' => $item['source_payload'] ?? null,
                    'is_active'      => true,
                    'last_seen_at'   => $now,
                ]);

                if ($isNew) {
                    $index->first_seen_at = $now;
                    $this->marketIndexMasterRepository->save($index);
                    $indexesInserted++;
                } elseif ($index->isDirty()) {
                    $this->marketIndexMasterRepository->save($index);
                    $indexesUpdated++;
                }
            } catch (Throwable $exception) {
                $errorsBySymbol["index:{$symbol}"] = $exception->getMessage();
            }
        }

        $indexesSeen       = array_values(array_unique($indexesSeen));
        $indexesInactivated = $this->marketIndexMasterRepository->deactivateNotIn('brapi', $indexesSeen);

        return [
            'received'      => $received,
            'inserted'      => $inserted,
            'updated'       => $updated,
            'ignored'       => $ignored,
            'inactivated'   => $inactivated,
            'errors_count'  => count($errorsBySymbol),
            'errors_by_symbol' => $errorsBySymbol,
            'indexes'       => [
                'inserted'   => $indexesInserted,
                'updated'    => $indexesUpdated,
                'inactivated'=> $indexesInactivated,
            ],
        ];
    }

    public function listIndexes(array $filters = []): array
    {
        $limit = max(1, min((int) ($filters['limit'] ?? 300), 1000));

        $items = $this->marketIndexMasterRepository
            ->list($filters, $limit)
            ->map(static fn (MarketIndexMaster $index): array => [
                'id'          => (int) $index->id,
                'symbol'      => $index->symbol,
                'name'        => $index->name,
                'source'      => $index->source,
                'is_active'   => (bool) $index->is_active,
                'first_seen_at' => $index->first_seen_at?->toIso8601String(),
                'last_seen_at'  => $index->last_seen_at?->toIso8601String(),
            ])
            ->all();

        return [
            'items'   => $items,
            'summary' => [
                'total'  => $this->marketIndexMasterRepository->countTotal(),
                'active' => $this->marketIndexMasterRepository->countActive(),
            ],
        ];
    }

    public function setMonitoringBlacklist(
        string $symbol,
        bool $isBlacklisted,
        ?string $reason = null,
        ?int $changedByUserId = null,
    ): array {
        $symbol = strtoupper(trim($symbol));
        $reason = $reason !== null ? trim($reason) : null;
        if ($reason === '') {
            $reason = null;
        }

        $asset = $this->assetMasterRepository->findBySymbolWithRelations($symbol);

        if ($asset === null) {
            throw (new ModelNotFoundException())->setModel(AssetMaster::class, [$symbol]);
        }

        $asset->fill([
            'is_blacklisted_for_monitoring' => $isBlacklisted,
            'blacklisted_at' => $isBlacklisted ? now() : null,
            'blacklist_reason' => $isBlacklisted
                ? ($reason ?? $asset->blacklist_reason ?? 'Bloqueado manualmente para monitoramento.')
                : null,
        ]);

        if ($asset->isDirty()) {
            $this->assetMasterRepository->save($asset);
        }

        if ($isBlacklisted && $asset->monitoredAsset !== null) {
            $this->marketUniverseService->updateMembership(
                assetId: (int) $asset->monitoredAsset->id,
                universeType: 'data_universe',
                isActive: false,
                manualReason: $asset->blacklist_reason ?? 'Ativo bloqueado para monitoramento no Asset Master.',
                changedByUserId: $changedByUserId,
            );
        }

        $fresh = $asset->fresh(['monitoredAsset.universeMemberships']);

        if ($fresh === null) {
            throw (new ModelNotFoundException())->setModel(AssetMaster::class, [$symbol]);
        }

        return $this->toArray($fresh);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(AssetMaster $asset): array
    {
        $monitored   = $asset->monitoredAsset;
        $memberships = $monitored?->universeMemberships?->keyBy('universe_type');

        return [
            'id'                  => (int) $asset->id,
            'symbol'              => $asset->symbol,
            'name'                => $asset->name,
            'asset_type'          => $asset->asset_type,
            'sector'              => $asset->sector,
            'logo_url'            => $asset->logo_url,
            'last_close'          => $asset->last_close !== null ? (float) $asset->last_close : null,
            'last_change_percent' => $asset->last_change_percent !== null ? (float) $asset->last_change_percent : null,
            'last_volume'         => $asset->last_volume !== null ? (int) $asset->last_volume : null,
            'market_cap'          => $asset->market_cap !== null ? (float) $asset->market_cap : null,
            'source'              => $asset->source,
            'is_listed'           => (bool) $asset->is_listed,
            'is_blacklisted_for_monitoring' => (bool) $asset->is_blacklisted_for_monitoring,
            'blacklisted_at'      => $asset->blacklisted_at?->toIso8601String(),
            'blacklist_reason'    => $asset->blacklist_reason,
            'missing_sync_count'  => (int) $asset->missing_sync_count,
            'first_seen_at'       => $asset->first_seen_at?->toIso8601String(),
            'last_seen_at'        => $asset->last_seen_at?->toIso8601String(),
            'delisted_at'         => $asset->delisted_at?->toIso8601String(),
            'delisting_reason'    => $asset->delisting_reason,
            'monitored_asset'     => $monitored !== null ? [
                'id'                    => (int) $monitored->id,
                'ticker'                => $monitored->ticker,
                'universe_type'         => $monitored->universe_type,
                'collect_data'          => (bool) $monitored->collect_data,
                'eligible_for_analysis' => (bool) $monitored->eligible_for_analysis,
                'eligible_for_calls'    => (bool) $monitored->eligible_for_calls,
                'eligible_for_execution'=> (bool) $monitored->eligible_for_execution,
                'memberships'           => [
                    'data_universe'     => (bool) ($memberships?->get('data_universe')?->is_active ?? false),
                    'eligible_universe' => (bool) ($memberships?->get('eligible_universe')?->is_active ?? false),
                    'trading_universe'  => (bool) ($memberships?->get('trading_universe')?->is_active ?? false),
                ],
            ] : null,
        ];
    }

    private function isFractionalSymbol(string $symbol): bool
    {
        $normalized = strtoupper(trim($symbol));

        if (! str_ends_with($normalized, 'F')) {
            return false;
        }

        return preg_match('/\dF$/', $normalized) === 1;
    }

    /**
     * @param  array<string, string>  $errorsBySymbol
     */
    private function deactivateFractionalSymbolIfExists(
        string $symbol,
        CarbonInterface $now,
        int &$inactivated,
        array &$errorsBySymbol,
    ): void {
        try {
            $asset = $this->assetMasterRepository->findBySymbol($symbol);

            if ($asset === null) {
                return;
            }

            $wasListed       = (bool) $asset->is_listed;
            $delistThreshold = (int) config('market.asset_master.delist_after_missing_syncs', 3);

            $asset->fill([
                'is_listed'          => false,
                'missing_sync_count' => max((int) $asset->missing_sync_count, max(1, $delistThreshold)),
                'delisted_at'        => $asset->delisted_at ?? $now,
                'delisting_reason'   => 'Ativo fracionario excluido do cadastro mestre.',
            ]);

            if ($asset->isDirty()) {
                $this->assetMasterRepository->save($asset);
            }

            if ($wasListed) {
                $inactivated++;
            }
        } catch (Throwable $exception) {
            $errorsBySymbol[$symbol] = $exception->getMessage();
        }
    }
}
