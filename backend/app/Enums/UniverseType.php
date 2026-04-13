<?php

declare(strict_types=1);

namespace App\Enums;

enum UniverseType: string
{
    case DATA = 'data_universe';
    case ELIGIBLE = 'eligible_universe';
    case TRADING = 'trading_universe';
}

