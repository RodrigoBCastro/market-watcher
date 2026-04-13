<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\PortfolioSimulationResultDTO;

interface PortfolioSimulationServiceInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function simulate(int $userId, array $payload): PortfolioSimulationResultDTO;
}
