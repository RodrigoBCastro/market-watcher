<?php

declare(strict_types=1);

namespace App\Services\Scoring;

class MomentumScoreService
{
    /**
     * @param  array<string, mixed>  $current
     * @param  array<int, array<string, mixed>>  $history
     */
    public function score(array $current, array $history): float
    {
        $score = 0.0;

        $rsi14 = (float) ($current['rsi_14'] ?? 0.0);
        $macdLine = (float) ($current['macd_line'] ?? 0.0);
        $macdSignal = (float) ($current['macd_signal'] ?? 0.0);
        $macdHistogram = (float) ($current['macd_histogram'] ?? 0.0);
        $prevHistogram = $this->previousValue($history, 'macd_histogram');

        if ($rsi14 >= 55 && $rsi14 <= 68) {
            $score += 4;
        }

        if ($macdLine > $macdSignal) {
            $score += 3;
        }

        if ($prevHistogram !== null && $macdHistogram > $prevHistogram) {
            $score += 3;
        }

        if ($rsi14 > 75) {
            $score -= 5;
        } elseif ($rsi14 > 72) {
            $score -= 3;
        }

        if ($rsi14 < 45) {
            $score -= 4;
        }

        if ($prevHistogram !== null && $macdHistogram < $prevHistogram) {
            $score -= 3;
        }

        return max(0.0, min(10.0, $score));
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     */
    private function previousValue(array $history, string $key): ?float
    {
        if (count($history) < 2) {
            return null;
        }

        $value = $history[count($history) - 2][$key] ?? null;

        return $value !== null ? (float) $value : null;
    }
}
