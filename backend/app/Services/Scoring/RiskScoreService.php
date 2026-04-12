<?php

declare(strict_types=1);

namespace App\Services\Scoring;

class RiskScoreService
{
    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $setupContext
     */
    public function score(array $current, array $setupContext): float
    {
        $technicalStopPercent = (float) ($setupContext['trade_plan']['risk_percent'] ?? 999.0);
        $atr = (float) ($current['atr_14'] ?? 0.0);
        $close = (float) ($current['close'] ?? 0.0);
        $avgRange = (float) ($current['avg_range'] ?? 0.0);

        if ($technicalStopPercent > 4.0) {
            return 0.0;
        }

        $score = 5.0;

        if ($close > 0.0) {
            $atrPercent = ($atr / $close) * 100;
            if ($atrPercent <= 3.0) {
                $score += 5.0;
            } elseif ($atrPercent > 5.0) {
                $score -= 5.0;
            }
        }

        if ($avgRange > 4.5) {
            $score -= 5.0;
        }

        return max(0.0, min(10.0, $score));
    }
}
