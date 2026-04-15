<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\MarketUniverseMembership;
use Illuminate\Support\Collection;

interface MarketUniverseMembershipRepositoryInterface
{
    /**
     * Returns count of active memberships grouped by universe_type.
     *
     * @return array<string, int>
     */
    public function countActiveByType(): array;

    /**
     * Active memberships of a given type with analysis score eager-loaded.
     */
    public function findActiveByType(string $universeType, int $limit): Collection;

    /**
     * Eligible memberships with monitoredAsset and latestAnalysisScore eager-loaded.
     * Used by recalculateTradingUniverse.
     */
    public function findEligibleWithAssets(): Collection;

    /**
     * Trading memberships that are active but whose asset is no longer in the Eligible Universe.
     */
    public function findOrphanTrading(): Collection;

    /**
     * @param  array<string, mixed>  $defaults
     */
    public function findOrCreateForAsset(int $assetId, string $universeType, array $defaults): MarketUniverseMembership;

    public function findAllForAsset(int $assetId): Collection;

    public function save(MarketUniverseMembership $membership): void;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createEvent(array $data): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRecentEvents(string $eventType, int $limit): array;
}
