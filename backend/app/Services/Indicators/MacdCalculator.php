<?php

declare(strict_types=1);

namespace App\Services\Indicators;

class MacdCalculator
{
    public function __construct(private readonly EmaCalculator $emaCalculator)
    {
    }

    /**
     * @param  array<int, float|int>  $closes
     * @return array{line: array<int, float|null>, signal: array<int, float|null>, histogram: array<int, float|null>}
     */
    public function calculate(array $closes): array
    {
        $ema12 = $this->emaCalculator->calculate($closes, 12);
        $ema26 = $this->emaCalculator->calculate($closes, 26);

        $line = [];

        foreach ($closes as $i => $_) {
            if ($ema12[$i] === null || $ema26[$i] === null) {
                $line[$i] = null;
                continue;
            }

            $line[$i] = $ema12[$i] - $ema26[$i];
        }

        $signal = $this->emaCalculator->calculate($line, 9);
        $histogram = [];

        foreach ($line as $i => $lineValue) {
            if ($lineValue === null || $signal[$i] === null) {
                $histogram[$i] = null;
                continue;
            }

            $histogram[$i] = $lineValue - $signal[$i];
        }

        return [
            'line' => $line,
            'signal' => $signal,
            'histogram' => $histogram,
        ];
    }
}
