<?php

declare(strict_types=1);

namespace App\Enums;

enum PortfolioPositionEventType: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case PARTIAL_EXIT = 'partial_exit';
    case FULL_EXIT = 'full_exit';
    case STOP_ADJUSTED = 'stop_adjusted';
    case TARGET_ADJUSTED = 'target_adjusted';
    case CANCELED = 'canceled';
}
