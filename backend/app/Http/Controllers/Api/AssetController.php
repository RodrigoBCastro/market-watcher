<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\AssetMasterRepositoryInterface;
use App\Contracts\MarketUniverseServiceInterface;
use App\Contracts\MonitoredAssetRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use Illuminate\Http\JsonResponse;

class AssetController extends Controller
{
    public function __construct(
        private readonly MarketUniverseServiceInterface    $marketUniverseService,
        private readonly MonitoredAssetRepositoryInterface $monitoredAssetRepository,
        private readonly AssetMasterRepositoryInterface    $assetMasterRepository,
    ) {
    }

    public function index(): JsonResponse
    {
        $assets = $this->monitoredAssetRepository
            ->findAllForListing()
            ->map(static function ($asset): array {
                $latestScore = $asset->latestAnalysisScore;
                $memberships = $asset->universeMemberships->keyBy('universe_type');
                $historySync = $asset->historySyncState;

                return [
                    'id'                    => $asset->id,
                    'ticker'                => $asset->ticker,
                    'name'                  => $asset->name,
                    'sector'                => $asset->sector,
                    'is_active'             => $asset->is_active,
                    'monitoring_enabled'    => $asset->monitoring_enabled,
                    'collect_data'          => $asset->collect_data,
                    'eligible_for_analysis' => $asset->eligible_for_analysis,
                    'eligible_for_calls'    => $asset->eligible_for_calls,
                    'eligible_for_execution'=> $asset->eligible_for_execution,
                    'universe_type'         => $asset->universe_type,
                    'liquidity_score'       => $asset->liquidity_score,
                    'operability_score'     => $asset->operability_score,
                    'metadata'              => $asset->metadata,
                    'history_sync'          => $historySync !== null ? [
                        'status'                   => $historySync->status,
                        'bootstrap_from_date'       => $historySync->bootstrap_from_date?->toDateString(),
                        'earliest_quote_date_found' => $historySync->earliest_quote_date_found?->toDateString(),
                        'latest_quote_date_synced'  => $historySync->latest_quote_date_synced?->toDateString(),
                        'last_mode_used'            => $historySync->last_mode_used,
                        'bootstrap_completed_at'    => $historySync->bootstrap_completed_at?->toIso8601String(),
                        'last_bootstrap_at'         => $historySync->last_bootstrap_at?->toIso8601String(),
                        'last_rolling_at'           => $historySync->last_rolling_at?->toIso8601String(),
                        'last_error'                => $historySync->last_error,
                    ] : null,
                    'asset_master' => $asset->assetMaster !== null ? [
                        'id'         => (int) $asset->assetMaster->id,
                        'symbol'     => $asset->assetMaster->symbol,
                        'asset_type' => $asset->assetMaster->asset_type,
                        'is_listed'  => (bool) $asset->assetMaster->is_listed,
                    ] : null,
                    'memberships' => [
                        'data_universe'     => (bool) ($memberships->get('data_universe')?->is_active     ?? false),
                        'eligible_universe' => (bool) ($memberships->get('eligible_universe')?->is_active ?? false),
                        'trading_universe'  => (bool) ($memberships->get('trading_universe')?->is_active  ?? false),
                    ],
                    'latest_analysis' => $latestScore !== null ? [
                        'trade_date'     => $latestScore->trade_date?->toDateString(),
                        'final_score'    => (float) $latestScore->final_score,
                        'classification' => $latestScore->classification,
                        'recommendation' => $latestScore->recommendation,
                        'setup_label'    => $latestScore->setup_label,
                    ] : null,
                ];
            });

        return response()->json([
            'items' => $assets,
        ]);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $payload     = $request->validated();
        $assetMaster = $this->assetMasterRepository->findBySymbol(strtoupper((string) ($payload['ticker'] ?? '')));

        if ($assetMaster !== null) {
            $payload['asset_master_id'] = (int) $assetMaster->id;
            $payload['name']            = $payload['name'] ?? $assetMaster->name;
            $payload['sector']          = $payload['sector'] ?? $assetMaster->sector;
        }

        $asset = $this->monitoredAssetRepository->create($payload);

        $this->marketUniverseService->updateMembership(
            assetId: (int) $asset->id,
            universeType: 'data_universe',
            isActive: (bool) $asset->monitoring_enabled,
            manualReason: 'Ativo recém-cadastrado na base de monitoramento.',
            changedByUserId: $request->user() !== null ? (int) $request->user()->id : null,
        );

        return response()->json([
            'message' => 'Ativo adicionado à watchlist.',
            'item'    => $asset->fresh(),
        ], 201);
    }

    public function update(UpdateAssetRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $asset     = $this->monitoredAssetRepository->findOrFailById($id);

        $asset->fill($validated);

        if ($asset->isDirty()) {
            $this->monitoredAssetRepository->save($asset);
        }

        if (array_key_exists('monitoring_enabled', $validated)) {
            $this->marketUniverseService->updateMembership(
                assetId: $id,
                universeType: 'data_universe',
                isActive: (bool) $asset->monitoring_enabled,
                manualReason: 'Ajuste manual de monitoramento do ativo.',
                changedByUserId: $request->user() !== null ? (int) $request->user()->id : null,
            );
        }

        return response()->json([
            'message' => 'Ativo atualizado.',
            'item'    => $asset->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $asset = $this->monitoredAssetRepository->findOrFailById($id);
        $this->monitoredAssetRepository->delete($asset);

        return response()->json([
            'message' => 'Ativo removido da watchlist.',
        ]);
    }
}
