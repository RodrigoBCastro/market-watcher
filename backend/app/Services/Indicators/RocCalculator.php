<?php

declare(strict_types=1);

namespace App\Services\Indicators;

class RocCalculator
{
    /**
     * @param  array<int, float|int>  $closes
     * @return array<int, float|null>
     */
    public function calculate(array $closes, int $period = 12): array
    {
        $count = count($closes);
        $result = array_fill(0, $count, null);

        if ($count <= $period) {
            return $result;
        }

        for ($i = $period; $i < $count; $i++) {
            $base = (float) $closes[$i - $period];

            if ($base == 0.0) {
                continue;
            }

            $result[$i] = (((float) $closes[$i] - $base) / $base) * 100;
        }

        return $result;
    }
}
