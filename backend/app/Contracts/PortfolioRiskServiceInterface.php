<?php

declare(strict_types=1);

namespace App\Contracts;

interface PortfolioRiskServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function summary(int $userId): array;

    /**
     * @return array<string, mixed>
     */
    public function exposure(int $userId): array;

    /**
     * @return array<string, mixed>
     */
    public function correlations(int $userId): array;

    /**
     * @return array{allowed: bool, violations: array<int, string>, warnings: array<int, string>}
     */
    public function canOpenPosition(int $userId, int $monitoredAssetId, float $positionValue, float $riskAmount): array;
}
