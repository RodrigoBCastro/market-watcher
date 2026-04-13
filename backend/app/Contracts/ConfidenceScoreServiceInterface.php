<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\ConfidenceScoreDTO;

interface ConfidenceScoreServiceInterface
{
    public function calculate(float $technicalScore, float $expectancy, string $marketRegime): ConfidenceScoreDTO;
}
