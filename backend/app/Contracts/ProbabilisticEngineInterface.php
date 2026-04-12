<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\SetupMetricDTO;

interface ProbabilisticEngineInterface
{
    /**
     * @return array<int, SetupMetricDTO>
     */
    public function rebuildSetupMetrics(): array;

    public function getSetupMetric(string $setupCode): ?SetupMetricDTO;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSetupMetrics(): array;

    /**
     * @return array<string, mixed>
     */
    public function getDashboardMetrics(): array;

    public function disableDeterioratingSetups(): int;
}
