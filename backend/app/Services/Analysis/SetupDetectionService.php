<?php

declare(strict_types=1);

namespace App\Services\Analysis;

use App\Contracts\SetupDetectorInterface;
use App\Enums\SetupCode;

class SetupDetectionService implements SetupDetectorInterface
{
    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $indicators
     * @param  array<string, mixed>  $marketContext
     * @return array<string, mixed>
     */
    public function detect(array $quotes, array $indicators, array $marketContext): array
    {
        if ($quotes === []) {
            return [
                'primary' => null,
                'candidates' => [],
                'flags' => ['Sem histórico de preços para detecção de setup'],
            ];
        }

        $current = (array) ($indicators['current'] ?? $indicators);
        $history = (array) ($indicators['history'] ?? [$current]);

        $candidates = [];
        $flags = [];

        if ($this->isPullbackEma21($quotes, $current, $history)) {
            $candidates[] = $this->setupArray(SetupCode::PULLBACK_EMA21, 0.82);
        }

        if ($this->isPullbackSma50($quotes, $current)) {
            $candidates[] = $this->setupArray(SetupCode::PULLBACK_SMA50, 0.78);
        }

        if ($this->isBreakout20d($quotes, $current)) {
            $candidates[] = $this->setupArray(SetupCode::BREAKOUT_20D, 0.86);
        }

        if ($this->isConsolidationBreak($quotes, $current)) {
            $candidates[] = $this->setupArray(SetupCode::CONSOLIDATION_BREAK, 0.79);
        }

        if ($this->isExtendedAsset($quotes, $current)) {
            $candidates[] = $this->setupArray(SetupCode::EXTENDED_ASSET, 0.90);
            $flags[] = 'Ativo com sinais de esticamento e timing desfavorável';
        }

        if ($this->isSidewaysNoEdge($current)) {
            $candidates[] = $this->setupArray(SetupCode::SIDEWAYS_NO_EDGE, 0.88);
            $flags[] = 'Estrutura lateral com médias emboladas e pouca vantagem estatística';
        }

        if ($this->isRiskTooHigh($quotes, $current)) {
            $candidates[] = $this->setupArray(SetupCode::RISK_TOO_HIGH, 0.93);
            $flags[] = 'Risco técnico acima do limite operacional de swing';
        }

        usort($candidates, static fn (array $a, array $b): int => ($b['priority'] <=> $a['priority']));

        $primary = $this->pickPrimarySetup($candidates);

        if (($marketContext['market_correction'] ?? false) === true) {
            $flags[] = 'Mercado em correção forte, privilegiar seletividade máxima';
        }

        return [
            'primary' => $primary,
            'candidates' => $candidates,
            'flags' => array_values(array_unique($flags)),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $current
     * @param  array<int, array<string, mixed>>  $history
     */
    private function isPullbackEma21(array $quotes, array $current, array $history): bool
    {
        $last = end($quotes);
        $prev = $quotes[count($quotes) - 2] ?? null;

        if ($prev === null) {
            return false;
        }

        $close = (float) ($last['close'] ?? 0.0);
        $sma50 = (float) ($current['sma_50'] ?? 0.0);
        $sma200 = (float) ($current['sma_200'] ?? 0.0);
        $ema21 = (float) ($current['ema_21'] ?? 0.0);
        $distance = (float) ($current['distance_ema_21'] ?? 999.0);

        $ema21Prev = (float) ($history[count($history) - 2]['ema_21'] ?? $ema21);

        $volume = (float) ($last['volume'] ?? 0.0);
        $avgVolume20 = (float) ($current['avg_volume_20'] ?? 0.0);

        $retracementInZone = abs($distance) <= 2.5;
        $smallerCorrectionVolume = $avgVolume20 > 0 && $volume <= $avgVolume20;
        $resumptionCandle = (float) ($last['close'] ?? 0.0) > (float) ($last['open'] ?? 0.0)
            && (float) ($last['close'] ?? 0.0) > (float) ($prev['close'] ?? 0.0);

        return $close > $sma50
            && $close > $sma200
            && $ema21 > $ema21Prev
            && $retracementInZone
            && $smallerCorrectionVolume
            && $resumptionCandle;
    }

    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $current
     */
    private function isPullbackSma50(array $quotes, array $current): bool
    {
        $last = end($quotes);
        $close = (float) ($last['close'] ?? 0.0);

        $sma50 = (float) ($current['sma_50'] ?? 0.0);
        $sma200 = (float) ($current['sma_200'] ?? 0.0);
        $ema21 = (float) ($current['ema_21'] ?? 0.0);

        if ($close <= 0 || $sma50 <= 0 || $sma200 <= 0) {
            return false;
        }

        $distanceSma50 = ((($close - $sma50) / $sma50) * 100);
        $riskPercentApprox = ((($close - min((float) ($last['low'] ?? $close), $sma50 * 0.995)) / $close) * 100);

        return $close > $sma200
            && $ema21 > $sma50
            && abs($distanceSma50) <= 2.8
            && $riskPercentApprox <= 4.0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $current
     */
    private function isBreakout20d(array $quotes, array $current): bool
    {
        if (count($quotes) < 21) {
            return false;
        }

        $last = end($quotes);
        $prior20 = array_slice($quotes, -21, 20);
        $max20 = max(array_map(static fn (array $row): float => (float) ($row['high'] ?? 0.0), $prior20));

        $close = (float) ($last['close'] ?? 0.0);
        $volume = (float) ($last['volume'] ?? 0.0);
        $avg20 = (float) ($current['avg_volume_20'] ?? 0.0);
        $distanceEma21 = abs((float) ($current['distance_ema_21'] ?? 0.0));

        return $close > $max20
            && $avg20 > 0
            && $volume > $avg20
            && $distanceEma21 <= 6.0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $current
     */
    private function isConsolidationBreak(array $quotes, array $current): bool
    {
        if (count($quotes) < 25) {
            return false;
        }

        $last = end($quotes);
        $window = array_slice($quotes, -9, 8);

        $high = max(array_map(static fn (array $row): float => (float) ($row['high'] ?? 0.0), $window));
        $low = min(array_map(static fn (array $row): float => (float) ($row['low'] ?? 0.0), $window));
        $compression = ((($high - $low) / max((float) ($last['close'] ?? 1), 1)) * 100);

        $priorTrend = ((float) ($last['close'] ?? 0.0)) > ((float) ($quotes[count($quotes) - 20]['close'] ?? 0.0));
        $breakout = (float) ($last['close'] ?? 0.0) > $high;
        $avg20 = (float) ($current['avg_volume_20'] ?? 0.0);
        $volume = (float) ($last['volume'] ?? 0.0);

        return $compression <= 4.0
            && $priorTrend
            && $breakout
            && $avg20 > 0
            && $volume >= $avg20;
    }

    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $current
     */
    private function isExtendedAsset(array $quotes, array $current): bool
    {
        $last = end($quotes);

        $distanceEma = (float) ($current['distance_ema_21'] ?? 0.0);
        $rsi = (float) ($current['rsi_14'] ?? 0.0);

        $candleExpansion = false;
        if (count($quotes) >= 2) {
            $prev = $quotes[count($quotes) - 2];
            $currRange = abs((float) ($last['high'] ?? 0.0) - (float) ($last['low'] ?? 0.0));
            $prevRange = abs((float) ($prev['high'] ?? 0.0) - (float) ($prev['low'] ?? 0.0));
            $candleExpansion = $prevRange > 0 && $currRange > ($prevRange * 1.7);
        }

        $nearResistance = false;
        $high20 = (float) ($current['high_20'] ?? 0.0);
        $close = (float) ($last['close'] ?? 0.0);
        if ($high20 > 0) {
            $nearResistance = (($high20 - $close) / $high20) * 100 <= 1.0;
        }

        $noRecentPullback = abs($distanceEma) > 5.0;

        $conditions = [
            $distanceEma > 6.0,
            $rsi > 72,
            $candleExpansion,
            $nearResistance,
            $noRecentPullback,
        ];

        return count(array_filter($conditions, static fn (bool $item): bool => $item)) >= 3;
    }

    /**
     * @param  array<string, mixed>  $current
     */
    private function isSidewaysNoEdge(array $current): bool
    {
        $ema9 = (float) ($current['ema_9'] ?? 0.0);
        $ema21 = (float) ($current['ema_21'] ?? 0.0);
        $sma50 = (float) ($current['sma_50'] ?? 0.0);
        $close = (float) ($current['close'] ?? 0.0);
        $adx = (float) ($current['adx_14'] ?? 0.0);

        if ($close <= 0.0) {
            return false;
        }

        $tangled = (abs($ema9 - $ema21) / $close) * 100 < 0.3
            && (abs($ema21 - $sma50) / $close) * 100 < 0.7;

        return $tangled && $adx < 18.0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $current
     */
    private function isRiskTooHigh(array $quotes, array $current): bool
    {
        $last = end($quotes);
        $close = (float) ($last['close'] ?? 0.0);
        $low = (float) ($last['low'] ?? 0.0);
        $atr = (float) ($current['atr_14'] ?? 0.0);

        if ($close <= 0.0) {
            return true;
        }

        $technicalStop = (($close - min($low, $close - ($atr * 1.2))) / $close) * 100;
        $atrPercent = ($atr / $close) * 100;
        $avgRange = (float) ($current['avg_range'] ?? 0.0);

        return $technicalStop > 4.0 || $atrPercent > 4.5 || $avgRange > 5.5;
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidates
     * @return array<string, mixed>|null
     */
    private function pickPrimarySetup(array $candidates): ?array
    {
        if ($candidates === []) {
            return null;
        }

        foreach ([
            SetupCode::RISK_TOO_HIGH->value,
            SetupCode::SIDEWAYS_NO_EDGE->value,
            SetupCode::EXTENDED_ASSET->value,
        ] as $blockingSetup) {
            foreach ($candidates as $candidate) {
                if (($candidate['code'] ?? null) === $blockingSetup) {
                    return $candidate;
                }
            }
        }

        return $candidates[0];
    }

    /**
     * @return array<string, mixed>
     */
    private function setupArray(SetupCode $setupCode, float $confidence): array
    {
        $priority = match ($setupCode) {
            SetupCode::RISK_TOO_HIGH => 110,
            SetupCode::SIDEWAYS_NO_EDGE => 100,
            SetupCode::EXTENDED_ASSET => 95,
            SetupCode::BREAKOUT_20D => 90,
            SetupCode::PULLBACK_EMA21 => 88,
            SetupCode::CONSOLIDATION_BREAK => 85,
            SetupCode::PULLBACK_SMA50 => 84,
        };

        return [
            'code' => $setupCode->value,
            'label' => $setupCode->label(),
            'confidence' => $confidence,
            'priority' => $priority,
        ];
    }
}
