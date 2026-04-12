<?php

declare(strict_types=1);

namespace App\Services\Scoring;

class MovingAverageAlignmentScoreService
{
    /**
     * @param  array<string, mixed>  $current
     */
    public function score(array $current): float
    {
        $score = 0.0;

        $ema9 = $this->toNullableFloat($current['ema_9'] ?? null);
        $ema21 = $this->toNullableFloat($current['ema_21'] ?? null);
        $sma50 = $this->toNullableFloat($current['sma_50'] ?? null);
        $sma200 = $this->toNullableFloat($current['sma_200'] ?? null);
        $close = $this->toNullableFloat($current['close'] ?? null);
        $distanceEma21 = $this->toNullableFloat($current['distance_ema_21'] ?? null);

        if ($ema9 !== null && $ema21 !== null && $ema9 > $ema21) {
            $score += 3;
        }

        if ($ema21 !== null && $sma50 !== null && $ema21 > $sma50) {
            $score += 4;
        }

        if ($sma50 !== null && $sma200 !== null && $sma50 > $sma200) {
            $score += 4;
        }

        if ($distanceEma21 !== null && abs($distanceEma21) <= 2.5 && $close !== null && $ema21 !== null && $close >= ($ema21 * 0.98)) {
            $score += 4;
        }

        $isTangled = false;
        if ($ema9 !== null && $ema21 !== null && $sma50 !== null && $close !== null && $close > 0) {
            $isTangled = (abs($ema9 - $ema21) / $close) * 100 < 0.3
                && (abs($ema21 - $sma50) / $close) * 100 < 0.5;
        }

        if ($isTangled) {
            $score -= 3;
        }

        if ($distanceEma21 !== null) {
            if (abs($distanceEma21) > 6) {
                $score -= 5;
            } elseif (abs($distanceEma21) > 4) {
                $score -= 2;
            }
        }

        if ($ema21 !== null && $sma50 !== null && $ema21 < $sma50) {
            $score -= 4;
        }

        return max(0.0, min(15.0, $score));
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
