<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\TradeCallServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminActionRequest;
use App\Http\Requests\ReviewTradeCallRequest;
use App\Jobs\EvaluateOpenTradesJob;
use App\Jobs\WeeklyCallsJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TradeCallController extends Controller
{
    public function __construct(private readonly TradeCallServiceInterface $tradeCallService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $limit = (int) ($request->query('limit') ?? 100);

        return response()->json([
            'items' => $this->tradeCallService->listCalls(
                status: is_string($status) && $status !== '' ? $status : null,
                limit: max(1, min($limit, 300)),
            ),
        ]);
    }

    public function queue(Request $request): JsonResponse
    {
        $limit = (int) ($request->query('limit') ?? 100);

        return response()->json([
            'items' => $this->tradeCallService->listCalls('draft', max(1, min($limit, 300))),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->tradeCallService->getCall($id)->toArray());
    }

    public function generate(AdminActionRequest $request): JsonResponse
    {
        $sync = filter_var((string) $request->query('sync', '0'), FILTER_VALIDATE_BOOL);

        if ($sync) {
            WeeklyCallsJob::dispatchSync();

            return response()->json([
                'message' => 'Ciclo semanal de calls executado em modo síncrono.',
                'items' => $this->tradeCallService->listCalls('draft', 100),
            ]);
        }

        WeeklyCallsJob::dispatch();

        return response()->json([
            'message' => 'Ciclo semanal de calls enfileirado.',
        ], 202);
    }

    public function evaluateOpen(AdminActionRequest $request): JsonResponse
    {
        $sync = filter_var((string) $request->query('sync', '0'), FILTER_VALIDATE_BOOL);

        if ($sync) {
            EvaluateOpenTradesJob::dispatchSync();

            return response()->json([
                'message' => 'Avaliação de trades abertos executada em modo síncrono.',
                'outcomes' => $this->tradeCallService->listOutcomes(100),
            ]);
        }

        EvaluateOpenTradesJob::dispatch();

        return response()->json([
            'message' => 'Avaliação de trades abertos enfileirada.',
        ], 202);
    }

    public function approve(int $id, ReviewTradeCallRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $call = $this->tradeCallService->approve(
            id: $id,
            reviewerId: (int) $request->user()->id,
            comments: $payload['comments'] ?? null,
        );

        return response()->json($call->toArray());
    }

    public function reject(int $id, ReviewTradeCallRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $call = $this->tradeCallService->reject(
            id: $id,
            reviewerId: (int) $request->user()->id,
            comments: $payload['comments'] ?? null,
        );

        return response()->json($call->toArray());
    }

    public function publish(int $id, AdminActionRequest $request): JsonResponse
    {
        return response()->json($this->tradeCallService->publish($id)->toArray());
    }

    public function outcomes(Request $request): JsonResponse
    {
        $limit = (int) ($request->query('limit') ?? 100);

        return response()->json([
            'items' => $this->tradeCallService->listOutcomes(max(1, min($limit, 300))),
        ]);
    }
}
