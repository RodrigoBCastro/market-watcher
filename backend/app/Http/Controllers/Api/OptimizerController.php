<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\ScoreOptimizerInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminActionRequest;
use App\Http\Requests\ApplyRankingWeightsRequest;
use Illuminate\Http\JsonResponse;

class OptimizerController extends Controller
{
    public function __construct(private readonly ScoreOptimizerInterface $scoreOptimizer)
    {
    }

    public function current(): JsonResponse
    {
        return response()->json([
            'weights' => $this->scoreOptimizer->currentWeights(),
        ]);
    }

    public function run(AdminActionRequest $request): JsonResponse
    {
        $result = $this->scoreOptimizer->optimize();

        return response()->json($result->toArray());
    }

    public function apply(ApplyRankingWeightsRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $this->scoreOptimizer->apply([
            'technical_weight' => (float) $payload['technical_weight'],
            'expectancy_weight' => (float) $payload['expectancy_weight'],
        ]);

        return response()->json([
            'message' => 'Pesos aplicados com sucesso.',
            'weights' => $this->scoreOptimizer->currentWeights(),
        ]);
    }
}
