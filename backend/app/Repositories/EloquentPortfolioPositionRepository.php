<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\PortfolioPositionRepositoryInterface;
use App\Enums\PortfolioPositionStatus;
use App\Models\PortfolioPosition;
use Illuminate\Support\Collection;

class EloquentPortfolioPositionRepository implements PortfolioPositionRepositoryInterface
{
    /**
     * @return Collection<int, PortfolioPosition>
     */
    public function findOpenByUser(int $userId): Collection
    {
        return PortfolioPosition::query()
            ->with('monitoredAsset:id,ticker')
            ->where('user_id', $userId)
            ->where('status', PortfolioPositionStatus::OPEN->value)
            ->get(['id', 'monitored_asset_id', 'current_price']);
    }

    /**
     * @return Collection<int, PortfolioPosition>
     */
    public function findAllByUser(int $userId): Collection
    {
        return PortfolioPosition::query()
            ->with($this->fullRelations())
            ->where('user_id', $userId)
            ->orderByDesc('entry_date')
            ->get();
    }

    /**
     * @return Collection<int, PortfolioPosition>
     */
    public function findOpenByUserWithRelations(int $userId): Collection
    {
        return PortfolioPosition::query()
            ->with($this->fullRelations())
            ->where('user_id', $userId)
            ->where('status', PortfolioPositionStatus::OPEN->value)
            ->orderByDesc('entry_date')
            ->get();
    }

    /**
     * @return Collection<int, PortfolioPosition>
     */
    public function findClosedByUser(int $userId): Collection
    {
        return PortfolioPosition::query()
            ->where('user_id', $userId)
            ->where('status', PortfolioPositionStatus::CLOSED->value)
            ->orderByDesc('entry_date')
            ->get();
    }

    public function findOpenByUserAndAsset(int $userId, int $assetId): ?PortfolioPosition
    {
        /** @var PortfolioPosition|null $position */
        $position = PortfolioPosition::query()
            ->where('user_id', $userId)
            ->where('monitored_asset_id', $assetId)
            ->where('status', PortfolioPositionStatus::OPEN->value)
            ->first();

        return $position;
    }

    public function findByIdForUser(int $id, int $userId): ?PortfolioPosition
    {
        /** @var PortfolioPosition|null $position */
        $position = PortfolioPosition::query()
            ->where('user_id', $userId)
            ->find($id);

        return $position;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PortfolioPosition
    {
        /** @var PortfolioPosition $position */
        $position = PortfolioPosition::query()->create($data);

        return $position;
    }

    public function save(PortfolioPosition $position): void
    {
        $position->save();
    }

    public function delete(PortfolioPosition $position): void
    {
        $position->delete();
    }

    public function countOpenByUser(int $userId): int
    {
        return PortfolioPosition::query()
            ->where('user_id', $userId)
            ->where('status', PortfolioPositionStatus::OPEN->value)
            ->count();
    }

    public function findOrFailByIdForUser(int $id, int $userId): PortfolioPosition
    {
        /** @var PortfolioPosition $position */
        $position = PortfolioPosition::query()
            ->where('user_id', $userId)
            ->findOrFail($id);

        return $position;
    }

    public function findOrFailOpenByIdForUser(int $id, int $userId): PortfolioPosition
    {
        /** @var PortfolioPosition $position */
        $position = PortfolioPosition::query()
            ->where('user_id', $userId)
            ->where('status', PortfolioPositionStatus::OPEN->value)
            ->findOrFail($id);

        return $position;
    }

    /**
     * @return array<int, string>
     */
    private function fullRelations(): array
    {
        return [
            'monitoredAsset:id,ticker,name,sector',
            'monitoredAsset.sectorMapping:monitored_asset_id,sector,subsector,segment',
            'tradeCall:id,setup_code,setup_label,score,confidence_score,confidence_label,market_regime',
        ];
    }
}
