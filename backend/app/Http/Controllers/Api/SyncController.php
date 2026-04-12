<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateDailyBriefJob;
use App\Jobs\RecalculateIndicatorsJob;
use App\Jobs\RecalculateScoresJob;
use App\Jobs\SyncAssetQuotesJob;
use App\Jobs\SyncMarketContextJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;

class SyncController extends Controller
{
    public function syncAsset(string $ticker): JsonResponse
    {
        SyncAssetQuotesJob::dispatch($ticker);

        return response()->json([
            'message' => "Sincronização de {$ticker} enfileirada.",
        ], 202);
    }

    public function syncAssets(): JsonResponse
    {
        SyncAssetQuotesJob::dispatch();

        return response()->json([
            'message' => 'Sincronização de todos os ativos enfileirada.',
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
            new SyncAssetQuotesJob(),
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
