<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Contracts\AssetMasterRegistryServiceInterface;
use App\Contracts\MarketDataProviderInterface;
use App\Models\AssetMaster;
use App\Models\MarketIndexMaster;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use RuntimeException;
use Throwable;

class AssetMasterRegistryService implements AssetMasterRegistryServiceInterface
{
    public function __construct(private readonly MarketDataProviderInterface $marketDataProvider)
    {
    }

    public function list(array $filters = []): array
    {
        $limit = max(1, min((int) ($filters['limit'] ?? 200), 1000));

        $query = AssetMaster::query()
            ->with(['monitoredAsset.universeMemberships']);

        if (($filters['type'] ?? null) !== null && $filters['type'] !== '') {
            $query->where('asset_type', (string) $filters['type']);
        }

        if (($filters['sector'] ?? null) !== null && $filters['sector'] !== '') {
            $query->where('sector', (string) $filters['sector']);
        }

        if (($filters['active'] ?? null) !== null && $filters['active'] !== '') {
            $query->where('is_active', $this->toBool($filters['active']));
        }

        if (($filters['listed'] ?? null) !== null && $filters['listed'] !== '') {
            $query->where('is_listed', $this->toBool($filters['listed']));
        }

        if (($filters['universe'] ?? null) !== null && $filters['universe'] !== '') {
            $universe = (string) $filters['universe'];
            $query->whereHas('monitoredAsset', function ($subQuery) use ($universe): void {
                if ($universe === 'data_universe') {
                    $subQuery->where('collect_data', true);

                    return;
                }

                if ($universe === 'eligible_universe') {
                    $subQuery->where('eligible_for_analysis', true);

                    return;
                }

                if ($universe === 'trading_universe') {
                    $subQuery->where('eligible_for_calls', true);
                }
            });
        }

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $search = '%'.strtoupper((string) $filters['search']).'%';
            $query->where(function ($subQuery) use ($search): void {
                $subQuery->whereRaw('UPPER(symbol) like ?', [$search])
                    ->orWhereRaw('UPPER(name) like ?', [$search]);
            });
        }

        $items = $query
            ->orderBy('symbol')
            ->limit($limit)
            ->get()
            ->map(fn (AssetMaster $asset): array => $this->toArray($asset))
            ->all();

        $summary = [
            'total_assets' => AssetMaster::query()->count(),
            'stock' => AssetMaster::query()->where('asset_type', 'stock')->count(),
            'fund' => AssetMaster::query()->where('asset_type', 'fund')->count(),
            'bdr' => AssetMaster::query()->where('asset_type', 'bdr')->count(),
            'index' => AssetMaster::query()->where('asset_type', 'index')->count(),
            'unknown' => AssetMaster::query()->where('asset_type', 'unknown')->count(),
            'listed' => AssetMaster::query()->where('is_listed', true)->count(),
            'active' => AssetMaster::query()->where('is_active', true)->count(),
        ];

        return [
            'summary' => $summary,
            'items' => $items,
        ];
    }

    public function getBySymbol(string $symbol): array
    {
        $normalized = strtoupper($symbol);

        $asset = AssetMaster::query()
            ->with(['monitoredAsset.universeMemberships'])
            ->where('symbol', $normalized)
            ->first();

        if ($asset === null) {
            throw (new ModelNotFoundException())->setModel(AssetMaster::class, [$normalized]);
        }

        return $this->toArray($asset);
    }

    public function synchronizeFromProvider(): array
    {
        $payload = $this->marketDataProvider->getAssetMasterList();
        $assets = Arr::get($payload, 'assets', []);
        $indexes = Arr::get($payload, 'indexes', []);

        if (! is_array($assets)) {
            $assets = [];
        }

        if (! is_array($indexes)) {
            $indexes = [];
        }

        $received = 0;
        $inserted = 0;
        $updated = 0;
        $ignored = 0;
        $inactivated = 0;
        $errorsBySymbol = [];

        $seenSymbols = [];
        $now = now();

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

                $model = AssetMaster::query()->firstOrNew(['symbol' => $symbol]);
                $isNew = ! $model->exists;

                $model->fill([
                    'name' => (string) ($item['name'] ?? $symbol),
                    'asset_type' => (string) ($item['asset_type'] ?? 'unknown'),
                    'sector' => $item['sector'] ?? null,
                    'logo_url' => $item['logo_url'] ?? null,
                    'last_close' => $item['last_close'] ?? null,
                    'last_change_percent' => $item['last_change_percent'] ?? null,
                    'last_volume' => $item['last_volume'] ?? null,
                    'market_cap' => $item['market_cap'] ?? null,
                    'source' => (string) ($item['source'] ?? 'brapi'),
                    'source_payload' => $item['source_payload'] ?? null,
                    'is_listed' => true,
                    'is_active' => true,
                    'missing_sync_count' => 0,
                    'last_seen_at' => $now,
                    'delisted_at' => null,
                    'delisting_reason' => null,
                ]);

                if ($isNew) {
                    $model->first_seen_at = $now;
                }

                if ($isNew) {
                    $model->save();
                    $inserted++;
                } elseif ($model->isDirty()) {
                    $model->save();
                    $updated++;
                } else {
                    $ignored++;
                }
            } catch (Throwable $exception) {
                $errorsBySymbol[$symbol] = $exception->getMessage();
            }
        }

        if ($assets === []) {
            throw new RuntimeException('Listagem do cadastro mestre retornou vazia; sincronização interrompida para evitar inativação indevida.');
        }

        $seenSymbols = array_values(array_unique($seenSymbols));
        if ($seenSymbols === []) {
            throw new RuntimeException('Nenhum símbolo válido foi processado na listagem do cadastro mestre.');
        }

        $delistThreshold = (int) config('market.asset_master.delist_after_missing_syncs', 3);

        $missingQuery = AssetMaster::query()
            ->where('source', 'brapi')
            ->where('is_listed', true)
            ->whereNotIn('symbol', $seenSymbols);

        $missingQuery->get()->each(function (AssetMaster $asset) use ($delistThreshold, &$inactivated, &$errorsBySymbol, $now): void {
            try {
                $nextMissing = ((int) $asset->missing_sync_count) + 1;
                $shouldDelist = $nextMissing >= max(1, $delistThreshold);
                $wasListed = (bool) $asset->is_listed;

                $asset->update([
                    'missing_sync_count' => $nextMissing,
                    'is_listed' => $shouldDelist ? false : (bool) $asset->is_listed,
                    'is_active' => $shouldDelist ? false : (bool) $asset->is_active,
                    'delisted_at' => $shouldDelist ? ($asset->delisted_at ?? $now) : $asset->delisted_at,
                    'delisting_reason' => $shouldDelist ? 'Não retornado pela fonte em sincronizações consecutivas.' : $asset->delisting_reason,
                ]);

                if ($shouldDelist && $wasListed) {
                    $inactivated++;
                }
            } catch (Throwable $exception) {
                $errorsBySymbol[$asset->symbol] = $exception->getMessage();
            }
        });

        $indexesInserted = 0;
        $indexesUpdated = 0;
        $indexesSeen = [];

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

                $index = MarketIndexMaster::query()->firstOrNew(['symbol' => $symbol]);
                $isNew = ! $index->exists;

                $index->fill([
                    'name' => (string) ($item['name'] ?? $symbol),
                    'source' => (string) ($item['source'] ?? 'brapi'),
                    'source_payload' => $item['source_payload'] ?? null,
                    'is_active' => true,
                    'last_seen_at' => $now,
                ]);

                if ($isNew) {
                    $index->first_seen_at = $now;
                    $index->save();
                    $indexesInserted++;
                } elseif ($index->isDirty()) {
                    $index->save();
                    $indexesUpdated++;
                }
            } catch (Throwable $exception) {
                $errorsBySymbol["index:{$symbol}"] = $exception->getMessage();
            }
        }

        $indexesSeen = array_values(array_unique($indexesSeen));
        $inactiveIndexQuery = MarketIndexMaster::query()->where('source', 'brapi');
        if ($indexesSeen !== []) {
            $inactiveIndexQuery->whereNotIn('symbol', $indexesSeen);
        }
        $indexesInactivated = (int) (clone $inactiveIndexQuery)->where('is_active', true)->count();
        $inactiveIndexQuery->where('is_active', true)->update(['is_active' => false]);

        return [
            'received' => $received,
            'inserted' => $inserted,
            'updated' => $updated,
            'ignored' => $ignored,
            'inactivated' => $inactivated,
            'errors_count' => count($errorsBySymbol),
            'errors_by_symbol' => $errorsBySymbol,
            'indexes' => [
                'inserted' => $indexesInserted,
                'updated' => $indexesUpdated,
                'inactivated' => $indexesInactivated,
            ],
        ];
    }

    public function listIndexes(array $filters = []): array
    {
        $limit = max(1, min((int) ($filters['limit'] ?? 300), 1000));
        $query = MarketIndexMaster::query();

        if (($filters['active'] ?? null) !== null && $filters['active'] !== '') {
            $query->where('is_active', $this->toBool($filters['active']));
        }

        $items = $query
            ->orderBy('symbol')
            ->limit($limit)
            ->get()
            ->map(static fn (MarketIndexMaster $index): array => [
                'id' => (int) $index->id,
                'symbol' => $index->symbol,
                'name' => $index->name,
                'source' => $index->source,
                'is_active' => (bool) $index->is_active,
                'first_seen_at' => $index->first_seen_at?->toIso8601String(),
                'last_seen_at' => $index->last_seen_at?->toIso8601String(),
            ])
            ->all();

        return [
            'items' => $items,
            'summary' => [
                'total' => MarketIndexMaster::query()->count(),
                'active' => MarketIndexMaster::query()->where('is_active', true)->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(AssetMaster $asset): array
    {
        $monitored = $asset->monitoredAsset;
        $memberships = $monitored?->universeMemberships?->keyBy('universe_type');

        return [
            'id' => (int) $asset->id,
            'symbol' => $asset->symbol,
            'name' => $asset->name,
            'asset_type' => $asset->asset_type,
            'sector' => $asset->sector,
            'logo_url' => $asset->logo_url,
            'last_close' => $asset->last_close !== null ? (float) $asset->last_close : null,
            'last_change_percent' => $asset->last_change_percent !== null ? (float) $asset->last_change_percent : null,
            'last_volume' => $asset->last_volume !== null ? (int) $asset->last_volume : null,
            'market_cap' => $asset->market_cap !== null ? (float) $asset->market_cap : null,
            'source' => $asset->source,
            'is_listed' => (bool) $asset->is_listed,
            'is_active' => (bool) $asset->is_active,
            'missing_sync_count' => (int) $asset->missing_sync_count,
            'first_seen_at' => $asset->first_seen_at?->toIso8601String(),
            'last_seen_at' => $asset->last_seen_at?->toIso8601String(),
            'delisted_at' => $asset->delisted_at?->toIso8601String(),
            'delisting_reason' => $asset->delisting_reason,
            'monitored_asset' => $monitored !== null ? [
                'id' => (int) $monitored->id,
                'ticker' => $monitored->ticker,
                'universe_type' => $monitored->universe_type,
                'collect_data' => (bool) $monitored->collect_data,
                'eligible_for_analysis' => (bool) $monitored->eligible_for_analysis,
                'eligible_for_calls' => (bool) $monitored->eligible_for_calls,
                'eligible_for_execution' => (bool) $monitored->eligible_for_execution,
                'memberships' => [
                    'data_universe' => (bool) ($memberships?->get('data_universe')?->is_active ?? false),
                    'eligible_universe' => (bool) ($memberships?->get('eligible_universe')?->is_active ?? false),
                    'trading_universe' => (bool) ($memberships?->get('trading_universe')?->is_active ?? false),
                ],
            ] : null,
        ];
    }

    /**
     * @param  mixed  $value
     */
    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var((string) $value, FILTER_VALIDATE_BOOL);
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
            $asset = AssetMaster::query()->where('symbol', $symbol)->first();
            if ($asset === null) {
                return;
            }

            $wasListed = (bool) $asset->is_listed;
            $delistThreshold = (int) config('market.asset_master.delist_after_missing_syncs', 3);

            $asset->fill([
                'is_listed' => false,
                'is_active' => false,
                'missing_sync_count' => max((int) $asset->missing_sync_count, max(1, $delistThreshold)),
                'delisted_at' => $asset->delisted_at ?? $now,
                'delisting_reason' => 'Ativo fracionário excluído do cadastro mestre.',
            ]);

            if ($asset->isDirty()) {
                $asset->save();
            }

            if ($wasListed) {
                $inactivated++;
            }
        } catch (Throwable $exception) {
            $errorsBySymbol[$symbol] = $exception->getMessage();
        }
    }
}
