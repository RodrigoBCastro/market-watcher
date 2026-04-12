<?php

declare(strict_types=1);

namespace App\Services\Indicators;

class StochasticCalculator
{
    /**
     * @param  array<int, float|int>  $highs
     * @param  array<int, float|int>  $lows
     * @param  array<int, float|int>  $closes
     * @return array{k: array<int, float|null>, d: array<int, float|null>}
     */
    public function calculate(array $highs, array $lows, array $closes, int $period = 14, int $dPeriod = 3): array
    {
        $count = count($closes);
        $k = array_fill(0, $count, null);
        $d = array_fill(0, $count, null);

        if ($count < $period) {
            return ['k' => $k, 'd' => $d];
        }

        for ($i = $period - 1; $i < $count; $i++) {
            $highSlice = array_map('floatval', array_slice($highs, $i - $period + 1, $period));
            $lowSlice = array_map('floatval', array_slice($lows, $i - $period + 1, $period));
            $highest = max($highSlice);
            $lowest = min($lowSlice);

            if ($highest == $lowest) {
                $k[$i] = 50.0;
                continue;
            }

            $k[$i] = (((float) $closes[$i] - $lowest) / ($highest - $lowest)) * 100;
        }

        for ($i = $period + $dPeriod - 2; $i < $count; $i++) {
            $slice = array_slice($k, $i - $dPeriod + 1, $dPeriod);
            $valid = array_filter($slice, static fn ($value): bool => $value !== null);

            if (count($valid) !== $dPeriod) {
                continue;
            }

            $d[$i] = array_sum($valid) / $dPeriod;
        }

        return ['k' => $k, 'd' => $d];
    }
}
