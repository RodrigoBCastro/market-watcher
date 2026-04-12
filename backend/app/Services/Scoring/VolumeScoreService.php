<?php

declare(strict_types=1);

namespace App\Services\Scoring;

class VolumeScoreService
{
    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $setupContext
     */
    public function score(array $quotes, array $current, array $setupContext): float
    {
        if ($quotes === []) {
            return 0.0;
        }

        $latest = end($quotes);
        $volume = (float) ($latest['volume'] ?? 0.0);
        $avg20 = (float) ($current['avg_volume_20'] ?? 0.0);

        $score = 0.0;

        if ($avg20 > 0 && $volume > $avg20) {
            $score += 4;
        }

        $setupCode = (string) ($setupContext['primary']['code'] ?? '');

        if (in_array($setupCode, ['BREAKOUT_20D', 'CONSOLIDATION_BREAK'], true) && $avg20 > 0 && $volume >= $avg20 * 1.3) {
            $score += 6;
        }

        if (in_array($setupCode, ['BREAKOUT_20D', 'CONSOLIDATION_BREAK'], true) && $avg20 > 0 && $volume <= $avg20) {
            $score -= 5;
        }

        if (in_array($setupCode, ['PULLBACK_EMA21', 'PULLBACK_SMA50'], true) && $avg20 > 0 && $volume < $avg20) {
            $score -= 3;
        }

        return max(0.0, min(10.0, $score));
    }
}
