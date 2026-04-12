<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\BacktestEngineInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\RunBacktestRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BacktestController extends Controller
{
    public function __construct(private readonly BacktestEngineInterface $backtestEngine)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $limit = (int) ($request->query('limit') ?? 30);

        return response()->json([
            'items' => $this->backtestEngine->listResults(max(1, min($limit, 200))),
        ]);
    }

    public function run(RunBacktestRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $strategy = (string) ($payload['strategy_name'] ?? 'default_quant');

        $result = $this->backtestEngine->run($strategy, [
            'from' => $payload['from'] ?? null,
            'to' => $payload['to'] ?? null,
            'max_holding_days' => (int) ($payload['max_holding_days'] ?? config('market.calls.max_holding_days', 20)),
        ]);

        return response()->json($result->toArray());
    }
}
