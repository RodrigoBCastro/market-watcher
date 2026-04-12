<?php

declare(strict_types=1);

namespace App\Services\Indicators;

use App\Contracts\IndicatorCalculatorInterface;

class IndicatorPipeline implements IndicatorCalculatorInterface
{
    public function __construct(
        private readonly SmaCalculator $smaCalculator,
        private readonly EmaCalculator $emaCalculator,
        private readonly RsiCalculator $rsiCalculator,
        private readonly MacdCalculator $macdCalculator,
        private readonly AtrCalculator $atrCalculator,
        private readonly BollingerCalculator $bollingerCalculator,
        private readonly AdxCalculator $adxCalculator,
        private readonly StochasticCalculator $stochasticCalculator,
        private readonly RocCalculator $rocCalculator,
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @return array<int, array<string, mixed>>
     */
    public function calculate(array $quotes): array
    {
        if ($quotes === []) {
            return [];
        }

        usort($quotes, static fn (array $a, array $b): int => strcmp((string) $a['trade_date'], (string) $b['trade_date']));

        $closes = array_map(static fn (array $row): float => (float) $row['close'], $quotes);
        $highs = array_map(static fn (array $row): float => (float) $row['high'], $quotes);
        $lows = array_map(static fn (array $row): float => (float) $row['low'], $quotes);
        $volumes = array_map(static fn (array $row): float => (float) $row['volume'], $quotes);

        $sma = [];
        foreach ([5, 9, 10, 20, 21, 30, 40, 50, 72, 80, 100, 120, 150, 200] as $period) {
            $sma[$period] = $this->smaCalculator->calculate($closes, $period);
        }

        $ema = [];
        foreach ([5, 8, 9, 12, 17, 20, 21, 26, 34, 50, 72, 100, 144, 200] as $period) {
            $ema[$period] = $this->emaCalculator->calculate($closes, $period);
        }

        $rsi7 = $this->rsiCalculator->calculate($closes, 7);
        $rsi14 = $this->rsiCalculator->calculate($closes, 14);
        $macd = $this->macdCalculator->calculate($closes);
        $atr14 = $this->atrCalculator->calculate($highs, $lows, $closes, 14);
        $bollinger = $this->bollingerCalculator->calculate($closes, 20, 2.0);
        $adx14 = $this->adxCalculator->calculate($highs, $lows, $closes, 14);
        $stochastic = $this->stochasticCalculator->calculate($highs, $lows, $closes, 14, 3);
        $roc = $this->rocCalculator->calculate($closes, 12);

        $avgVolume20 = $this->smaCalculator->calculate($volumes, 20);
        $change5 = $this->percentChange($closes, 5);
        $change10 = $this->percentChange($closes, 10);
        $change20 = $this->percentChange($closes, 20);
        $high20 = $this->rollingHigh($highs, 20);
        $low20 = $this->rollingLow($lows, 20);
        $high50 = $this->rollingHigh($highs, 50);
        $low50 = $this->rollingLow($lows, 50);
        $high200 = $this->rollingHigh($highs, 200);
        $low200 = $this->rollingLow($lows, 200);
        $volatility20 = $this->recentVolatility($closes, 20);
        $avgRange20 = $this->averageRange($highs, $lows, $closes, 20);

        $rows = [];

        foreach ($quotes as $i => $quote) {
            $close = (float) $quote['close'];

            $rows[] = [
                'trade_date' => $quote['trade_date'],
                'sma_5' => $sma[5][$i],
                'sma_9' => $sma[9][$i],
                'sma_10' => $sma[10][$i],
                'sma_20' => $sma[20][$i],
                'sma_21' => $sma[21][$i],
                'sma_30' => $sma[30][$i],
                'sma_40' => $sma[40][$i],
                'sma_50' => $sma[50][$i],
                'sma_72' => $sma[72][$i],
                'sma_80' => $sma[80][$i],
                'sma_100' => $sma[100][$i],
                'sma_120' => $sma[120][$i],
                'sma_150' => $sma[150][$i],
                'sma_200' => $sma[200][$i],
                'ema_5' => $ema[5][$i],
                'ema_8' => $ema[8][$i],
                'ema_9' => $ema[9][$i],
                'ema_12' => $ema[12][$i],
                'ema_17' => $ema[17][$i],
                'ema_20' => $ema[20][$i],
                'ema_21' => $ema[21][$i],
                'ema_26' => $ema[26][$i],
                'ema_34' => $ema[34][$i],
                'ema_50' => $ema[50][$i],
                'ema_72' => $ema[72][$i],
                'ema_100' => $ema[100][$i],
                'ema_144' => $ema[144][$i],
                'ema_200' => $ema[200][$i],
                'rsi_7' => $rsi7[$i],
                'rsi_14' => $rsi14[$i],
                'macd_line' => $macd['line'][$i],
                'macd_signal' => $macd['signal'][$i],
                'macd_histogram' => $macd['histogram'][$i],
                'atr_14' => $atr14[$i],
                'bollinger_mid' => $bollinger['mid'][$i],
                'bollinger_upper' => $bollinger['upper'][$i],
                'bollinger_lower' => $bollinger['lower'][$i],
                'adx_14' => $adx14[$i],
                'stochastic_k' => $stochastic['k'][$i],
                'stochastic_d' => $stochastic['d'][$i],
                'roc' => $roc[$i],
                'avg_volume_20' => $avgVolume20[$i],
                'change_5' => $change5[$i],
                'change_10' => $change10[$i],
                'change_20' => $change20[$i],
                'high_20' => $high20[$i],
                'low_20' => $low20[$i],
                'high_50' => $high50[$i],
                'low_50' => $low50[$i],
                'high_200' => $high200[$i],
                'low_200' => $low200[$i],
                'distance_ema_21' => $this->distancePercent($close, $ema[21][$i]),
                'distance_sma_50' => $this->distancePercent($close, $sma[50][$i]),
                'distance_sma_200' => $this->distancePercent($close, $sma[200][$i]),
                'recent_volatility' => $volatility20[$i],
                'avg_range' => $avgRange20[$i],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, float>  $values
     * @return array<int, float|null>
     */
    private function percentChange(array $values, int $period): array
    {
        $result = array_fill(0, count($values), null);

        for ($i = $period; $i < count($values); $i++) {
            if ($values[$i - $period] == 0.0) {
                continue;
            }

            $result[$i] = (($values[$i] - $values[$i - $period]) / $values[$i - $period]) * 100;
        }

        return $result;
    }

    /**
     * @param  array<int, float>  $values
     * @return array<int, float|null>
     */
    private function rollingHigh(array $values, int $period): array
    {
        $result = array_fill(0, count($values), null);

        for ($i = $period - 1; $i < count($values); $i++) {
            $result[$i] = max(array_slice($values, $i - $period + 1, $period));
        }

        return $result;
    }

    /**
     * @param  array<int, float>  $values
     * @return array<int, float|null>
     */
    private function rollingLow(array $values, int $period): array
    {
        $result = array_fill(0, count($values), null);

        for ($i = $period - 1; $i < count($values); $i++) {
            $result[$i] = min(array_slice($values, $i - $period + 1, $period));
        }

        return $result;
    }

    private function distancePercent(float $price, ?float $base): ?float
    {
        if ($base === null || $base == 0.0) {
            return null;
        }

        return (($price - $base) / $base) * 100;
    }

    /**
     * @param  array<int, float>  $closes
     * @return array<int, float|null>
     */
    private function recentVolatility(array $closes, int $period): array
    {
        $returns = array_fill(0, count($closes), null);

        for ($i = 1; $i < count($closes); $i++) {
            if ($closes[$i - 1] == 0.0) {
                continue;
            }

            $returns[$i] = (($closes[$i] - $closes[$i - 1]) / $closes[$i - 1]) * 100;
        }

        $result = array_fill(0, count($closes), null);

        for ($i = $period; $i < count($returns); $i++) {
            $slice = array_slice($returns, $i - $period + 1, $period);
            $valid = array_values(array_filter($slice, static fn ($value): bool => $value !== null));

            if (count($valid) < $period) {
                continue;
            }

            $mean = array_sum($valid) / count($valid);
            $variance = 0.0;

            foreach ($valid as $value) {
                $variance += ($value - $mean) ** 2;
            }

            $result[$i] = sqrt($variance / count($valid));
        }

        return $result;
    }

    /**
     * @param  array<int, float>  $highs
     * @param  array<int, float>  $lows
     * @param  array<int, float>  $closes
     * @return array<int, float|null>
     */
    private function averageRange(array $highs, array $lows, array $closes, int $period): array
    {
        $ranges = array_fill(0, count($closes), null);

        foreach ($closes as $i => $close) {
            if ($close == 0.0) {
                continue;
            }

            $ranges[$i] = (($highs[$i] - $lows[$i]) / $close) * 100;
        }

        $result = array_fill(0, count($closes), null);

        for ($i = $period - 1; $i < count($ranges); $i++) {
            $slice = array_slice($ranges, $i - $period + 1, $period);
            $valid = array_values(array_filter($slice, static fn ($value): bool => $value !== null));

            if (count($valid) < $period) {
                continue;
            }

            $result[$i] = array_sum($valid) / count($valid);
        }

        return $result;
    }
}
