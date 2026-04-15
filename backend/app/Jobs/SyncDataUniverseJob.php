<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\MarketDataProviderInterface;
use App\Contracts\QuoteImporterInterface;
use App\DTOs\MarketQuoteDTO;
use App\Models\AssetHistorySyncState;
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

    private QuoteImporterInterface $quoteImporter;

    public function __construct(public readonly ?string $ticker = null)
    {
    }

    public function handle(MarketDataProviderInterface $provider, SyncLogger $syncLogger, QuoteImporterInterface $quoteImporter): void
    {
        $this->quoteImporter = $quoteImporter;
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
            ->when($this->ticker, static function ($query, string $ticker): void {
                $query->where('ticker', strtoupper($ticker));
            })
            ->select(['id', 'ticker'])
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

        $stateByAssetId = $this->loadStateByAssetId($assets);
        $bootstrapAssets = collect();
        $rollingAssets = collect();

        foreach ($assets as $asset) {
            $state = $stateByAssetId->get((int) $asset->id);
            $mode = $this->resolveMode($state, $fromDate);

            if ($mode === 'bootstrap') {
                $bootstrapAssets->push($asset);

                continue;
            }

            $rollingAssets->push($asset);
        }

        $syncLogger->log($run, 'info', 'Distribuição de modo da sincronização.', [
            'bootstrap_assets' => $bootstrapAssets->count(),
            'rolling_assets' => $rollingAssets->count(),
        ]);

        if ($bootstrapAssets->isNotEmpty() && $fromDate !== null) {
            [$bootstrapProcessed, $bootstrapFailed] = $this->syncAssets(
                syncLogger: $syncLogger,
                run: $run,
                assets: $bootstrapAssets,
                stateByAssetId: $stateByAssetId,
                mode: 'bootstrap',
                modeLabel: 'bootstrap_single',
                fromDate: $fromDate,
                quoteResolver: static fn (MonitoredAsset $asset): array => $provider->getHistoricalQuotes(
                    strtoupper($asset->ticker),
                    $days,
                    $fromDate,
                ),
            );

            $processed += $bootstrapProcessed;
            $failed += $bootstrapFailed;
        }

        foreach ($rollingAssets->chunk($batchSize) as $chunk) {
            $tickers = $chunk->map(static fn (MonitoredAsset $asset): string => strtoupper($asset->ticker))
                ->values()
                ->all();
            $rollingBatchQuotes = $this->loadRollingBatchQuotes(
                provider: $provider,
                syncLogger: $syncLogger,
                run: $run,
                tickers: $tickers,
                days: $days,
            );

            [$chunkProcessed, $chunkFailed] = $this->syncAssets(
                syncLogger: $syncLogger,
                run: $run,
                assets: $chunk,
                stateByAssetId: $stateByAssetId,
                mode: 'rolling',
                modeLabel: 'rolling_batch',
                fromDate: $fromDate,
                quoteResolver: static function (MonitoredAsset $asset) use ($provider, $days, $rollingBatchQuotes): array {
                    $ticker = strtoupper($asset->ticker);
                    $quotes = $rollingBatchQuotes[$ticker] ?? null;

                    if ($quotes !== null) {
                        return $quotes;
                    }

                    return $provider->getHistoricalQuotes($ticker, $days, null);
                },
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
     * @param  Collection<int, MonitoredAsset>  $assets
     * @return Collection<int, AssetHistorySyncState>
     */
    private function loadStateByAssetId(Collection $assets): Collection
    {
        $assetIds = $assets->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        if ($assetIds === []) {
            return collect();
        }

        return AssetHistorySyncState::query()
            ->whereIn('monitored_asset_id', $assetIds)
            ->get()
            ->keyBy('monitored_asset_id');
    }

    /**
     * @param  Collection<int, MonitoredAsset>  $assets
     * @param  Collection<int, AssetHistorySyncState>  $stateByAssetId
     * @return array{0:int,1:int}
     */
    private function syncAssets(
        SyncLogger $syncLogger,
        SyncRun $run,
        Collection $assets,
        Collection $stateByAssetId,
        string $mode,
        string $modeLabel,
        ?string $fromDate,
        callable $quoteResolver,
    ): array {
        $processed = 0;
        $failed = 0;

        foreach ($assets as $asset) {
            try {
                /** @var array<int, MarketQuoteDTO> $quotes */
                $quotes = $quoteResolver($asset);
                $processed += $this->handleSyncSuccess(
                    syncLogger: $syncLogger,
                    run: $run,
                    asset: $asset,
                    stateByAssetId: $stateByAssetId,
                    mode: $mode,
                    modeLabel: $modeLabel,
                    fromDate: $fromDate,
                    quotes: $quotes,
                );
            } catch (Throwable $exception) {
                $failed += $this->handleSyncFailure(
                    syncLogger: $syncLogger,
                    run: $run,
                    asset: $asset,
                    stateByAssetId: $stateByAssetId,
                    errorMessage: $exception->getMessage(),
                    fromDate: $fromDate,
                );
            }
        }

        return [$processed, $failed];
    }

    /**
     * @param  Collection<int, AssetHistorySyncState>  $stateByAssetId
     * @param  array<int, MarketQuoteDTO>  $quotes
     */
    private function handleSyncSuccess(
        SyncLogger $syncLogger,
        SyncRun $run,
        MonitoredAsset $asset,
        Collection $stateByAssetId,
        string $mode,
        string $modeLabel,
        ?string $fromDate,
        array $quotes,
    ): int {
        $state = $stateByAssetId->get((int) $asset->id);
        $result = $this->quoteImporter->import((int) $asset->id, $quotes);

        $updatedState = $this->updateSyncStateAfterSuccess(
            monitoredAssetId: (int) $asset->id,
            state: $state,
            mode: $mode,
            fromDate: $fromDate,
            earliestFromRun: $result->earliestTradeDate,
            latestFromRun: $result->latestTradeDate,
        );
        $stateByAssetId->put((int) $asset->id, $updatedState);

        $syncLogger->log($run, 'info', "Data Universe sincronizado para {$asset->ticker}", [
            'records' => count($quotes),
            'mode' => $modeLabel,
            'state_status' => $updatedState->status,
        ]);

        return $result->processed;
    }

    /**
     * @param  Collection<int, AssetHistorySyncState>  $stateByAssetId
     */
    private function handleSyncFailure(
        SyncLogger $syncLogger,
        SyncRun $run,
        MonitoredAsset $asset,
        Collection $stateByAssetId,
        string $errorMessage,
        ?string $fromDate,
    ): int {
        $state = $stateByAssetId->get((int) $asset->id);
        $updatedState = $this->updateSyncStateAfterFailure(
            monitoredAssetId: (int) $asset->id,
            state: $state,
            errorMessage: $errorMessage,
            fromDate: $fromDate,
        );
        $stateByAssetId->put((int) $asset->id, $updatedState);

        $syncLogger->log($run, 'error', "Falha no Data Universe para {$asset->ticker}", [
            'error' => $errorMessage,
        ]);

        return 1;
    }

    /**
     * @param  array<int, string>  $tickers
     * @return array<string, array<int, \App\DTOs\MarketQuoteDTO>>
     */
    private function loadRollingBatchQuotes(
        MarketDataProviderInterface $provider,
        SyncLogger $syncLogger,
        SyncRun $run,
        array $tickers,
        int $days,
    ): array {
        if ($tickers === []) {
            return [];
        }

        try {
            return $provider->getHistoricalQuotesBatch($tickers, $days, null);
        } catch (Throwable $exception) {
            $syncLogger->log(
                run: $run,
                level: 'warning',
                message: 'Falha no lote rolling da sincronização. Fallback para sync individual.',
                context: [
                    'tickers' => $tickers,
                    'days' => $days,
                    'error' => $exception->getMessage(),
                ],
            );

            return [];
        }
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
