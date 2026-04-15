<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Contracts\QuoteImporterInterface;
use App\DTOs\MarketQuoteDTO;
use App\DTOs\QuoteImportResultDTO;
use App\Models\AssetQuote;

class QuoteImporter implements QuoteImporterInterface
{
    private const CHUNK_SIZE = 500;

    /**
     * @param  array<int, MarketQuoteDTO>  $quotes
     */
    public function import(int $assetId, array $quotes): QuoteImportResultDTO
    {
        if ($quotes === []) {
            return new QuoteImportResultDTO(0, null, null);
        }

        $now = now()->toDateTimeString();
        $rows = [];

        foreach ($quotes as $quote) {
            $rows[] = [
                'monitored_asset_id' => $assetId,
                'trade_date'         => $quote->tradeDate->toDateString(),
                'open'               => $quote->open,
                'high'               => $quote->high,
                'low'                => $quote->low,
                'close'              => $quote->close,
                'adjusted_close'     => $quote->adjustedClose,
                'volume'             => $quote->volume,
                'source'             => $quote->source,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            AssetQuote::query()->upsert(
                $chunk,
                ['monitored_asset_id', 'trade_date'],
                ['open', 'high', 'low', 'close', 'adjusted_close', 'volume', 'source', 'updated_at'],
            );
        }

        $tradeDates = array_column($rows, 'trade_date');

        return new QuoteImportResultDTO(
            processed: count($quotes),
            earliestTradeDate: min($tradeDates),
            latestTradeDate: max($tradeDates),
        );
    }
}
