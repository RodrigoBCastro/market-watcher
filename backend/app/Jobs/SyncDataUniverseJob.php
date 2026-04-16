<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\AssetQuoteRepositoryInterface;
use App\Contracts\AssetHistorySyncStateRepositoryInterface;
use App\Contracts\MarketDataProviderInterface;
use App\Contracts\MonitoredAssetRepositoryInterface;
use App\DTOs\MarketQuoteDTO;
use App\Models\AssetHistorySyncState;
use App\Models\MonitoredAsset;
use App\Models\SyncRun;
use App\Services\MarketData\SyncLogger;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

class SyncDataUniverseJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1200;

    private AssetQuoteRepositoryInterface $assetQuoteRepository;

    private AssetHistorySyncStateRepositoryInterface $syncStateRepository;

    private MonitoredAssetRepositoryInterface $monitoredAssetRepository;

    public function __construct(public readonly ?string $ticker = null)
    {
    }

    public function handle(
        MarketDataProviderInterface $provider,
        SyncLogger $syncLogger,
        AssetQuoteRepositoryInterface $assetQuoteRepository,
        AssetHistorySyncStateRepositoryInterface $syncStateRepository,
        MonitoredAssetRepositoryInterface $monitoredAssetRepository,
    ): void {
        $this->assetQuoteRepository     = $assetQuoteRepository;
        $this->syncStateRepository      = $syncStateRepository;
        $this->monitoredAssetRepository = $monitoredAssetRepository;

        $run = $syncLogger->start($this->ticker !== null ? 'sync_data_universe_single' : 'sync_data_universe');

        $processed = 0;
        $failed    = 0;

        try {
            $days           = $this->resolveDays();
            $startYearsBack = $this->resolveStartYearsBack();
            $dateBack       = $this->resolveDateBack();
            $fromDate       = $this->resolveFromDate($startYearsBack, $dateBack);
            $batchSize      = $this->resolveBatchSize();
        } catch (Throwable $exception) {
            $syncLogger->log($run, 'error', 'Parâmetros inválidos para sincronização do Data Universe.', [
                'error' => $exception->getMessage(),
            ]);
            $syncLogger->finish($run, 'failed', 0, 1, 'Parâmetros de sync inválidos.');

            return;
        }

        $assets   = $this->monitoredAssetRepository->findForDataCollection($this->ticker);
        $fromMode = $fromDate === null ? 'disabled' : 'years_back';

        $syncLogger->log($run, 'info', 'Parâmetros de sincronização do Data Universe.', [
            'asset_days'    => $days,
            'from_date'     => $fromDate,
            'from_mode'     => $fromMode,
            'start_years_back' => $startYearsBack,
            'batch_size'    => $batchSize,
            'assets_total'  => $assets->count(),
        ]);

        $stateByAssetId  = $this->loadStateByAssetId($assets);
        $bootstrapAssets = collect();
        $rollingAssets   = collect();

        foreach ($assets as $asset) {
            $state = $stateByAssetId->get((int) $asset->id);
            $mode  = $this->resolveMode($state, $fromDate);

            if ($mode === 'bootstrap') {
                $bootstrapAssets->push($asset);
                continue;
            }

            $rollingAssets->push($asset);
        }

        $syncLogger->log($run, 'info', 'Distribuição de modo da sincronização.', [
            'bootstrap_assets' => $bootstrapAssets->count(),
            'rolling_assets'   => $rollingAssets->count(),
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
                quoteResolver: function (MonitoredAsset $asset) use ($provider, $syncLogger, $run, $days, $fromDate): array {
                    $ticker = strtoupper($asset->ticker);
                    return $this->fetchHistoricalQuotesWithRetry(
                        provider: $provider,
                        syncLogger: $syncLogger,
                        run: $run,
                        ticker: $ticker,
                        days: $days,
                        fromDate: $fromDate,
                        modeLabel: 'bootstrap_single',
                    );
                },
            );

            $processed += $bootstrapProcessed;
            $failed    += $bootstrapFailed;
        }

        foreach ($rollingAssets->chunk($batchSize) as $chunk) {
            $tickers = $chunk
                ->map(static fn (MonitoredAsset $asset): string => strtoupper($asset->ticker))
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
                quoteResolver: function (MonitoredAsset $asset) use ($provider, $syncLogger, $run, $days, $rollingBatchQuotes): array {
                    $ticker = strtoupper($asset->ticker);
                    $quotes = $rollingBatchQuotes[$ticker] ?? null;

                    if ($quotes !== null && $quotes !== []) {
                        return $quotes;
                    }

                    if ($quotes === []) {
                        $syncLogger->log(
                            run: $run,
                            level: 'warning',
                            message: "Lote rolling retornou vazio para {$ticker}. Fallback para sync individual.",
                            context: [
                                'ticker' => $ticker,
                                'days' => $days,
                            ],
                        );
                    }

                    return $this->fetchHistoricalQuotesWithRetry(
                        provider: $provider,
                        syncLogger: $syncLogger,
                        run: $run,
                        ticker: $ticker,
                        days: $days,
                        fromDate: null,
                        modeLabel: 'rolling_single_fallback',
                    );
                },
            );

            $processed += $chunkProcessed;
            $failed    += $chunkFailed;
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
        $assetIds = $assets
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        return $this->syncStateRepository->loadByAssetIds($assetIds);
    }

    /**
     * @param  Collection<int, MonitoredAsset>             $assets
     * @param  Collection<int, AssetHistorySyncState>      $stateByAssetId
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
        $failed    = 0;

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
     * @param  array<int, MarketQuoteDTO>               $quotes
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
        $records = count($quotes);
        $minRecordsPerAsset = $this->resolveMinRecordsPerAsset();
        if ($records < $minRecordsPerAsset) {
            throw new RuntimeException(
                "Resposta insuficiente do provedor para {$asset->ticker} (records={$records}, min_expected={$minRecordsPerAsset}).",
            );
        }

        $state = $stateByAssetId->get((int) $asset->id);
        $result = $this->assetQuoteRepository->upsertBatch((int) $asset->id, $quotes);

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
            'records'      => $records,
            'mode'         => $modeLabel,
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
        $state        = $stateByAssetId->get((int) $asset->id);
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
     * @return array<string, array<int, MarketQuoteDTO>>
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
                    'days'    => $days,
                    'error'   => $exception->getMessage(),
                ],
            );

            return [];
        }
    }

    private function resolveDays(): int
    {
        return max(1, (int) config('market.sync.asset_days', 90));
    }

    private function resolveMinRecordsPerAsset(): int
    {
        return max(1, (int) config('market.sync.min_records_per_asset', 1));
    }

    private function resolveEmptyQuoteRetryAttempts(): int
    {
        return max(1, min((int) config('market.sync.empty_quotes_retries', 2), 5));
    }

    private function resolveEmptyQuoteRetrySleepMs(): int
    {
        return max(0, min((int) config('market.sync.empty_quotes_retry_sleep_ms', 250), 5000));
    }

    private function resolveFromDate(int $startYearsBack, ?string $dateBack): ?string
    {
        if (!empty($dateBack)) {
            return CarbonImmutable::parse($dateBack)->toDateString();
        }

        if ($startYearsBack > 0) {
            return CarbonImmutable::now()->subYears($startYearsBack)->toDateString();
        }

        return null;
    }

    private function resolveStartYearsBack(): int
    {
        return max(0, (int) config('market.sync.start_years_back', 15));
    }

    private function resolveDateBack(): string
    {
        return config('market.sync.date_back', null);
    }

    private function resolveBatchSize(): int
    {
        return max(1, min((int) config('market.sync.batch_size', 20), 20));
    }

    private function fetchHistoricalQuotesWithRetry(
        MarketDataProviderInterface $provider,
        SyncLogger $syncLogger,
        SyncRun $run,
        string $ticker,
        int $days,
        ?string $fromDate,
        string $modeLabel,
    ): array {
        $attempts = $this->resolveEmptyQuoteRetryAttempts();
        $retrySleepMs = $this->resolveEmptyQuoteRetrySleepMs();
        $minRecords = $this->resolveMinRecordsPerAsset();
        $lastQuotes = [];

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $quotes = $provider->getHistoricalQuotes($ticker, $days, $fromDate);
            } catch (Throwable $exception) {
                if ($attempt >= $attempts) {
                    throw $exception;
                }

                $syncLogger->log(
                    run: $run,
                    level: 'warning',
                    message: "Falha ao buscar histórico de {$ticker}. Nova tentativa agendada.",
                    context: [
                        'mode' => $modeLabel,
                        'attempt' => $attempt,
                        'attempts_total' => $attempts,
                        'error' => $exception->getMessage(),
                    ],
                );

                if ($retrySleepMs > 0) {
                    usleep($retrySleepMs * 1000);
                }

                continue;
            }

            $lastQuotes = $quotes;
            if (count($quotes) >= $minRecords) {
                return $quotes;
            }

            if ($attempt < $attempts) {
                $syncLogger->log(
                    run: $run,
                    level: 'warning',
                    message: "Resposta vazia para {$ticker}. Nova tentativa de coleta.",
                    context: [
                        'mode' => $modeLabel,
                        'attempt' => $attempt,
                        'attempts_total' => $attempts,
                        'records' => count($quotes),
                        'min_expected' => $minRecords,
                    ],
                );

                if ($retrySleepMs > 0) {
                    usleep($retrySleepMs * 1000);
                }
            }
        }

        return $lastQuotes;
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
        $now   = now();
        $model = $state ?? new AssetHistorySyncState([
            'monitored_asset_id' => $monitoredAssetId,
            'status'             => $mode === 'bootstrap' ? 'pending_bootstrap' : 'bootstrap_complete',
            'bootstrap_from_date' => $fromDate,
        ]);

        $existingEarliest   = $model->earliest_quote_date_found?->toDateString();
        $existingLatest     = $model->latest_quote_date_synced?->toDateString();
        $candidateEarliest  = $this->minDate($existingEarliest, $earliestFromRun);
        $candidateLatest    = $this->maxDate($existingLatest, $latestFromRun);

        $model->latest_quote_date_synced = $candidateLatest;
        $model->last_mode_used           = $mode;
        $model->last_error               = null;

        if ($mode === 'bootstrap') {
            $model->bootstrap_from_date       = $model->bootstrap_from_date?->toDateString() ?? $fromDate;
            $model->earliest_quote_date_found = $candidateEarliest;
            $model->last_bootstrap_at         = $now;
            $model->status                    = 'bootstrap_complete';
            $model->bootstrap_completed_at    = $model->bootstrap_completed_at ?? $now;
        } else {
            $model->last_rolling_at = $now;
            $model->status          = 'bootstrap_complete';
            if ($model->bootstrap_completed_at === null) {
                $model->bootstrap_completed_at = $now;
            }
        }

        $this->syncStateRepository->save($model);

        return $model;
    }

    private function updateSyncStateAfterFailure(
        int $monitoredAssetId,
        ?AssetHistorySyncState $state,
        string $errorMessage,
        ?string $fromDate,
    ): AssetHistorySyncState {
        $model = $state ?? new AssetHistorySyncState([
            'monitored_asset_id'  => $monitoredAssetId,
            'status'              => $fromDate !== null ? 'pending_bootstrap' : 'bootstrap_complete',
            'bootstrap_from_date' => $fromDate,
        ]);

        $model->last_error = mb_substr($errorMessage, 0, 1000);
        $this->syncStateRepository->save($model);

        return $model;
    }
}
