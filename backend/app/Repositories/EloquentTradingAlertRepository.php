<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\TradingAlertRepositoryInterface;
use App\Models\TradingAlert;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class EloquentTradingAlertRepository implements TradingAlertRepositoryInterface
{
    /**
     * @return Collection<int, TradingAlert>
     */
    public function listByUser(int $userId, int $limit, bool $unreadOnly): Collection
    {
        return TradingAlert::query()
            ->where('user_id', $userId)
            ->when($unreadOnly, static function ($query): void {
                $query->where('is_read', false);
            })
            ->orderByDesc('created_at')
            ->limit(max(1, min($limit, 300)))
            ->get();
    }

    public function findByIdForUser(int $id, int $userId): ?TradingAlert
    {
        /** @var TradingAlert|null $alert */
        $alert = TradingAlert::query()
            ->where('user_id', $userId)
            ->find($id);

        return $alert;
    }

    public function markRead(TradingAlert $alert): void
    {
        $alert->update(['is_read' => true]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TradingAlert
    {
        /** @var TradingAlert $alert */
        $alert = TradingAlert::query()->create($data);

        return $alert;
    }

    public function existsBySignature(int $userId, string $alertType, string $title, string $signature): bool
    {
        $todayStart = CarbonImmutable::today()->startOfDay();

        return TradingAlert::query()
            ->where('user_id', $userId)
            ->where('alert_type', $alertType)
            ->where('title', $title)
            ->where('created_at', '>=', $todayStart)
            ->where('payload->signature', $signature)
            ->exists();
    }

    public function countUnread(int $userId): int
    {
        return TradingAlert::query()
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }
}
