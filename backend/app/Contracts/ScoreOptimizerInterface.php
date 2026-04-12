<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\OptimizerResultDTO;

interface ScoreOptimizerInterface
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function optimize(array $options = []): OptimizerResultDTO;

    /**
     * @param  array<string, float>  $weights
     */
    public function apply(array $weights): void;

    /**
     * @return array<string, float>
     */
    public function currentWeights(): array;
}
