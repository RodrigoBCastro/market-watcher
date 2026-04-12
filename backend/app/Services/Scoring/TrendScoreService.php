<?php

declare(strict_types=1);

namespace App\Services\Scoring;

class TrendScoreService
{
    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $current
     * @param  array<int, array<string, mixed>>  $history
     */
    public function score(array $quotes, array $current, array $history): float
    {
        if ($quotes === []) {
            return 0.0;
        }

        $latestQuote = end($quotes);
        $close = (float) ($latestQuote['close'] ?? 0.0);

        $score = 0.0;

        $ema21 = $this->toNullableFloat($current['ema_21'] ?? null);
        $sma50 = $this->toNullableFloat($current['sma_50'] ?? null);
        $sma200 = $this->toNullableFloat($current['sma_200'] ?? null);

        if ($ema21 !== null && $close > $ema21) {
            $score += 4;
        }

        if ($sma50 !== null && $close > $sma50) {
            $score += 4;
        }

        if ($sma200 !== null && $close > $sma200) {
            $score += 4;
        }

        if ($sma50 !== null && $sma200 !== null && $sma50 > $sma200) {
            $score += 3;
        }

        $ema21Prev = $this->historyValue($history, 'ema_21');
        if ($ema21 !== null && $ema21Prev !== null && $ema21 > $ema21Prev) {
            $score += 3;
        }

        $sma50Prev = $this->historyValue($history, 'sma_50');
        if ($sma50 !== null && $sma50Prev !== null && $sma50 > $sma50Prev) {
            $score += 2;
        }

        return max(0.0, min(20.0, $score));
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     */
    private function historyValue(array $history, string $key): ?float
    {
        if (count($history) < 2) {
            return null;
        }

        $previous = $history[count($history) - 2][$key] ?? null;

        return $this->toNullableFloat($previous);
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
