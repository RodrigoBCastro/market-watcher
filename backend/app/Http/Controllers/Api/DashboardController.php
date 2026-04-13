<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Trading\TradingDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly TradingDashboardService $tradingDashboardService)
    {
    }

    public function __invoke(): JsonResponse
    {
        $userId = (int) request()->user()->id;

        return response()->json($this->tradingDashboardService->build($userId));
    }
}
