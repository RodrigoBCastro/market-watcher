<?php

declare(strict_types=1);

namespace App\Enums;

enum Recommendation: string
{
    case ENTER = 'entrar';
    case WATCH = 'observar';
    case AVOID = 'evitar';
}
