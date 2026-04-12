<?php

declare(strict_types=1);

namespace App\Services\Indicators;

class BollingerCalculator
{
    /**
     * @param  array<int, float|int>  $closes
     * @return array{mid: array<int, float|null>, upper: array<int, float|null>, lower: array<int, float|null>}
     */
    public function calculate(array $closes, int $period = 20, float $multiplier = 2.0): array
    {
        $count = count($closes);
        $mid = array_fill(0, $count, null);
        $upper = array_fill(0, $count, null);
        $lower = array_fill(0, $count, null);

        if ($count < $period) {
            return [
                'mid' => $mid,
                'upper' => $upper,
                'lower' => $lower,
            ];
        }

        for ($i = $period - 1; $i < $count; $i++) {
            $slice = array_map('floatval', array_slice($closes, $i - $period + 1, $period));
            $mean = array_sum($slice) / $period;
            $variance = 0.0;

            foreach ($slice as $value) {
                $variance += ($value - $mean) ** 2;
            }

            $stdDev = sqrt($variance / $period);

            $mid[$i] = $mean;
            $upper[$i] = $mean + ($multiplier * $stdDev);
            $lower[$i] = $mean - ($multiplier * $stdDev);
        }

        return [
            'mid' => $mid,
            'upper' => $upper,
            'lower' => $lower,
        ];
    }
}
