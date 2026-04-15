<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\MarketIndexMaster;
use Illuminate\Support\Collection;

interface MarketIndexMasterRepositoryInterface
{
    public function findOrNewBySymbol(string $symbol): MarketIndexMaster;

    public function save(MarketIndexMaster $index): void;

    /**
     * @param  string[]  $seenSymbols
     */
    public function deactivateNotIn(string $source, array $seenSymbols): int;

    public function countActive(): int;

    public function countTotal(): int;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, MarketIndexMaster>
     */
    public function list(array $filters = [], int $limit = 300): Collection;
}
