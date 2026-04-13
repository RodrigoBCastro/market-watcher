<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\MarketQuoteDTO;

interface MarketDataProviderInterface
{
    /**
     * @return array<int, MarketQuoteDTO>
     */
    public function getHistoricalQuotes(string $symbol, int $days): array;

    public function getCurrentQuote(string $symbol): MarketQuoteDTO;

    /**
     * @return array<int, MarketQuoteDTO>
     */
    public function getIndexQuote(string $symbol, int $days = 60): array;

    /**
     * @return array{symbol: string, value: float, source: string, trade_date: string}
     */
    public function getUsdBrlQuote(): array;

    /**
     * @return array{
     *   assets: array<int, array<string, mixed>>,
     *   indexes: array<int, array<string, mixed>>,
     *   raw: array<string, mixed>
     * }
     */
    public function getAssetMasterList(): array;
}
