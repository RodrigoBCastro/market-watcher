<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateDailyBriefJob;
use App\Jobs\BootstrapDataUniverseFromMasterJob;
use App\Jobs\RecalculateEligibleUniverseJob;
use App\Jobs\RecalculateIndicatorsJob;
use App\Jobs\RecalculateScoresJob;
use App\Jobs\RecalculateTradingUniverseJob;
use App\Jobs\SyncAssetMasterFromBrapiJob;
use App\Jobs\SyncDataUniverseJob;
use App\Jobs\SyncMarketContextJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;

class SyncController extends Controller
{
    public function syncAsset(string $ticker): JsonResponse
    {
        SyncDataUniverseJob::dispatch($ticker);

        return response()->json([
            'message' => "Sincronização de {$ticker} enfileirada.",
        ], 202);
    }

    public function syncAssets(): JsonResponse
    {
        SyncDataUniverseJob::dispatch();

        return response()->json([
            'message' => 'Sincronização do Data Universe enfileirada.',
        ], 202);
    }

    public function syncMarket(): JsonResponse
    {
        SyncMarketContextJob::dispatch();

        return response()->json([
            'message' => 'Sincronização de contexto de mercado enfileirada.',
        ], 202);
    }

    public function syncFull(): JsonResponse
    {
        Bus::chain([
            new SyncAssetMasterFromBrapiJob(),
            new BootstrapDataUniverseFromMasterJob(),
            new SyncDataUniverseJob(),
            new RecalculateEligibleUniverseJob(),
            new RecalculateTradingUniverseJob(),
            new SyncMarketContextJob(),
            new RecalculateIndicatorsJob(),
            new RecalculateScoresJob(),
            new GenerateDailyBriefJob(),
        ])->dispatch();

        return response()->json([
            'message' => 'Pipeline completo de sincronização enfileirado.',
        ], 202);
    }
}
