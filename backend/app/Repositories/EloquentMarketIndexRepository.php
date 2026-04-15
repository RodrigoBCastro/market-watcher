<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\MarketIndexRepositoryInterface;
use App\DTOs\MarketQuoteDTO;
use App\Models\MarketIndex;
use Illuminate\Support\Collection;

class EloquentMarketIndexRepository implements MarketIndexRepositoryInterface
{
    private const CHUNK_SIZE = 500;

    /**
     * @param  array<int, MarketQuoteDTO>  $quotes
     */
    public function upsertBatch(string $symbol, array $quotes): int
    {
        if ($quotes === []) {
            return 0;
        }

        $now = now()->toDateTimeString();
        $rows = [];

        foreach ($quotes as $quote) {
            $rows[] = [
                'symbol'     => $symbol,
                'trade_date' => $quote->tradeDate->toDateString(),
                'open'       => $quote->open,
                'high'       => $quote->high,
                'low'        => $quote->low,
                'close'      => $quote->close,
                'volume'     => $quote->volume,
                'source'     => $quote->source,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            MarketIndex::query()->upsert(
                $chunk,
                ['symbol', 'trade_date'],
                ['open', 'high', 'low', 'close', 'volume', 'source', 'updated_at'],
            );
        }

        return count($quotes);
    }

    public function findBySymbolDescending(string $symbol, int $limit): Collection
    {
        return MarketIndex::query()
            ->where('symbol', $symbol)
            ->orderByDesc('trade_date')
            ->limit($limit)
            ->get(['trade_date', 'close'])
            ->sortBy('trade_date')
            ->values();
    }

    public function findBySymbolUpToDateAscending(string $symbol, string $upToDate, int $limit): Collection
    {
        return MarketIndex::query()
            ->where('symbol', $symbol)
            ->where('trade_date', '<=', $upToDate)
            ->orderByDesc('trade_date')
            ->limit($limit)
            ->get(['trade_date', 'close'])
            ->sortBy('trade_date')
            ->values();
    }
}
