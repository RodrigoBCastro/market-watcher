<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\MarketRegimeDTO;

interface MarketRegimeServiceInterface
{
    public function current(): MarketRegimeDTO;

    /**
     * @return array{min_score: float, max_calls: int}
     */
    public function rulesForRegime(string $regime): array;

    public function contextScoreFromRegime(string $regime): float;
}
