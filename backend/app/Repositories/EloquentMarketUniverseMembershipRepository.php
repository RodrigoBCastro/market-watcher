<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\MarketUniverseMembershipRepositoryInterface;
use App\Enums\UniverseType;
use App\Models\MarketUniverseEvent;
use App\Models\MarketUniverseMembership;
use Illuminate\Support\Collection;

class EloquentMarketUniverseMembershipRepository implements MarketUniverseMembershipRepositoryInterface
{
    /**
     * @return array<string, int>
     */
    public function countActiveByType(): array
    {
        return MarketUniverseMembership::query()
            ->selectRaw('universe_type, count(*) as total')
            ->where('is_active', true)
            ->groupBy('universe_type')
            ->pluck('total', 'universe_type')
            ->all();
    }

    public function findActiveByType(string $universeType, int $limit): Collection
    {
        return MarketUniverseMembership::query()
            ->where('universe_type', $universeType)
            ->where('is_active', true)
            ->with([
                'monitoredAsset.latestAnalysisScore' => static function ($query): void {
                    $query->select([
                        'asset_analysis_scores.id',
                        'asset_analysis_scores.monitored_asset_id',
                        'asset_analysis_scores.trade_date',
                        'asset_analysis_scores.final_score',
                        'asset_analysis_scores.classification',
                        'asset_analysis_scores.recommendation',
                        'asset_analysis_scores.setup_label',
                    ]);
                },
            ])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    public function findEligibleWithAssets(): Collection
    {
        return MarketUniverseMembership::query()
            ->where('universe_type', UniverseType::ELIGIBLE->value)
            ->where('is_active', true)
            ->with([
                'monitoredAsset',
                'monitoredAsset.latestAnalysisScore' => static function ($query): void {
                    $query->select([
                        'asset_analysis_scores.id',
                        'asset_analysis_scores.monitored_asset_id',
                        'asset_analysis_scores.trade_date',
                        'asset_analysis_scores.final_score',
                    ]);
                },
            ])
            ->get();
    }

    public function findOrphanTrading(): Collection
    {
        return MarketUniverseMembership::query()
            ->where('universe_type', UniverseType::TRADING->value)
            ->where('is_active', true)
            ->whereDoesntHave('monitoredAsset.universeMemberships', static function ($query): void {
                $query->where('universe_type', UniverseType::ELIGIBLE->value)
                    ->where('is_active', true);
            })
            ->with('monitoredAsset')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $defaults
     */
    public function findOrCreateForAsset(int $assetId, string $universeType, array $defaults): MarketUniverseMembership
    {
        return MarketUniverseMembership::query()->firstOrCreate(
            ['monitored_asset_id' => $assetId, 'universe_type' => $universeType],
            $defaults,
        );
    }

    public function findAllForAsset(int $assetId): Collection
    {
        return MarketUniverseMembership::query()
            ->where('monitored_asset_id', $assetId)
            ->get()
            ->keyBy('universe_type');
    }

    public function save(MarketUniverseMembership $membership): void
    {
        $membership->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createEvent(array $data): void
    {
        MarketUniverseEvent::query()->create($data);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRecentEvents(string $eventType, int $limit): array
    {
        return MarketUniverseEvent::query()
            ->where('event_type', $eventType)
            ->with('monitoredAsset:id,ticker,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(static fn (MarketUniverseEvent $event): array => [
                'id'               => (int) $event->id,
                'event_type'       => $event->event_type,
                'universe_type'    => $event->universe_type,
                'ticker'           => $event->monitoredAsset?->ticker,
                'asset_name'       => $event->monitoredAsset?->name,
                'automatic_reason' => $event->automatic_reason,
                'manual_reason'    => $event->manual_reason,
                'created_at'       => $event->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
