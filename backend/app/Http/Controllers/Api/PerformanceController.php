<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\PerformanceAnalyticsServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\PerformanceFilterRequest;
use Illuminate\Http\JsonResponse;

class PerformanceController extends Controller
{
    public function __construct(private readonly PerformanceAnalyticsServiceInterface $performanceAnalyticsService)
    {
    }

    public function summary(PerformanceFilterRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        return response()->json(
            $this->performanceAnalyticsService->summary($userId, $request->validated())->toArray(),
        );
    }

    public function equityCurve(PerformanceFilterRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        return response()->json([
            'items' => $this->performanceAnalyticsService->equityCurve($userId, $request->validated()),
        ]);
    }

    public function bySetup(PerformanceFilterRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        return response()->json([
            'items' => $this->performanceAnalyticsService->bySetup($userId, $request->validated()),
        ]);
    }

    public function byAsset(PerformanceFilterRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        return response()->json([
            'items' => $this->performanceAnalyticsService->byAsset($userId, $request->validated()),
        ]);
    }

    public function bySector(PerformanceFilterRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        return response()->json([
            'items' => $this->performanceAnalyticsService->bySector($userId, $request->validated()),
        ]);
    }

    public function byRegime(PerformanceFilterRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        return response()->json([
            'items' => $this->performanceAnalyticsService->byRegime($userId, $request->validated()),
        ]);
    }
}
