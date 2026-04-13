<?php

declare(strict_types=1);

namespace App\Enums;

enum UniverseEventType: string
{
    case PROMOTED = 'promoted';
    case DEMOTED = 'demoted';
    case MANUAL_OVERRIDE = 'manual_override';
    case REVIEW = 'review';
}

