<?php

declare(strict_types=1);

namespace App\Contracts;

interface MarketUniverseServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function summary(): array;

    /**
     * @return array<string, mixed>
     */
    public function listUniverse(string $universeType, int $limit = 200): array;

    /**
     * @return array<string, mixed>
     */
    public function recalculateEligibleUniverse(?int $changedByUserId = null): array;

    /**
     * @return array<string, mixed>
     */
    public function recalculateTradingUniverse(?int $changedByUserId = null): array;

    /**
     * @return array<string, mixed>
     */
    public function updateMembership(
        int $assetId,
        string $universeType,
        bool $isActive,
        ?string $manualReason = null,
        ?int $changedByUserId = null,
    ): array;

    /**
     * @return array<string, mixed>
     */
    public function statusByTicker(string $ticker): array;
}

