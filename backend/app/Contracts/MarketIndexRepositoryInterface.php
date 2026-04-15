<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\MarketQuoteDTO;
use Illuminate\Support\Collection;

interface MarketIndexRepositoryInterface
{
    /**
     * Bulk-upserts index quotes for a given symbol.
     *
     * @param  array<int, MarketQuoteDTO>  $quotes
     */
    public function upsertBatch(string $symbol, array $quotes): int;

    /**
     * Returns the most recent $limit records for $symbol, ordered descending then re-sorted ascending.
     * Ideal for computing rolling indicators that need the latest N days.
     */
    public function findBySymbolDescending(string $symbol, int $limit): Collection;

    /**
     * Returns up to $limit records for $symbol with trade_date <= $upToDate, ordered ascending.
     */
    public function findBySymbolUpToDateAscending(string $symbol, string $upToDate, int $limit): Collection;
}
