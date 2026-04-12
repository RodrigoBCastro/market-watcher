<?php

declare(strict_types=1);

namespace App\Services\Indicators;

class SmaCalculator
{
    /**
     * @param  array<int, float|int|null>  $values
     * @return array<int, float|null>
     */
    public function calculate(array $values, int $period): array
    {
        $count = count($values);
        $result = array_fill(0, $count, null);

        if ($period <= 0 || $count < $period) {
            return $result;
        }

        $windowSum = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $value = (float) ($values[$i] ?? 0.0);
            $windowSum += $value;

            if ($i >= $period) {
                $windowSum -= (float) ($values[$i - $period] ?? 0.0);
            }

            if ($i >= $period - 1) {
                $result[$i] = $windowSum / $period;
            }
        }

        return $result;
    }
}
