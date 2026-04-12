<?php

declare(strict_types=1);

namespace App\Services\Scoring;

class MarketContextScoreService
{
    /**
     * @param  array<string, mixed>  $marketContext
     */
    public function score(array $marketContext): float
    {
        $score = 0.0;
        $bias = (string) ($marketContext['market_bias'] ?? 'neutro');
        $usdPressure = (float) ($marketContext['usd_pressure_percent'] ?? 0.0);
        $inCorrection = (bool) ($marketContext['market_correction'] ?? false);

        if (in_array($bias, ['favoravel', 'cautelosamente_favoravel'], true)) {
            $score += 3;
        }

        if ($usdPressure < 1.2) {
            $score += 2;
        }

        if ($bias === 'fraco') {
            $score -= 3;
        }

        if ($inCorrection) {
            $score -= 5;
        }

        if ($usdPressure >= 2.0) {
            $score -= 2;
        }

        return max(0.0, min(5.0, $score));
    }
}
