<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\RiskSettingsServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRiskSettingsRequest;
use Illuminate\Http\JsonResponse;

class RiskSettingsController extends Controller
{
    public function __construct(private readonly RiskSettingsServiceInterface $riskSettingsService)
    {
    }

    public function show(): JsonResponse
    {
        $userId = (int) request()->user()->id;

        return response()->json($this->riskSettingsService->getForUser($userId)->toArray());
    }

    public function update(UpdateRiskSettingsRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $payload = $request->validated();

        $settings = $this->riskSettingsService->updateForUser($userId, $payload);

        return response()->json($settings->toArray());
    }
}
