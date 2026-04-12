<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\ScoreBreakdownDTO;

interface ScoreEngineInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $indicators
     * @param  array<string, mixed>  $setupContext
     * @param  array<string, mixed>  $marketContext
     */
    public function score(array $quotes, array $indicators, array $setupContext, array $marketContext): ScoreBreakdownDTO;
}
