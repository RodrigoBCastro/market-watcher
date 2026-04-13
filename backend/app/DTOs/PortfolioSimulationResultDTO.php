<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class PortfolioSimulationResultDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $calls
     * @param  array<string, float>  $exposureBySector
     * @param  array<string, float>  $exposureByAsset
     */
    public function __construct(
        public float $projectedRiskPercent,
        public float $projectedAllocatedCapital,
        public float $projectedFreeCapital,
        public float $expectedReturnPercent,
        public float $optimisticScenarioPercent,
        public float $conservativeScenarioPercent,
        public array $exposureBySector,
        public array $exposureByAsset,
        public array $calls,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'projected_risk_percent' => $this->projectedRiskPercent,
            'projected_allocated_capital' => $this->projectedAllocatedCapital,
            'projected_free_capital' => $this->projectedFreeCapital,
            'expected_return_percent' => $this->expectedReturnPercent,
            'optimistic_scenario_percent' => $this->optimisticScenarioPercent,
            'conservative_scenario_percent' => $this->conservativeScenarioPercent,
            'exposure_by_sector' => $this->exposureBySector,
            'exposure_by_asset' => $this->exposureByAsset,
            'calls' => $this->calls,
        ];
    }
}
