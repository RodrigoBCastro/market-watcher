<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\MacroSnapshotRepositoryInterface;
use App\Contracts\MarketDataProviderInterface;
use App\Contracts\MarketIndexRepositoryInterface;
use App\Services\MarketData\HgBrasilProvider;
use App\Services\MarketData\SyncLogger;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SyncMarketContextJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function handle(
        MarketDataProviderInterface $provider,
        HgBrasilProvider $hgBrasilProvider,
        MarketIndexRepositoryInterface $marketIndexRepository,
        MacroSnapshotRepositoryInterface $macroSnapshotRepository,
        SyncLogger $syncLogger,
    ): void {
        $run = $syncLogger->start('sync_market_context');

        $processed = 0;
        $failed    = 0;

        try {
            $ibovQuotes = $provider->getIndexQuote('^BVSP', 90);

            if ($ibovQuotes === []) {
                $ibovQuotes = $provider->getIndexQuote('IBOV', 90);
            }

            // Bulk upsert — replaces the per-row updateOrCreate loop.
            $processed += $marketIndexRepository->upsertBatch('IBOV', $ibovQuotes);

            $latestIbov = end($ibovQuotes);

            if ($latestIbov === false) {
                throw new \RuntimeException('Sem dados de IBOV retornados pelo provider');
            }

            $usdBrl = null;

            try {
                $usdBrl = $hgBrasilProvider->getUsdBrlQuote();
            } catch (Throwable) {
                $usdBrl = $provider->getUsdBrlQuote();
            }

            $snapshotDate = CarbonImmutable::parse((string) ($usdBrl['trade_date'] ?? $latestIbov->tradeDate->toDateString()));

            $macroSnapshotRepository->upsert($snapshotDate->toDateString(), [
                'usd_brl'      => (float) ($usdBrl['value'] ?? 0.0),
                'ibov_close'   => (float) $latestIbov->close,
                'market_bias'  => $this->resolveMarketBias($ibovQuotes, (float) ($usdBrl['value'] ?? 0.0)),
                'source'       => (string) ($usdBrl['source'] ?? 'brapi'),
                'raw_payload'  => [
                    'ibov_last_trade_date' => $latestIbov->tradeDate->toDateString(),
                    'usd_brl'              => $usdBrl,
                ],
            ]);

            $processed++;

            $syncLogger->log($run, 'info', 'Contexto macro sincronizado com sucesso');
        } catch (Throwable $exception) {
            $failed++;
            $syncLogger->log($run, 'error', 'Falha na sincronização de contexto macro', [
                'error' => $exception->getMessage(),
            ]);
        }

        $status = $failed > 0 ? ($processed > 0 ? 'partial' : 'failed') : 'success';

        $syncLogger->finish($run, $status, $processed, $failed);
    }

    /**
     * @param  array<int, \App\DTOs\MarketQuoteDTO>  $ibovQuotes
     */
    private function resolveMarketBias(array $ibovQuotes, float $usdBrl): string
    {
        if (count($ibovQuotes) < 25) {
            return 'neutro';
        }

        $closes = array_map(static fn ($quote): float => $quote->close, $ibovQuotes);
        $last   = $closes[count($closes) - 1];
        $sma21  = array_sum(array_slice($closes, -21)) / 21;
        $sma50  = array_sum(array_slice($closes, -50)) / 50;

        if ($last > $sma21 && $sma21 > $sma50 && $usdBrl < 5.45) {
            return 'favoravel';
        }

        if ($last > $sma50) {
            return 'cautelosamente_favoravel';
        }

        if ($last < $sma50 * 0.98 || $usdBrl > 5.80) {
            return 'fraco';
        }

        return 'neutro';
    }
}
