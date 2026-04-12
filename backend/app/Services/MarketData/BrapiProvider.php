<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Contracts\MarketDataProviderInterface;
use App\DTOs\MarketQuoteDTO;
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
}
