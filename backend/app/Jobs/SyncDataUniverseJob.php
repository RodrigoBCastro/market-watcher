<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\MarketDataProviderInterface;
use App\Models\AssetQuote;
use App\Models\MonitoredAsset;
use App\Services\MarketData\SyncLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncDataUniverseJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public readonly ?string $ticker = null)
    {
    }

    public function handle(MarketDataProviderInterface $provider, SyncLogger $syncLogger): void
    {
        $run = $syncLogger->start($this->ticker !== null ? 'sync_data_universe_single' : 'sync_data_universe');

        $processed = 0;
        $failed = 0;

        $assets = MonitoredAsset::query()
            ->where('is_active', true)
            ->where('collect_data', true)
            ->when($this->ticker !== null, static function ($query, string $ticker): void {
                $query->where('ticker', strtoupper($ticker));
            })
            ->orderBy('ticker')
            ->get();

        foreach ($assets as $asset) {
            try {
                $quotes = $provider->getHistoricalQuotes($asset->ticker, (int) config('market.sync.asset_days', 90));

                foreach ($quotes as $quote) {
                    AssetQuote::query()->updateOrCreate([
                        'monitored_asset_id' => $asset->id,
                        'trade_date' => $quote->tradeDate->toDateString(),
                    ], [
                        'open' => $quote->open,
                        'high' => $quote->high,
                        'low' => $quote->low,
                        'close' => $quote->close,
                        'adjusted_close' => $quote->adjustedClose,
                        'volume' => $quote->volume,
                        'source' => $quote->source,
                    ]);
                    $processed++;
                }

                $syncLogger->log($run, 'info', "Data Universe sincronizado para {$asset->ticker}", [
                    'records' => count($quotes),
                ]);
            } catch (Throwable $exception) {
                $failed++;
                $syncLogger->log($run, 'error', "Falha no Data Universe para {$asset->ticker}", [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $status = $failed > 0 ? ($processed > 0 ? 'partial' : 'failed') : 'success';

        $syncLogger->finish(
            run: $run,
            status: $status,
            processed: $processed,
            failed: $failed,
            notes: $assets->isEmpty() ? 'Nenhum ativo ativo no Data Universe.' : null,
        );
    }
}

