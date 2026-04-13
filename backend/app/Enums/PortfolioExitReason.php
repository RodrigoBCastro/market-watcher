<?php

declare(strict_types=1);

namespace App\Enums;

enum PortfolioExitReason: string
{
    case TARGET = 'target';
    case STOP = 'stop';
    case MANUAL = 'manual';
    case TIMEOUT = 'timeout';
    case REBALANCE = 'rebalance';
}
