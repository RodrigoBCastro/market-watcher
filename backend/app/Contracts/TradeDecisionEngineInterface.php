<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\TradeDecisionDTO;

interface TradeDecisionEngineInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $indicators
     * @param  array<string, mixed>  $marketContext
     */
    public function evaluate(string $symbol, array $quotes, array $indicators, array $marketContext): TradeDecisionDTO;
}
