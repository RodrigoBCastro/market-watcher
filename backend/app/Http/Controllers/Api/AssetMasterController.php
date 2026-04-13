<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\AssetMasterRegistryServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminActionRequest;
use App\Http\Requests\BootstrapDataUniverseRequest;
use App\Jobs\BootstrapDataUniverseFromMasterJob;
use App\Jobs\SyncAssetMasterFromBrapiJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetMasterController extends Controller
{
    public function __construct(private readonly AssetMasterRegistryServiceInterface $assetMasterRegistryService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->assetMasterRegistryService->list([
            'type' => $request->query('type'),
            'sector' => $request->query('sector'),
            'active' => $request->query('active'),
            'listed' => $request->query('listed'),
            'universe' => $request->query('universe'),
            'search' => $request->query('search'),
            'limit' => $request->query('limit', 200),
        ]));
    }

    public function show(string $symbol): JsonResponse
    {
        return response()->json($this->assetMasterRegistryService->getBySymbol($symbol));
    }

    public function sync(AdminActionRequest $request): JsonResponse
    {
        $sync = filter_var((string) $request->query('sync', '0'), FILTER_VALIDATE_BOOL);

        if ($sync) {
            SyncAssetMasterFromBrapiJob::dispatchSync();

            return response()->json([
                'message' => 'Sincronização do cadastro mestre executada em modo síncrono.',
                'summary' => $this->assetMasterRegistryService->list(['limit' => 1])['summary'] ?? [],
            ]);
        }

        SyncAssetMasterFromBrapiJob::dispatch();

        return response()->json([
            'message' => 'Sincronização do cadastro mestre enfileirada.',
        ], 202);
    }

    public function indexes(Request $request): JsonResponse
    {
        return response()->json($this->assetMasterRegistryService->listIndexes([
            'active' => $request->query('active'),
            'limit' => $request->query('limit', 300),
        ]));
    }

    public function bootstrapDataUniverse(BootstrapDataUniverseRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $sync = filter_var((string) $request->query('sync', '0'), FILTER_VALIDATE_BOOL);
        $userId = $request->user() !== null ? (int) $request->user()->id : null;

        if ($sync) {
            BootstrapDataUniverseFromMasterJob::dispatchSync($payload, $userId);

            return response()->json([
                'message' => 'Bootstrap do Data Universe executado em modo síncrono.',
            ]);
        }

        BootstrapDataUniverseFromMasterJob::dispatch($payload, $userId);

        return response()->json([
            'message' => 'Bootstrap do Data Universe enfileirado.',
        ], 202);
    }
}

