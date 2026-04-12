<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HgBrasilProvider
{
    private string $baseUrl;

    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.hg_brasil.base_url', 'https://api.hgbrasil.com'), '/');
        $this->apiKey = config('services.hg_brasil.key') ? (string) config('services.hg_brasil.key') : null;
    }

    /**
     * @return array{symbol: string, value: float, source: string, trade_date: string, raw_payload: array<string, mixed>}
     */
    public function getUsdBrlQuote(): array
    {
        try {
            $response = Http::timeout((int) config('services.hg_brasil.timeout', 10))
                ->retry((int) config('services.hg_brasil.retries', 2), 500)
                ->acceptJson()
                ->get("{$this->baseUrl}/finance", [
                    'format' => 'json-cors',
                    'key' => $this->apiKey,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Falha de conexão com HG Brasil', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException("HG Brasil retornou erro {$response->status()}");
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];

        $usd = Arr::get($payload, 'results.currencies.USD.buy');

        if (! is_numeric($usd)) {
            throw new RuntimeException('HG Brasil não retornou USD/BRL válido');
        }

        return [
            'symbol' => 'USDBRL',
            'value' => (float) $usd,
            'source' => 'hg_brasil',
            'trade_date' => CarbonImmutable::now()->toDateString(),
            'raw_payload' => $payload,
        ];
    }
}
