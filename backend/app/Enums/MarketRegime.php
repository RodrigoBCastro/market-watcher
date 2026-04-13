<?php

declare(strict_types=1);

namespace App\Enums;

enum MarketRegime: string
{
    case BULL = 'bull';
    case NEUTRAL = 'neutral';
    case CORRECTION = 'correction';
    case BEAR = 'bear';
    case HIGH_VOLATILITY = 'high_volatility';
}
