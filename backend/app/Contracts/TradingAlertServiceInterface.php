<?php

declare(strict_types=1);

namespace App\Contracts;

interface TradingAlertServiceInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(int $userId, bool $onlyUnread = false, int $limit = 100): array;

    /**
     * @return array<string, mixed>
     */
    public function markAsRead(int $userId, int $alertId): array;

    public function refreshForUser(int $userId): int;
}
