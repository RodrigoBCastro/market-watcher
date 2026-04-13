<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\IndicatorCalculatorInterface;
use App\Models\MonitoredAsset;
use App\Models\TechnicalIndicator;
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

    public function handle(IndicatorCalculatorInterface $indicatorPipeline, SyncLogger $syncLogger): void
    {
        $run = $syncLogger->start('recalculate_indicators');

        $processed = 0;
        $failed = 0;

        $assets = MonitoredAsset::query()
            ->where('is_active', true)
            ->where('eligible_for_analysis', true)
            ->when($this->ticker !== null, static function ($query, string $ticker): void {
                $query->where('ticker', strtoupper($ticker));
            })
            ->with(['quotes' => static function ($query): void {
                $query->orderBy('trade_date');
            }])
            ->get();

        foreach ($assets as $asset) {
            try {
                $quotes = $asset->quotes->map(static fn ($quote): array => [
                    'trade_date' => $quote->trade_date->toDateString(),
                    'open' => (float) $quote->open,
                    'high' => (float) $quote->high,
                    'low' => (float) $quote->low,
                    'close' => (float) $quote->close,
                    'volume' => (int) $quote->volume,
                ])->all();

                if (count($quotes) < 20) {
                    $syncLogger->log($run, 'warning', "Histórico insuficiente para indicadores de {$asset->ticker}");
                    continue;
                }

                $indicatorRows = $indicatorPipeline->calculate($quotes);

                foreach ($indicatorRows as $row) {
                    TechnicalIndicator::query()->updateOrCreate([
                        'monitored_asset_id' => $asset->id,
                        'trade_date' => $row['trade_date'],
                    ], array_merge($row, [
                        'monitored_asset_id' => $asset->id,
                    ]));

                    $processed++;
                }

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
