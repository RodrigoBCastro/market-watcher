<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\PortfolioClosedPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface PortfolioClosedPositionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PortfolioClosedPosition;

    /**
     * Closed positions for a user via the portfolioPosition relation,
     * with portfolioPosition + monitoredAsset + sectorMapping eager-loaded.
     * Ordered by exit_date desc, created_at desc.
     *
     * @return Collection<int, PortfolioClosedPosition>
     */
    public function listByUser(int $userId): Collection;

    /**
     * Returns an Eloquent Builder scoped to a user's closed positions,
     * with the standard analytics eager-loads applied. Used by
     * PerformanceAnalyticsService for date-range filtering and aggregations.
     *
     * @return Builder<PortfolioClosedPosition>
     */
    public function queryByUser(int $userId): Builder;
}
