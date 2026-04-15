<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AssetMasterRepositoryInterface;
use App\Enums\AssetType;
use App\Models\AssetMaster;
use Illuminate\Support\Collection;

class EloquentAssetMasterRepository implements AssetMasterRepositoryInterface
{
    public function findBySymbol(string $symbol): ?AssetMaster
    {
        /** @var AssetMaster|null $asset */
        $asset = AssetMaster::query()
            ->where('symbol', strtoupper($symbol))
            ->first();

        return $asset;
    }

    public function findBySymbolWithRelations(string $symbol): ?AssetMaster
    {
        /** @var AssetMaster|null $asset */
        $asset = AssetMaster::query()
            ->with(['monitoredAsset.universeMemberships'])
            ->where('symbol', strtoupper($symbol))
            ->first();

        return $asset;
    }

    public function findOrNewBySymbol(string $symbol): AssetMaster
    {
        /** @var AssetMaster $asset */
        $asset = AssetMaster::query()->firstOrNew(['symbol' => strtoupper($symbol)]);

        return $asset;
    }

    public function save(AssetMaster $asset): void
    {
        $asset->save();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function findEligibleForBootstrap(array $filters): Collection
    {
        $assetTypes = $filters['asset_types'] ?? config('market.bootstrap.default_asset_types', [AssetType::STOCK->value]);
        if (! is_array($assetTypes) || $assetTypes === []) {
            $assetTypes = [AssetType::STOCK->value];
        }

        $limit = max(1, min((int) ($filters['limit'] ?? config('market.bootstrap.default_limit', 1000)), 5000));

        $query = AssetMaster::query()
            ->where('is_listed', true)
            ->where('is_blacklisted_for_monitoring', false)
            ->whereIn('asset_type', $assetTypes)
            ->orderBy('symbol');

        if (isset($filters['price_min'])) {
            $query->where('last_close', '>=', (float) $filters['price_min']);
        }

        if (isset($filters['market_cap_min'])) {
            $query->where('market_cap', '>=', (float) $filters['market_cap_min']);
        }

        if (isset($filters['volume_min'])) {
            $query->where('last_volume', '>=', (float) $filters['volume_min']);
        }

        $sectors = is_array($filters['sectors'] ?? null)
            ? array_values(array_filter(array_map('trim', (array) $filters['sectors'])))
            : [];

        if ($sectors !== []) {
            $query->whereIn('sector', $sectors);
        }

        return $query->limit($limit)->get();
    }

    /**
     * @param  string[]  $seenSymbols
     * @return Collection<int, AssetMaster>
     */
    public function findMissingListed(string $source, array $seenSymbols): Collection
    {
        $query = AssetMaster::query()
            ->where('source', $source)
            ->where('is_listed', true);

        if ($seenSymbols !== []) {
            $query->whereNotIn('symbol', $seenSymbols);
        }

        return $query->get();
    }

    /**
     * @return array<string, int>
     */
    public function summaryCounts(): array
    {
        $typeCounts = AssetMaster::query()
            ->selectRaw('asset_type, COUNT(*) as total')
            ->groupBy('asset_type')
            ->pluck('total', 'asset_type')
            ->map(static fn ($v): int => (int) $v);

        return [
            'total_assets' => AssetMaster::query()->count(),
            'stock'        => (int) ($typeCounts['stock'] ?? 0),
            'fund'         => (int) ($typeCounts['fund'] ?? 0),
            'bdr'          => (int) ($typeCounts['bdr'] ?? 0),
            'index'        => (int) ($typeCounts['index'] ?? 0),
            'unknown'      => (int) ($typeCounts['unknown'] ?? 0),
            'listed'       => AssetMaster::query()->where('is_listed', true)->count(),
            'blacklisted'  => AssetMaster::query()->where('is_blacklisted_for_monitoring', true)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AssetMaster>
     */
    public function listFiltered(array $filters, int $limit): Collection
    {
        $query = AssetMaster::query()
            ->with(['monitoredAsset.universeMemberships']);

        if (($filters['type'] ?? null) !== null && $filters['type'] !== '') {
            $query->where('asset_type', (string) $filters['type']);
        }

        if (($filters['sector'] ?? null) !== null && $filters['sector'] !== '') {
            $query->where('sector', (string) $filters['sector']);
        }

        if (($filters['listed'] ?? null) !== null && $filters['listed'] !== '') {
            $query->where('is_listed', $this->toBool($filters['listed']));
        }

        if (($filters['blacklisted'] ?? null) !== null && $filters['blacklisted'] !== '') {
            $query->where('is_blacklisted_for_monitoring', $this->toBool($filters['blacklisted']));
        }

        if (($filters['universe'] ?? null) !== null && $filters['universe'] !== '') {
            $universe = (string) $filters['universe'];

            $query->whereHas('monitoredAsset', static function ($subQuery) use ($universe): void {
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
            $query->where(static function ($subQuery) use ($search): void {
                $subQuery->whereRaw('UPPER(symbol) like ?', [$search])
                    ->orWhereRaw('UPPER(name) like ?', [$search]);
            });
        }

        return $query->orderBy('symbol')->limit($limit)->get();
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
}
