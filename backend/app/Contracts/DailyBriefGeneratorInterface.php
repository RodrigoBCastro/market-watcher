<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\DailyBriefDTO;

interface DailyBriefGeneratorInterface
{
    public function generate(\DateTimeInterface $briefDate): DailyBriefDTO;
}
