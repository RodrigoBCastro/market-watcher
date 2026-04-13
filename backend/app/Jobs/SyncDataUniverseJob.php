<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\MarketDataProviderInterface;
use App\DTOs\MarketQuoteDTO;
use App\Models\AssetHistorySyncState;
use App\Models\AssetQuote;
use App\Models\MonitoredAsset;
use App\Models\SyncRun;
use App\Services\MarketData\SyncLogger;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
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
        try {
            $days = $this->resolveDays();
            $startYearsBack = $this->resolveStartYearsBack();
            $fromDate = $this->resolveFromDate($startYearsBack);
            $batchSize = $this->resolveBatchSize();
        } catch (Throwable $exception) {
            $syncLogger->log($run, 'error', 'Parâmetros inválidos para sincronização do Data Universe.', [
                'error' => $exception->getMessage(),
            ]);
            $syncLogger->finish($run, 'failed', 0, 1, 'Parâmetros de sync inválidos.');

            return;
        }

        $assets = MonitoredAsset::query()
            ->where('is_active', true)
            ->where('collect_data', true)
            ->when($this->ticker !== null, static function ($query, string $ticker): void {
                $query->where('ticker', strtoupper($ticker));
            })
            ->orderBy('ticker')
            ->get();

        $fromMode = $fromDate === null ? 'disabled' : 'years_back';

        $syncLogger->log($run, 'info', 'Parâmetros de sincronização do Data Universe.', [
            'asset_days' => $days,
            'from_date' => $fromDate,
            'from_mode' => $fromMode,
            'start_years_back' => $startYearsBack,
            'batch_size' => $batchSize,
            'assets_total' => $assets->count(),
        ]);

        foreach ($assets->chunk($batchSize) as $chunk) {
            [$chunkProcessed, $chunkFailed] = $this->syncChunk(
                provider: $provider,
                syncLogger: $syncLogger,
                run: $run,
                assetsChunk: $chunk,
                days: $days,
                fromDate: $fromDate,
            );

            $processed += $chunkProcessed;
            $failed += $chunkFailed;
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

    /**
     * @param  Collection<int, MonitoredAsset>  $assetsChunk
     * @return array{0:int,1:int}
     */
    private function syncChunk(
        MarketDataProviderInterface $provider,
        SyncLogger $syncLogger,
        SyncRun $run,
        Collection $assetsChunk,
        int $days,
        ?string $fromDate,
    ): array {
        $processed = 0;
        $failed = 0;
        $assetIds = $assetsChunk->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        $stateByAssetId = AssetHistorySyncState::query()
            ->whereIn('monitored_asset_id', $assetIds)
            ->get()
            ->keyBy('monitored_asset_id');

        $bootstrapTickers = [];
        $rollingTickers = [];
        $modeByAssetId = [];

        foreach ($assetsChunk as $asset) {
            $ticker = strtoupper($asset->ticker);
            $state = $stateByAssetId->get((int) $asset->id);
            $mode = $this->resolveMode($state, $fromDate);
            $modeByAssetId[(int) $asset->id] = $mode;
            if ($mode === 'bootstrap') {
                $bootstrapTickers[] = $ticker;

                continue;
            }

            $rollingTickers[] = $ticker;
        }

        $bootstrapTickerSet = array_fill_keys($bootstrapTickers, true);
        $bootstrapBatchQuotes = $this->loadBatchQuotes(
            provider: $provider,
            syncLogger: $syncLogger,
            run: $run,
            tickers: $bootstrapTickers,
            days: $days,
            fromDate: $fromDate,
            mode: 'bootstrap',
        );
        $rollingBatchQuotes = $this->loadBatchQuotes(
            provider: $provider,
            syncLogger: $syncLogger,
            run: $run,
            tickers: $rollingTickers,
            days: $days,
            fromDate: null,
            mode: 'rolling',
        );

        foreach ($assetsChunk as $asset) {
            try {
                $ticker = strtoupper($asset->ticker);
                $mode = $modeByAssetId[(int) $asset->id] ?? 'rolling';
                $state = $stateByAssetId->get((int) $asset->id);
                $useFromDate = $mode === 'bootstrap' && isset($bootstrapTickerSet[$ticker]) && $fromDate !== null;
                $quotes = $useFromDate
                    ? ($bootstrapBatchQuotes[$ticker] ?? null)
                    : ($rollingBatchQuotes[$ticker] ?? null);

                if ($quotes === null) {
                    $quotes = $provider->getHistoricalQuotes($ticker, $days, $useFromDate ? $fromDate : null);
                }

                $persisted = $this->persistQuotes((int) $asset->id, $quotes);
                $processed += (int) $persisted['processed'];

                $updatedState = $this->updateSyncStateAfterSuccess(
                    monitoredAssetId: (int) $asset->id,
                    state: $state,
                    mode: $mode,
                    fromDate: $fromDate,
                    earliestFromRun: $persisted['earliest_trade_date'],
                    latestFromRun: $persisted['latest_trade_date'],
                );
                $stateByAssetId->put((int) $asset->id, $updatedState);

                $syncLogger->log($run, 'info', "Data Universe sincronizado para {$asset->ticker}", [
                    'records' => count($quotes),
                    'mode' => $mode,
                    'state_status' => $updatedState->status,
                ]);
            } catch (Throwable $exception) {
                $failed++;
                $state = $stateByAssetId->get((int) $asset->id);
                $updatedState = $this->updateSyncStateAfterFailure(
                    monitoredAssetId: (int) $asset->id,
                    state: $state,
                    errorMessage: $exception->getMessage(),
                    fromDate: $fromDate,
                );
                $stateByAssetId->put((int) $asset->id, $updatedState);

                $syncLogger->log($run, 'error', "Falha no Data Universe para {$asset->ticker}", [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return [$processed, $failed];
    }

    /**
     * @param  array<int, string>  $tickers
     * @return array<string, array<int, \App\DTOs\MarketQuoteDTO>>
     */
    private function loadBatchQuotes(
        MarketDataProviderInterface $provider,
        SyncLogger $syncLogger,
        SyncRun $run,
        array $tickers,
        int $days,
        ?string $fromDate,
        string $mode,
    ): array {
        if ($tickers === []) {
            return [];
        }

        try {
            return $provider->getHistoricalQuotesBatch($tickers, $days, $fromDate);
        } catch (Throwable $exception) {
            $syncLogger->log(
                run: $run,
                level: 'warning',
                message: "Falha no lote {$mode} da sincronização. Fallback para sync individual.",
                context: [
                    'tickers' => $tickers,
                    'days' => $days,
                    'from_date' => $fromDate,
                    'error' => $exception->getMessage(),
                ],
            );

            return [];
        }
    }

    /**
     * @param  array<int, MarketQuoteDTO>  $quotes
     * @return array{processed:int, earliest_trade_date:?string, latest_trade_date:?string}
     */
    private function persistQuotes(int $assetId, array $quotes): array
    {
        $processed = 0;
        $earliestTradeDate = null;
        $latestTradeDate = null;

        foreach ($quotes as $quote) {
            $tradeDate = $quote->tradeDate->toDateString();

            AssetQuote::query()->updateOrCreate([
                'monitored_asset_id' => $assetId,
                'trade_date' => $tradeDate,
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

            $earliestTradeDate = $this->minDate($earliestTradeDate, $tradeDate);
            $latestTradeDate = $this->maxDate($latestTradeDate, $tradeDate);
        }

        return [
            'processed' => $processed,
            'earliest_trade_date' => $earliestTradeDate,
            'latest_trade_date' => $latestTradeDate,
        ];
    }

    private function resolveDays(): int
    {
        return max(1, (int) config('market.sync.asset_days', 90));
    }

    private function resolveFromDate(int $startYearsBack): ?string
    {
        if ($startYearsBack <= 0) {
            return null;
        }

        return CarbonImmutable::now()->subYears($startYearsBack)->toDateString();
    }

    private function resolveStartYearsBack(): int
    {
        return max(0, (int) config('market.sync.start_years_back', 10));
    }

    private function resolveBatchSize(): int
    {
        return max(1, min((int) config('market.sync.batch_size', 20), 20));
    }

    private function resolveMode(?AssetHistorySyncState $state, ?string $fromDate): string
    {
        if ($fromDate === null) {
            return 'rolling';
        }

        if ($state === null) {
            return 'bootstrap';
        }

        if ($state->status !== 'bootstrap_complete') {
            return 'bootstrap';
        }

        return 'rolling';
    }

    private function minDate(?string $left, ?string $right): ?string
    {
        if ($left === null || $left === '') {
            return $right;
        }

        if ($right === null || $right === '') {
            return $left;
        }

        return strcmp($left, $right) <= 0 ? $left : $right;
    }

    private function maxDate(?string $left, ?string $right): ?string
    {
        if ($left === null || $left === '') {
            return $right;
        }

        if ($right === null || $right === '') {
            return $left;
        }

        return strcmp($left, $right) >= 0 ? $left : $right;
    }

    private function updateSyncStateAfterSuccess(
        int $monitoredAssetId,
        ?AssetHistorySyncState $state,
        string $mode,
        ?string $fromDate,
        ?string $earliestFromRun,
        ?string $latestFromRun,
    ): AssetHistorySyncState {
        $now = now();
        $model = $state ?? new AssetHistorySyncState([
            'monitored_asset_id' => $monitoredAssetId,
            'status' => $mode === 'bootstrap' ? 'pending_bootstrap' : 'bootstrap_complete',
            'bootstrap_from_date' => $fromDate,
        ]);

        $existingEarliest = $model->earliest_quote_date_found?->toDateString();
        $existingLatest = $model->latest_quote_date_synced?->toDateString();
        $candidateEarliest = $this->minDate($existingEarliest, $earliestFromRun);
        $candidateLatest = $this->maxDate($existingLatest, $latestFromRun);

        $model->latest_quote_date_synced = $candidateLatest;
        $model->last_mode_used = $mode;
        $model->last_error = null;

        if ($mode === 'bootstrap') {
            $model->bootstrap_from_date = $model->bootstrap_from_date?->toDateString() ?? $fromDate;
            $model->earliest_quote_date_found = $candidateEarliest;
            $model->last_bootstrap_at = $now;
            $model->status = 'bootstrap_complete';
            $model->bootstrap_completed_at = $model->bootstrap_completed_at ?? $now;
        } else {
            $model->last_rolling_at = $now;
            $model->status = 'bootstrap_complete';
            if ($model->bootstrap_completed_at === null) {
                $model->bootstrap_completed_at = $now;
            }
        }

        $model->save();

        return $model;
    }

    private function updateSyncStateAfterFailure(
        int $monitoredAssetId,
        ?AssetHistorySyncState $state,
        string $errorMessage,
        ?string $fromDate,
    ): AssetHistorySyncState {
        $model = $state ?? new AssetHistorySyncState([
            'monitored_asset_id' => $monitoredAssetId,
            'status' => $fromDate !== null ? 'pending_bootstrap' : 'bootstrap_complete',
            'bootstrap_from_date' => $fromDate,
        ]);

        $model->last_error = mb_substr($errorMessage, 0, 1000);
        $model->save();

        return $model;
    }
}
