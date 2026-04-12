<?php

declare(strict_types=1);

namespace App\Contracts;

interface SetupDetectorInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $indicators
     * @param  array<string, mixed>  $marketContext
     * @return array<string, mixed>
     */
    public function detect(array $quotes, array $indicators, array $marketContext): array;
}
