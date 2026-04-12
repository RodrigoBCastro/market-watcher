<?php

declare(strict_types=1);

namespace App\Contracts;

interface IndicatorCalculatorInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @return array<int, array<string, mixed>>
     */
    public function calculate(array $quotes): array;
}
