<?php

declare(strict_types=1);

namespace App\Enums;

enum PortfolioPositionStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case CANCELED = 'canceled';
}
