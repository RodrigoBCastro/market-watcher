<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\BacktestResultDTO;

interface BacktestEngineInterface
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function run(string $strategyName, array $options = []): BacktestResultDTO;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listResults(int $limit = 30): array;
}
