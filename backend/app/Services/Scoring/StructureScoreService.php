<?php

declare(strict_types=1);

namespace App\Services\Scoring;

class StructureScoreService
{
    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $setupContext
     */
    public function score(array $quotes, array $current, array $setupContext): float
    {
        if (count($quotes) < 3) {
            return 0.0;
        }

        $score = 0.0;
        $last = $quotes[count($quotes) - 1];
        $prev = $quotes[count($quotes) - 2];

        if ((float) $last['high'] > (float) $prev['high']) {
            $score += 2.5;
        }

        if ((float) $last['low'] > (float) $prev['low']) {
            $score += 2.5;
        }

        if ($this->isConstructiveConsolidation($quotes, $current)) {
            $score += 4;
        }

        $primarySetup = (string) ($setupContext['primary']['code'] ?? '');
        if (in_array($primarySetup, ['PULLBACK_EMA21', 'PULLBACK_SMA50'], true)) {
            $score += 5;
        }

        if (in_array($primarySetup, ['BREAKOUT_20D', 'CONSOLIDATION_BREAK'], true)) {
            $score += 6;
        }

        if ($primarySetup === 'SIDEWAYS_NO_EDGE' || ((float) ($current['adx_14'] ?? 0) < 18.0)) {
            $score -= 4;
        }

        if (in_array($primarySetup, ['BREAKOUT_20D', 'CONSOLIDATION_BREAK'], true)
            && (float) ($last['close'] ?? 0) < (float) ($current['high_20'] ?? 0)
            && (float) ($last['volume'] ?? 0) <= (float) ($current['avg_volume_20'] ?? 0)) {
            $score -= 5;
        }

        if ((float) ($last['close'] ?? 0) < (float) ($current['sma_50'] ?? 0)
            && (float) ($last['close'] ?? 0) < (float) ($current['ema_21'] ?? 0)) {
            $score -= 8;
        }

        return max(0.0, min(20.0, $score));
    }

    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $current
     */
    private function isConstructiveConsolidation(array $quotes, array $current): bool
    {
        $slice = array_slice($quotes, -10);

        if (count($slice) < 10) {
            return false;
        }

        $high = max(array_map(static fn (array $row): float => (float) $row['high'], $slice));
        $low = min(array_map(static fn (array $row): float => (float) $row['low'], $slice));
        $close = (float) ($slice[count($slice) - 1]['close'] ?? 0.0);

        if ($close == 0.0) {
            return false;
        }

        $compression = (($high - $low) / $close) * 100;

        return $compression <= 6.0 && (float) ($current['sma_50'] ?? 0.0) < $close;
    }
}
