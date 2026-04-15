<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\PortfolioPosition;
use Illuminate\Support\Collection;

interface PortfolioPositionRepositoryInterface
{
    /**
     * Open positions for a user, eager-loading monitoredAsset:id,ticker.
     *
     * @return Collection<int, PortfolioPosition>
     */
    public function findOpenByUser(int $userId): Collection;

    /**
     * All positions for a user with full relations, ordered by entry_date desc.
     *
     * @return Collection<int, PortfolioPosition>
     */
    public function findAllByUser(int $userId): Collection;

    /**
     * Open positions with full portfolio relations (monitoredAsset, sectorMapping, tradeCall).
     *
     * @return Collection<int, PortfolioPosition>
     */
    public function findOpenByUserWithRelations(int $userId): Collection;

    /**
     * @return Collection<int, PortfolioPosition>
     */
    public function findClosedByUser(int $userId): Collection;

    public function findOpenByUserAndAsset(int $userId, int $assetId): ?PortfolioPosition;

    public function findByIdForUser(int $id, int $userId): ?PortfolioPosition;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PortfolioPosition;

    public function save(PortfolioPosition $position): void;

    public function delete(PortfolioPosition $position): void;

    public function countOpenByUser(int $userId): int;

    /**
     * Throws ModelNotFoundException if the position does not belong to $userId.
     */
    public function findOrFailByIdForUser(int $id, int $userId): PortfolioPosition;

    /**
     * Throws ModelNotFoundException if the position does not belong to $userId
     * or is not in the open status.
     */
    public function findOrFailOpenByIdForUser(int $id, int $userId): PortfolioPosition;
}
