<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\MonitoredAsset;
use Illuminate\Http\JsonResponse;

class AssetController extends Controller
{
    public function index(): JsonResponse
    {
        $assets = MonitoredAsset::query()
            ->with('latestAnalysisScore')
            ->orderBy('ticker')
            ->get()
            ->map(static function (MonitoredAsset $asset): array {
                $latestScore = $asset->latestAnalysisScore;

                return [
                    'id' => $asset->id,
                    'ticker' => $asset->ticker,
                    'name' => $asset->name,
                    'sector' => $asset->sector,
                    'is_active' => $asset->is_active,
                    'monitoring_enabled' => $asset->monitoring_enabled,
                    'metadata' => $asset->metadata,
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

        return response()->json([
            'message' => 'Ativo adicionado à watchlist.',
            'item' => $asset,
        ], 201);
    }

    public function update(UpdateAssetRequest $request, int $id): JsonResponse
    {
        $asset = MonitoredAsset::query()->findOrFail($id);
        $asset->update($request->validated());

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
