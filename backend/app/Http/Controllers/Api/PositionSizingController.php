<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\PositionSizingServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\CalculatePositionSizingRequest;
use Illuminate\Http\JsonResponse;

class PositionSizingController extends Controller
{
    public function __construct(private readonly PositionSizingServiceInterface $positionSizingService)
    {
    }

    public function calculate(CalculatePositionSizingRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        try {
            $result = $this->positionSizingService->calculateForUser($userId, $request->validated());

            return response()->json($result->toArray());
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
