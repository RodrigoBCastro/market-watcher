<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\MarketUniverseServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminActionRequest;
use App\Http\Requests\UpdateUniverseMembershipRequest;
use App\Jobs\RecalculateEligibleUniverseJob;
use App\Jobs\RecalculateTradingUniverseJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UniverseController extends Controller
{
    public function __construct(private readonly MarketUniverseServiceInterface $marketUniverseService)
    {
    }

    public function summary(): JsonResponse
    {
        return response()->json($this->marketUniverseService->summary());
    }

    public function data(Request $request): JsonResponse
    {
        $limit = (int) ($request->query('limit') ?? 200);

        return response()->json($this->marketUniverseService->listUniverse('data_universe', $limit));
    }

    public function eligible(Request $request): JsonResponse
    {
        $limit = (int) ($request->query('limit') ?? 200);

        return response()->json($this->marketUniverseService->listUniverse('eligible_universe', $limit));
    }

    public function trading(Request $request): JsonResponse
    {
        $limit = (int) ($request->query('limit') ?? 200);

        return response()->json($this->marketUniverseService->listUniverse('trading_universe', $limit));
    }

    public function recalculateEligible(AdminActionRequest $request): JsonResponse
    {
        $sync = filter_var((string) $request->query('sync', '0'), FILTER_VALIDATE_BOOL);

        if ($sync) {
            RecalculateEligibleUniverseJob::dispatchSync((int) $request->user()->id);

            return response()->json([
                'message' => 'Recálculo do Eligible Universe executado em modo síncrono.',
                'summary' => $this->marketUniverseService->summary(),
            ]);
        }

        RecalculateEligibleUniverseJob::dispatch((int) $request->user()->id);

        return response()->json([
            'message' => 'Recálculo do Eligible Universe enfileirado.',
        ], 202);
    }

    public function recalculateTrading(AdminActionRequest $request): JsonResponse
    {
        $sync = filter_var((string) $request->query('sync', '0'), FILTER_VALIDATE_BOOL);

        if ($sync) {
            RecalculateTradingUniverseJob::dispatchSync((int) $request->user()->id);

            return response()->json([
                'message' => 'Recálculo do Trading Universe executado em modo síncrono.',
                'summary' => $this->marketUniverseService->summary(),
            ]);
        }

        RecalculateTradingUniverseJob::dispatch((int) $request->user()->id);

        return response()->json([
            'message' => 'Recálculo do Trading Universe enfileirado.',
        ], 202);
    }

    public function updateAssetMembership(UpdateUniverseMembershipRequest $request, int $id): JsonResponse
    {
        $payload = $request->validated();

        $result = $this->marketUniverseService->updateMembership(
            assetId: $id,
            universeType: (string) $payload['universe_type'],
            isActive: (bool) $payload['is_active'],
            manualReason: $payload['manual_reason'] ?? null,
            changedByUserId: (int) $request->user()->id,
        );

        return response()->json($result);
    }

    public function assetStatus(string $ticker): JsonResponse
    {
        return response()->json($this->marketUniverseService->statusByTicker($ticker));
    }

    public function diagnose(): JsonResponse
    {
        return response()->json($this->marketUniverseService->diagnoseEligibleUniverse());
    }
}

