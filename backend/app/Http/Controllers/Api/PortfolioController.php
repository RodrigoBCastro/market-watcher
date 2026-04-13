<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\PortfolioServiceInterface;
use App\Contracts\PortfolioSimulationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClosePortfolioPositionRequest;
use App\Http\Requests\PartialClosePortfolioPositionRequest;
use App\Http\Requests\PortfolioSimulationRequest;
use App\Http\Requests\StorePortfolioPositionRequest;
use App\Http\Requests\UpdatePortfolioPositionRequest;
use Illuminate\Http\JsonResponse;

class PortfolioController extends Controller
{
    public function __construct(
        private readonly PortfolioServiceInterface $portfolioService,
        private readonly PortfolioSimulationServiceInterface $portfolioSimulationService,
    ) {
    }

    public function index(): JsonResponse
    {
        $userId = (int) request()->user()->id;

        return response()->json([
            'items' => $this->portfolioService->listAll($userId),
        ]);
    }

    public function open(): JsonResponse
    {
        $userId = (int) request()->user()->id;

        return response()->json([
            'items' => $this->portfolioService->listOpen($userId),
        ]);
    }

    public function closed(): JsonResponse
    {
        $userId = (int) request()->user()->id;

        return response()->json([
            'items' => $this->portfolioService->listClosed($userId),
        ]);
    }

    public function store(StorePortfolioPositionRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        try {
            $result = $this->portfolioService->create($userId, $request->validated());

            return response()->json($result, 201);
        } catch (\DomainException|\InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function update(int $id, UpdatePortfolioPositionRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        try {
            return response()->json($this->portfolioService->update($userId, $id, $request->validated()));
        } catch (\DomainException|\InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function close(int $id, ClosePortfolioPositionRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        try {
            return response()->json($this->portfolioService->close($userId, $id, $request->validated()));
        } catch (\DomainException|\InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function partialClose(int $id, PartialClosePortfolioPositionRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        try {
            return response()->json($this->portfolioService->partialClose($userId, $id, $request->validated()));
        } catch (\DomainException|\InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function simulate(PortfolioSimulationRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        try {
            $result = $this->portfolioSimulationService->simulate($userId, $request->validated());

            return response()->json($result->toArray());
        } catch (\DomainException|\InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
