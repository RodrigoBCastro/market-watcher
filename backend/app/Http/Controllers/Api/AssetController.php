<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\MarketUniverseServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\MonitoredAsset;
use Illuminate\Http\JsonResponse;

class AssetController extends Controller
{
    public function __construct(private readonly MarketUniverseServiceInterface $marketUniverseService)
    {
    }

    public function index(): JsonResponse
    {
        $assets = MonitoredAsset::query()
            ->with(['latestAnalysisScore', 'universeMemberships'])
            ->orderBy('ticker')
            ->get()
            ->map(static function (MonitoredAsset $asset): array {
                $latestScore = $asset->latestAnalysisScore;
                $memberships = $asset->universeMemberships->keyBy('universe_type');

                return [
                    'id' => $asset->id,
                    'ticker' => $asset->ticker,
                    'name' => $asset->name,
                    'sector' => $asset->sector,
                    'is_active' => $asset->is_active,
                    'monitoring_enabled' => $asset->monitoring_enabled,
                    'collect_data' => $asset->collect_data,
                    'eligible_for_analysis' => $asset->eligible_for_analysis,
                    'eligible_for_calls' => $asset->eligible_for_calls,
                    'eligible_for_execution' => $asset->eligible_for_execution,
                    'universe_type' => $asset->universe_type,
                    'liquidity_score' => $asset->liquidity_score,
                    'operability_score' => $asset->operability_score,
                    'metadata' => $asset->metadata,
                    'memberships' => [
                        'data_universe' => (bool) ($memberships->get('data_universe')?->is_active ?? false),
                        'eligible_universe' => (bool) ($memberships->get('eligible_universe')?->is_active ?? false),
                        'trading_universe' => (bool) ($memberships->get('trading_universe')?->is_active ?? false),
                    ],
                    'latest_analysis' => $latestScore !== null ? [
                        'trade_date' => $latestScore->trade_date?->toDateString(),
                        'final_score' => (float) $latestScore->final_score,
                        'classification' => $latestScore->classification,
                        'recommendation' => $latestScore->recommendation,
                        'setup_label' => $latestScore->setup_label,
                    ] : null,
                ];
            });

        return response()->json([
            'items' => $assets,
        ]);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $asset = MonitoredAsset::query()->create($request->validated());
        $this->marketUniverseService->updateMembership(
            assetId: (int) $asset->id,
            universeType: 'data_universe',
            isActive: (bool) $asset->monitoring_enabled,
            manualReason: 'Ativo recém-cadastrado na base de monitoramento.',
            changedByUserId: $request->user() !== null ? (int) $request->user()->id : null,
        );

        return response()->json([
            'message' => 'Ativo adicionado à watchlist.',
            'item' => $asset->fresh(),
        ], 201);
    }

    public function update(UpdateAssetRequest $request, int $id): JsonResponse
    {
        $asset = MonitoredAsset::query()->findOrFail($id);
        $asset->update($request->validated());

        if (array_key_exists('monitoring_enabled', $request->validated())) {
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
            'item' => $asset->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $asset = MonitoredAsset::query()->findOrFail($id);
        $asset->delete();

        return response()->json([
            'message' => 'Ativo removido da watchlist.',
        ]);
    }
}
