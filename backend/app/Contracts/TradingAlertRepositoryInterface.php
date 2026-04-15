<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\TradingAlert;
use Illuminate\Support\Collection;

interface TradingAlertRepositoryInterface
{
    /**
     * @return Collection<int, TradingAlert>
     */
    public function listByUser(int $userId, int $limit, bool $unreadOnly): Collection;

    public function findByIdForUser(int $id, int $userId): ?TradingAlert;

    public function markRead(TradingAlert $alert): void;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TradingAlert;

    /**
     * Checks for an existing alert with matching user, alert_type, title,
     * created today, and the given payload signature.
     */
    public function existsBySignature(int $userId, string $alertType, string $title, string $signature): bool;

    public function countUnread(int $userId): int;
}
