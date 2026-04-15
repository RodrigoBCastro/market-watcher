<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AssetQuoteRepositoryInterface;
use App\DTOs\MarketQuoteDTO;
use App\DTOs\QuoteImportResultDTO;
use App\Models\AssetQuote;
use Illuminate\Support\Collection;

class EloquentAssetQuoteRepository implements AssetQuoteRepositoryInterface
{
    private const CHUNK_SIZE = 500;

    /**
     * @param  array<int, MarketQuoteDTO>  $quotes
     */
    public function upsertBatch(int $assetId, array $quotes): QuoteImportResultDTO
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

    public function findByAssetAscending(int $assetId, int $limit): Collection
    {
        return AssetQuote::query()
            ->where('monitored_asset_id', $assetId)
            ->orderBy('trade_date')
            ->limit($limit)
            ->get(['trade_date', 'open', 'high', 'low', 'close', 'volume']);
    }

    public function findByAssetDescending(int $assetId, int $limit): Collection
    {
        return AssetQuote::query()
            ->where('monitored_asset_id', $assetId)
            ->orderByDesc('trade_date')
            ->limit($limit)
            ->get(['trade_date', 'open', 'high', 'low', 'close', 'adjusted_close', 'volume', 'source']);
    }

    public function findByAssetAfterDate(int $assetId, string $afterDate, int $limit): Collection
    {
        return AssetQuote::query()
            ->where('monitored_asset_id', $assetId)
            ->whereDate('trade_date', '>', $afterDate)
            ->orderBy('trade_date')
            ->limit($limit)
            ->get(['trade_date', 'high', 'low', 'close']);
    }

    public function latestCloseByAsset(int $assetId): ?float
    {
        $value = AssetQuote::query()
            ->where('monitored_asset_id', $assetId)
            ->orderByDesc('trade_date')
            ->value('close');

        return $value !== null ? (float) $value : null;
    }
}
