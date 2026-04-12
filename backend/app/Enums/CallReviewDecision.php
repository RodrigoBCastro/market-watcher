<?php

declare(strict_types=1);

namespace App\Enums;

enum CallReviewDecision: string
{
    case APPROVE = 'approve';
    case REJECT = 'reject';
}
