<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\ProbabilisticEngineInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class QuantController extends Controller
{
    public function __construct(private readonly ProbabilisticEngineInterface $probabilisticEngine)
    {
    }

    public function dashboard(): JsonResponse
    {
        return response()->json($this->probabilisticEngine->getDashboardMetrics());
    }

    public function setupMetrics(): JsonResponse
    {
        return response()->json([
            'items' => $this->probabilisticEngine->listSetupMetrics(),
        ]);
    }
}
