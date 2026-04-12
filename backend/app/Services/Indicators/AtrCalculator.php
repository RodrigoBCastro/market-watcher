<?php

declare(strict_types=1);

namespace App\Services\Indicators;

class AtrCalculator
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
        $result = array_fill(0, $count, null);

        if ($count < $period) {
            return $result;
        }

        $trueRanges = [];

        for ($i = 0; $i < $count; $i++) {
            $high = (float) $highs[$i];
            $low = (float) $lows[$i];

            if ($i === 0) {
                $trueRanges[$i] = $high - $low;
                continue;
            }

            $prevClose = (float) $closes[$i - 1];

            $trueRanges[$i] = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose),
            );
        }

        $atr = array_sum(array_slice($trueRanges, 0, $period)) / $period;
        $result[$period - 1] = $atr;

        for ($i = $period; $i < $count; $i++) {
            $atr = (($atr * ($period - 1)) + $trueRanges[$i]) / $period;
            $result[$i] = $atr;
        }

        return $result;
    }
}
