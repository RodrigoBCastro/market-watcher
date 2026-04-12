<?php

declare(strict_types=1);

namespace App\Services\Indicators;

class RsiCalculator
{
    /**
     * @param  array<int, float|int>  $closes
     * @return array<int, float|null>
     */
    public function calculate(array $closes, int $period = 14): array
    {
        $count = count($closes);
        $result = array_fill(0, $count, null);

        if ($count <= $period) {
            return $result;
        }

        $gains = [];
        $losses = [];

        for ($i = 1; $i < $count; $i++) {
            $change = (float) $closes[$i] - (float) $closes[$i - 1];
            $gains[] = $change > 0 ? $change : 0.0;
            $losses[] = $change < 0 ? abs($change) : 0.0;
        }

        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        $result[$period] = $this->toRsi($avgGain, $avgLoss);

        for ($i = $period + 1; $i < $count; $i++) {
            $gain = $gains[$i - 1];
            $loss = $losses[$i - 1];

            $avgGain = (($avgGain * ($period - 1)) + $gain) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $loss) / $period;

            $result[$i] = $this->toRsi($avgGain, $avgLoss);
        }

        return $result;
    }

    private function toRsi(float $avgGain, float $avgLoss): float
    {
        if ($avgLoss == 0.0) {
            return 100.0;
        }

        $rs = $avgGain / $avgLoss;

        return 100 - (100 / (1 + $rs));
    }
}
