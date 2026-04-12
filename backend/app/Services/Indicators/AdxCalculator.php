<?php

declare(strict_types=1);

namespace App\Services\Indicators;

class AdxCalculator
{
    /**
     * @param  array<int, float|int>  $highs
     * @param  array<int, float|int>  $lows
     * @param  array<int, float|int>  $closes
     * @return array<int, float|null>
     */
    public function calculate(array $highs, array $lows, array $closes, int $period = 14): array
    {
        $count = count($closes);
        $adx = array_fill(0, $count, null);

        if ($count <= ($period * 2)) {
            return $adx;
        }

        $tr = [];
        $plusDm = [];
        $minusDm = [];

        for ($i = 1; $i < $count; $i++) {
            $upMove = (float) $highs[$i] - (float) $highs[$i - 1];
            $downMove = (float) $lows[$i - 1] - (float) $lows[$i];

            $plusDm[$i] = ($upMove > $downMove && $upMove > 0) ? $upMove : 0.0;
            $minusDm[$i] = ($downMove > $upMove && $downMove > 0) ? $downMove : 0.0;

            $tr[$i] = max(
                (float) $highs[$i] - (float) $lows[$i],
                abs((float) $highs[$i] - (float) $closes[$i - 1]),
                abs((float) $lows[$i] - (float) $closes[$i - 1]),
            );
        }

        $tr14 = array_sum(array_slice($tr, 1, $period));
        $plusDm14 = array_sum(array_slice($plusDm, 1, $period));
        $minusDm14 = array_sum(array_slice($minusDm, 1, $period));

        $dx = [];

        for ($i = $period; $i < $count; $i++) {
            if ($i > $period) {
                $tr14 = $tr14 - ($tr14 / $period) + ($tr[$i] ?? 0.0);
                $plusDm14 = $plusDm14 - ($plusDm14 / $period) + ($plusDm[$i] ?? 0.0);
                $minusDm14 = $minusDm14 - ($minusDm14 / $period) + ($minusDm[$i] ?? 0.0);
            }

            if ($tr14 == 0.0) {
                $dx[$i] = 0.0;
                continue;
            }

            $plusDi = 100 * ($plusDm14 / $tr14);
            $minusDi = 100 * ($minusDm14 / $tr14);
            $sum = $plusDi + $minusDi;

            $dx[$i] = $sum == 0.0 ? 0.0 : 100 * (abs($plusDi - $minusDi) / $sum);
        }

        $start = $period * 2 - 1;

        if (! isset($dx[$start])) {
            return $adx;
        }

        $firstSlice = [];

        for ($i = $period; $i <= $start; $i++) {
            if (isset($dx[$i])) {
                $firstSlice[] = $dx[$i];
            }
        }

        if (count($firstSlice) < $period) {
            return $adx;
        }

        $adxValue = array_sum($firstSlice) / $period;
        $adx[$start] = $adxValue;

        for ($i = $start + 1; $i < $count; $i++) {
            if (! isset($dx[$i])) {
                continue;
            }

            $adxValue = (($adxValue * ($period - 1)) + $dx[$i]) / $period;
            $adx[$i] = $adxValue;
        }

        return $adx;
    }
}
