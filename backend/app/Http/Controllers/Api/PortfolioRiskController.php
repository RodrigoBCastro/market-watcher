<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\PortfolioRiskServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PortfolioRiskController extends Controller
{
    public function __construct(private readonly PortfolioRiskServiceInterface $portfolioRiskService)
    {
    }

    public function risk(): JsonResponse
    {
        $userId = (int) request()->user()->id;

        return response()->json($this->portfolioRiskService->summary($userId));
    }

    public function exposure(): JsonResponse
    {
        $userId = (int) request()->user()->id;

        return response()->json($this->portfolioRiskService->exposure($userId));
    }

    public function correlations(): JsonResponse
    {
        $userId = (int) request()->user()->id;

        return response()->json($this->portfolioRiskService->correlations($userId));
    }
}
