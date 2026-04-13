<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\PositionSizingResultDTO;

interface PositionSizingServiceInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function calculateForUser(int $userId, array $payload): PositionSizingResultDTO;
}
