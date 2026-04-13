<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\TradingAlertServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function __construct(private readonly TradingAlertServiceInterface $tradingAlertService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $onlyUnread = filter_var((string) $request->query('only_unread', '0'), FILTER_VALIDATE_BOOL);
        $limit = (int) ($request->query('limit') ?? 100);

        return response()->json([
            'items' => $this->tradingAlertService->listForUser($userId, $onlyUnread, $limit),
        ]);
    }

    public function read(int $id, Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        return response()->json($this->tradingAlertService->markAsRead($userId, $id));
    }
}
