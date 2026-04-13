<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\PerformanceSummaryDTO;

interface PerformanceAnalyticsServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function summary(int $userId, array $filters = []): PerformanceSummaryDTO;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function equityCurve(int $userId, array $filters = []): array;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function bySetup(int $userId, array $filters = []): array;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function byAsset(int $userId, array $filters = []): array;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function bySector(int $userId, array $filters = []): array;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function byRegime(int $userId, array $filters = []): array;
}
