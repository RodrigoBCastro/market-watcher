<?php

declare(strict_types=1);

namespace App\Services\Indicators;

class EmaCalculator
{
    /**
     * @param  array<int, float|int|null>  $values
     * @return array<int, float|null>
     */
    public function calculate(array $values, int $period): array
    {
        $result = array_fill(0, count($values), null);

        if ($period <= 0) {
            return $result;
        }

        $validIndexes = [];
        $validValues = [];

        foreach ($values as $index => $value) {
            if ($value === null) {
                continue;
            }

            $validIndexes[] = $index;
            $validValues[] = (float) $value;
        }

        if (count($validValues) < $period) {
            return $result;
        }

        $multiplier = 2 / ($period + 1);
        $ema = array_sum(array_slice($validValues, 0, $period)) / $period;
        $result[$validIndexes[$period - 1]] = $ema;

        for ($i = $period; $i < count($validValues); $i++) {
            $ema = ($validValues[$i] * $multiplier) + ($ema * (1 - $multiplier));
            $result[$validIndexes[$i]] = $ema;
        }

        return $result;
    }
}
