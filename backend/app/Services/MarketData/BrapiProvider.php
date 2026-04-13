<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Contracts\MarketDataProviderInterface;
use App\DTOs\MarketQuoteDTO;
use App\Enums\AssetType;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BrapiProvider implements MarketDataProviderInterface
{
    private string $baseUrl;

    private ?string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.brapi.base_url', 'https://brapi.dev/api'), '/');
        $this->token = config('services.brapi.token') ? (string) config('services.brapi.token') : null;
    }

    public function getHistoricalQuotes(string $symbol, int $days): array
    {
        $payload = $this->quoteRequest($symbol, [
            'range' => $this->mapDaysToRange($days),
            'interval' => '1d',
            'fundamental' => 'false',
            'dividends' => 'false',
        ]);

        $items = Arr::get($payload, 'results.0.historicalDataPrice', []);

        $quotes = [];

        foreach ($items as $item) {
            if (! isset($item['date'], $item['open'], $item['high'], $item['low'], $item['close'])) {
                continue;
            }

            $quotes[] = new MarketQuoteDTO(
                symbol: strtoupper($symbol),
                tradeDate: CarbonImmutable::createFromTimestampUTC((int) $item['date'])->setTimezone(config('app.timezone')),
                open: (float) $item['open'],
                high: (float) $item['high'],
                low: (float) $item['low'],
                close: (float) $item['close'],
                adjustedClose: isset($item['adjustedClose']) ? (float) $item['adjustedClose'] : null,
                volume: (int) ($item['volume'] ?? 0),
                source: 'brapi',
            );
        }

        usort($quotes, fn (MarketQuoteDTO $a, MarketQuoteDTO $b): int => $a->tradeDate->getTimestamp() <=> $b->tradeDate->getTimestamp());

        return $quotes;
    }

    public function getCurrentQuote(string $symbol): MarketQuoteDTO
    {
        $payload = $this->quoteRequest($symbol, [
            'range' => '5d',
            'interval' => '1d',
            'fundamental' => 'false',
            'dividends' => 'false',
        ]);

        $result = Arr::get($payload, 'results.0');

        if (! is_array($result)) {
            throw new RuntimeException("Brapi retornou payload inválido para {$symbol}");
        }

        $tradeDate = isset($result['regularMarketTime'])
            ? CarbonImmutable::createFromTimestampUTC((int) $result['regularMarketTime'])->setTimezone(config('app.timezone'))
            : CarbonImmutable::now();

        $close = isset($result['regularMarketPrice']) ? (float) $result['regularMarketPrice'] : (float) ($result['close'] ?? 0);

        return new MarketQuoteDTO(
            symbol: strtoupper($symbol),
            tradeDate: $tradeDate,
            open: (float) ($result['regularMarketOpen'] ?? $close),
            high: (float) ($result['regularMarketDayHigh'] ?? $close),
            low: (float) ($result['regularMarketDayLow'] ?? $close),
            close: $close,
            adjustedClose: isset($result['regularMarketPreviousClose']) ? (float) $result['regularMarketPreviousClose'] : null,
            volume: (int) ($result['regularMarketVolume'] ?? 0),
            source: 'brapi',
        );
    }

    public function getIndexQuote(string $symbol, int $days = 60): array
    {
        return $this->getHistoricalQuotes($symbol, $days);
    }

    public function getUsdBrlQuote(): array
    {
        try {
            $quote = $this->getCurrentQuote('USDBRL=X');
        } catch (\RuntimeException) {
            $quote = $this->getCurrentQuote('USDBRL');
        }

        return [
            'symbol' => $quote->symbol,
            'value' => $quote->close,
            'source' => $quote->source,
            'trade_date' => $quote->tradeDate->toDateString(),
        ];
    }

    public function getAssetMasterList(): array
    {
        $payload = $this->listRequest();

        $stocks = Arr::get($payload, 'stocks', []);
        if (! is_array($stocks) || $stocks === []) {
            $stocks = Arr::get($payload, 'results', []);
        }

        $indexes = Arr::get($payload, 'indexes', []);

        $assetItems = [];
        $indexItems = [];

        foreach ($stocks as $item) {
            if (! is_array($item)) {
                continue;
            }

            $symbol = strtoupper((string) ($item['stock'] ?? $item['symbol'] ?? $item['ticker'] ?? ''));
            if ($symbol === '') {
                continue;
            }

            $normalizedType = AssetType::normalize((string) ($item['type'] ?? $item['quoteType'] ?? ''), $symbol);

            if ($normalizedType === AssetType::INDEX) {
                $indexItems[] = $this->normalizeIndex($item, $symbol);
                continue;
            }

            $assetItems[] = [
                'symbol' => $symbol,
                'name' => (string) ($item['name'] ?? $item['shortName'] ?? $symbol),
                'asset_type' => $normalizedType->value,
                'sector' => isset($item['sector']) ? (string) $item['sector'] : null,
                'logo_url' => isset($item['logo']) ? (string) $item['logo'] : null,
                'last_close' => isset($item['close']) ? (float) $item['close'] : (isset($item['regularMarketPrice']) ? (float) $item['regularMarketPrice'] : null),
                'last_change_percent' => isset($item['change']) ? (float) $item['change'] : (isset($item['changePercent']) ? (float) $item['changePercent'] : null),
                'last_volume' => isset($item['volume']) ? (int) $item['volume'] : (isset($item['regularMarketVolume']) ? (int) $item['regularMarketVolume'] : null),
                'market_cap' => isset($item['market_cap']) ? (float) $item['market_cap'] : (isset($item['marketCap']) ? (float) $item['marketCap'] : null),
                'source' => 'brapi',
                'source_payload' => $item,
            ];
        }

        foreach ($indexes as $item) {
            if (! is_array($item)) {
                continue;
            }

            $symbol = strtoupper((string) ($item['stock'] ?? $item['symbol'] ?? $item['ticker'] ?? ''));
            if ($symbol === '') {
                continue;
            }

            $indexItems[] = $this->normalizeIndex($item, $symbol);
        }

        return [
            'assets' => $assetItems,
            'indexes' => $indexItems,
            'raw' => $payload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function quoteRequest(string $symbol, array $query = []): array
    {
        try {
            $request = Http::timeout((int) config('services.brapi.timeout', 10))
                ->retry((int) config('services.brapi.retries', 2), 500)
                ->acceptJson();

            if ($this->token !== null) {
                $request = $request->withToken($this->token);
            }

            $response = $request->get("{$this->baseUrl}/quote/{$symbol}", $query);
        } catch (ConnectionException $exception) {
            throw new RuntimeException("Falha de conexão com brapi para {$symbol}", previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException("Brapi retornou erro {$response->status()} para {$symbol}");
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];
        if ((bool) ($json['error'] ?? false)) {
            $message = (string) ($json['message'] ?? "Brapi retornou erro de domínio para {$symbol}");
            throw new RuntimeException($message);
        }

        return $json;
    }

    private function mapDaysToRange(int $days): string
    {
        return match (true) {
            $days <= 1 => '1d',
            $days <= 5 => '5d',
            $days <= 7 => '7d',
            $days <= 30 => '1mo',
            $days <= 90 => '3mo',
            $days <= 180 => '6mo',
            $days <= 365 => '1y',
            $days <= 730 => '2y',
            $days <= 1825 => '5y',
            default => 'max',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function listRequest(array $query = []): array
    {
        try {
            $request = Http::timeout((int) config('services.brapi.timeout', 10))
                ->retry((int) config('services.brapi.retries', 2), 500)
                ->acceptJson();

            if ($this->token !== null) {
                $request = $request->withToken($this->token);
            }

            $response = $request->get("{$this->baseUrl}/quote/list", $query);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Falha de conexão com brapi na listagem de ativos.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException("Brapi retornou erro {$response->status()} na listagem de ativos.");
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];
        if ((bool) ($json['error'] ?? false)) {
            $message = (string) ($json['message'] ?? 'Brapi retornou erro de domínio na listagem de ativos.');
            throw new RuntimeException($message);
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeIndex(array $item, string $symbol): array
    {
        return [
            'symbol' => $symbol,
            'name' => (string) ($item['name'] ?? $item['shortName'] ?? $symbol),
            'source' => 'brapi',
            'source_payload' => $item,
        ];
    }
}
