<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\AssetQuoteRepositoryInterface;
use App\Contracts\IndicatorCalculatorInterface;
use App\Contracts\MonitoredAssetRepositoryInterface;
use App\Contracts\TechnicalIndicatorRepositoryInterface;
use App\Services\MarketData\SyncLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RecalculateIndicatorsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 240;

    public function __construct(public readonly ?string $ticker = null)
    {
    }

    public function handle(
        IndicatorCalculatorInterface $indicatorPipeline,
        MonitoredAssetRepositoryInterface $monitoredAssetRepository,
        AssetQuoteRepositoryInterface $assetQuoteRepository,
        TechnicalIndicatorRepositoryInterface $indicatorRepository,
        SyncLogger $syncLogger,
    ): void {
        $run = $syncLogger->start('recalculate_indicators');

        $processed = 0;
        $failed    = 0;

        // cursor() iterates one asset at a time without loading all into memory.
        $assets = $monitoredAssetRepository->cursorForAnalysis($this->ticker);

        foreach ($assets as $asset) {
            try {
                // Fetch up to 600 candles — enough for SMA200 + 400 candles of valid history.
                $quotes = $assetQuoteRepository
                    ->findByAssetAscending((int) $asset->id, 600)
                    ->map(static fn ($quote): array => [
                        'trade_date' => $quote->trade_date->toDateString(),
                        'open'       => (float) $quote->open,
                        'high'       => (float) $quote->high,
                        'low'        => (float) $quote->low,
                        'close'      => (float) $quote->close,
                        'volume'     => (int) $quote->volume,
                    ])
                    ->all();

                if (count($quotes) < 20) {
                    $syncLogger->log($run, 'warning', "Histórico insuficiente para indicadores de {$asset->ticker}");
                    continue;
                }

                $indicatorRows = $indicatorPipeline->calculate($quotes);

                // Bulk upsert — replaces the per-row updateOrCreate loop.
                $count = $indicatorRepository->upsertBatch((int) $asset->id, $indicatorRows);
                $processed += $count;

                $syncLogger->log($run, 'info', "Indicadores recalculados para {$asset->ticker}", [
                    'rows' => count($indicatorRows),
                ]);
            } catch (Throwable $exception) {
                $failed++;
                $syncLogger->log($run, 'error', "Falha ao recalcular indicadores para {$asset->ticker}", [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $status = $failed > 0 ? ($processed > 0 ? 'partial' : 'failed') : 'success';

        $syncLogger->finish($run, $status, $processed, $failed);
    }
}
