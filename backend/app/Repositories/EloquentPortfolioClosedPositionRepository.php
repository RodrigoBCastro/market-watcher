<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\PortfolioClosedPositionRepositoryInterface;
use App\Models\PortfolioClosedPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentPortfolioClosedPositionRepository implements PortfolioClosedPositionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PortfolioClosedPosition
    {
        /** @var PortfolioClosedPosition $closed */
        $closed = PortfolioClosedPosition::query()->create($data);

        return $closed;
    }

    /**
     * @return Collection<int, PortfolioClosedPosition>
     */
    public function listByUser(int $userId): Collection
    {
        return PortfolioClosedPosition::query()
            ->with([
                'portfolioPosition.monitoredAsset:id,ticker,name,sector',
                'portfolioPosition.monitoredAsset.sectorMapping:monitored_asset_id,sector',
            ])
            ->whereHas('portfolioPosition', static function (Builder $query) use ($userId): void {
                $query->where('user_id', $userId);
            })
            ->orderByDesc('exit_date')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return Builder<PortfolioClosedPosition>
     */
    public function queryByUser(int $userId): Builder
    {
        return PortfolioClosedPosition::query()
            ->with([
                'portfolioPosition:id,user_id,monitored_asset_id,trade_call_id,entry_date,entry_price,market_regime',
                'portfolioPosition.monitoredAsset:id,ticker,name,sector',
                'portfolioPosition.monitoredAsset.sectorMapping:monitored_asset_id,sector',
                'portfolioPosition.tradeCall:id,setup_code',
            ])
            ->whereHas('portfolioPosition', static function (Builder $builder) use ($userId): void {
                $builder->where('user_id', $userId);
            });
    }
}
