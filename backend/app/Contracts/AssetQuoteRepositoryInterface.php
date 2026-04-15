<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\MarketQuoteDTO;
use App\DTOs\QuoteImportResultDTO;
use Illuminate\Support\Collection;

interface AssetQuoteRepositoryInterface
{
    /**
     * Bulk-upserts a batch of quotes for a given asset.
     *
     * @param  array<int, MarketQuoteDTO>  $quotes
     */
    public function upsertBatch(int $assetId, array $quotes): QuoteImportResultDTO;

    public function findByAssetAscending(int $assetId, int $limit): Collection;

    public function findByAssetDescending(int $assetId, int $limit): Collection;

    public function findByAssetAfterDate(int $assetId, string $afterDate, int $limit): Collection;

    /**
     * Returns the most recent close price for the given asset, or null if no quotes exist.
     */
    public function latestCloseByAsset(int $assetId): ?float;
}
