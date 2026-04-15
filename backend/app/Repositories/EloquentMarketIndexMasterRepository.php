<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\MarketIndexMasterRepositoryInterface;
use App\Models\MarketIndexMaster;
use Illuminate\Support\Collection;

class EloquentMarketIndexMasterRepository implements MarketIndexMasterRepositoryInterface
{
    public function findOrNewBySymbol(string $symbol): MarketIndexMaster
    {
        /** @var MarketIndexMaster $index */
        $index = MarketIndexMaster::query()->firstOrNew(['symbol' => $symbol]);

        return $index;
    }

    public function save(MarketIndexMaster $index): void
    {
        $index->save();
    }

    /**
     * @param  string[]  $seenSymbols
     */
    public function deactivateNotIn(string $source, array $seenSymbols): int
    {
        $query = MarketIndexMaster::query()
            ->where('source', $source)
            ->where('is_active', true);

        if ($seenSymbols !== []) {
            $query->whereNotIn('symbol', $seenSymbols);
        }

        return $query->update(['is_active' => false]);
    }

    public function countActive(): int
    {
        return MarketIndexMaster::query()->where('is_active', true)->count();
    }

    public function countTotal(): int
    {
        return MarketIndexMaster::query()->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, MarketIndexMaster>
     */
    public function list(array $filters = [], int $limit = 300): Collection
    {
        $query = MarketIndexMaster::query();

        if (($filters['active'] ?? null) !== null && $filters['active'] !== '') {
            $active = is_bool($filters['active'])
                ? $filters['active']
                : filter_var((string) $filters['active'], FILTER_VALIDATE_BOOL);
            $query->where('is_active', $active);
        }

        return $query
            ->orderBy('symbol')
            ->limit(max(1, min($limit, 1000)))
            ->get();
    }
}
