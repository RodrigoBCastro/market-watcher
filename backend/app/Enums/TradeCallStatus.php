<?php

declare(strict_types=1);

namespace App\Enums;

enum TradeCallStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PUBLISHED = 'published';
}
