<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\MarketQuoteDTO;
use App\DTOs\QuoteImportResultDTO;

interface QuoteImporterInterface
{
    /**
     * Persists a batch of quotes for the given asset using bulk upsert.
     * Does not fire Eloquent model events.
     *
     * @param  array<int, MarketQuoteDTO>  $quotes
     */
    public function import(int $assetId, array $quotes): QuoteImportResultDTO;
}
