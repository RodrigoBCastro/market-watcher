<?php

declare(strict_types=1);

namespace App\Enums;

enum PortfolioCloseResult: string
{
    case WIN = 'win';
    case LOSS = 'loss';
    case BREAKEVEN = 'breakeven';
}
