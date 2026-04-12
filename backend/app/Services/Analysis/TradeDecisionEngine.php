<?php

declare(strict_types=1);

namespace App\Services\Analysis;

use App\Contracts\SetupDetectorInterface;
use App\Contracts\ScoreEngineInterface;
use App\Contracts\TradeDecisionEngineInterface;
use App\DTOs\TradeDecisionDTO;
use App\Enums\Recommendation;
use App\Enums\SetupCode;
use Carbon\CarbonImmutable;

class TradeDecisionEngine implements TradeDecisionEngineInterface
{
    public function __construct(
        private readonly SetupDetectorInterface $setupDetectionService,
        private readonly ScoreEngineInterface $scoreEngine,
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $indicators
     * @param  array<string, mixed>  $marketContext
     */
    public function evaluate(string $symbol, array $quotes, array $indicators, array $marketContext): TradeDecisionDTO
    {
        if ($quotes === []) {
            return new TradeDecisionDTO(
                symbol: $symbol,
                tradeDate: CarbonImmutable::now(),
                recommendation: Recommendation::AVOID->value,
                classification: 'Evitar',
                setupCode: null,
                setupLabel: null,
                entry: null,
                stop: null,
                target: null,
                riskPercent: null,
                rewardPercent: null,
                rrRatio: null,
                alerts: ['Sem histórico de preços para decisão'],
                rationale: 'Não foi possível avaliar o ativo sem dados históricos.',
                scoreBreakdown: [
                    'trend_score' => 0,
                    'moving_average_score' => 0,
                    'structure_score' => 0,
                    'momentum_score' => 0,
                    'volume_score' => 0,
                    'risk_score' => 0,
                    'market_context_score' => 0,
                    'final_score' => 0,
                    'classification' => 'Evitar',
                ],
            );
        }

        usort($quotes, static fn (array $a, array $b): int => strcmp((string) $a['trade_date'], (string) $b['trade_date']));

        $currentIndicator = (array) ($indicators['current'] ?? $indicators);
        $indicatorHistory = (array) ($indicators['history'] ?? [$currentIndicator]);

        $latestQuote = $quotes[count($quotes) - 1];

        $currentIndicator['close'] = (float) ($latestQuote['close'] ?? 0.0);

        $setupContext = $this->setupDetectionService->detect($quotes, [
            'current' => $currentIndicator,
            'history' => $indicatorHistory,
        ], $marketContext);

        $tradePlan = $this->buildTradePlan($quotes, $currentIndicator, $setupContext);
        $setupContext['trade_plan'] = $tradePlan;

        $scoreBreakdown = $this->scoreEngine->score($quotes, [
            'current' => $currentIndicator,
            'history' => $indicatorHistory,
        ], $setupContext, $marketContext);

        $alerts = (array) ($setupContext['flags'] ?? []);
        $alerts = [...$alerts, ...$this->vetoAlerts($tradePlan, $setupContext, $marketContext)];
        $alerts = array_values(array_unique(array_filter($alerts, static fn (string $item): bool => trim($item) !== '')));

        $recommendation = $this->resolveRecommendation(
            finalScore: $scoreBreakdown->finalScore,
            tradePlan: $tradePlan,
            setupContext: $setupContext,
            marketContext: $marketContext,
        );

        $rationale = $this->buildRationale(
            symbol: $symbol,
            currentIndicator: $currentIndicator,
            setupContext: $setupContext,
            recommendation: $recommendation,
            score: $scoreBreakdown->finalScore,
            alerts: $alerts,
        );

        $setupPrimary = $setupContext['primary'] ?? null;

        return new TradeDecisionDTO(
            symbol: strtoupper($symbol),
            tradeDate: CarbonImmutable::parse((string) ($latestQuote['trade_date'] ?? CarbonImmutable::now()->toDateString())),
            recommendation: $recommendation,
            classification: $scoreBreakdown->classification,
            setupCode: $setupPrimary['code'] ?? null,
            setupLabel: $setupPrimary['label'] ?? null,
            entry: $tradePlan['entry'],
            stop: $tradePlan['stop'],
            target: $tradePlan['target'],
            riskPercent: $tradePlan['risk_percent'],
            rewardPercent: $tradePlan['reward_percent'],
            rrRatio: $tradePlan['rr_ratio'],
            alerts: $alerts,
            rationale: $rationale,
            scoreBreakdown: $scoreBreakdown->toArray(),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @param  array<string, mixed>  $indicator
     * @param  array<string, mixed>  $setupContext
     * @return array{entry: float|null, stop: float|null, target: float|null, risk_percent: float|null, reward_percent: float|null, rr_ratio: float|null}
     */
    private function buildTradePlan(array $quotes, array $indicator, array $setupContext): array
    {
        $last = $quotes[count($quotes) - 1];
        $setupCode = (string) ($setupContext['primary']['code'] ?? '');

        $close = (float) ($last['close'] ?? 0.0);
        $high = (float) ($last['high'] ?? $close);
        $low = (float) ($last['low'] ?? $close);
        $atr = (float) ($indicator['atr_14'] ?? 0.0);

        if ($close <= 0.0) {
            return [
                'entry' => null,
                'stop' => null,
                'target' => null,
                'risk_percent' => null,
                'reward_percent' => null,
                'rr_ratio' => null,
            ];
        }

        $fallbackAtr = $atr > 0 ? $atr : ($close * 0.02);

        $entry = $close;
        $stop = $close - ($fallbackAtr * 1.1);
        $target = $close * 1.06;

        if ($setupCode === SetupCode::PULLBACK_EMA21->value) {
            $ema21 = (float) ($indicator['ema_21'] ?? $close);
            $entry = $high * 1.001;
            $stop = min($low, $ema21) - ($fallbackAtr * 0.30);
            $target = max($entry * 1.06, (float) ($indicator['high_20'] ?? ($entry * 1.06)));
        } elseif ($setupCode === SetupCode::PULLBACK_SMA50->value) {
            $sma50 = (float) ($indicator['sma_50'] ?? $close);
            $entry = $high * 1.001;
            $stop = min($low, $sma50) - ($fallbackAtr * 0.35);
            $target = max($entry * 1.06, (float) ($indicator['high_20'] ?? ($entry * 1.06)));
        } elseif (in_array($setupCode, [SetupCode::BREAKOUT_20D->value, SetupCode::CONSOLIDATION_BREAK->value], true)) {
            $recentHigh = max(array_map(static fn (array $row): float => (float) ($row['high'] ?? 0.0), array_slice($quotes, -20)));
            $recentLow = min(array_map(static fn (array $row): float => (float) ($row['low'] ?? 0.0), array_slice($quotes, -5)));
            $entry = max($high, $recentHigh) * 1.001;
            $stop = $recentLow - ($fallbackAtr * 0.40);
            $target = max($entry * 1.06, (float) ($indicator['high_50'] ?? ($entry * 1.08)));
        }

        $entry = round($entry, 4);
        $stop = round($stop, 4);
        $target = round(max($target, $entry * 1.06), 4);

        $riskPercent = $entry > 0 ? (($entry - $stop) / $entry) * 100 : null;
        $rewardPercent = $entry > 0 ? (($target - $entry) / $entry) * 100 : null;
        $rrRatio = ($riskPercent !== null && $riskPercent > 0 && $rewardPercent !== null)
            ? $rewardPercent / $riskPercent
            : null;

        return [
            'entry' => $entry,
            'stop' => $stop,
            'target' => $target,
            'risk_percent' => $riskPercent !== null ? round($riskPercent, 4) : null,
            'reward_percent' => $rewardPercent !== null ? round($rewardPercent, 4) : null,
            'rr_ratio' => $rrRatio !== null ? round($rrRatio, 4) : null,
        ];
    }

    /**
     * @param  array{entry: float|null, stop: float|null, target: float|null, risk_percent: float|null, reward_percent: float|null, rr_ratio: float|null}  $tradePlan
     * @param  array<string, mixed>  $setupContext
     * @param  array<string, mixed>  $marketContext
     */
    private function resolveRecommendation(float $finalScore, array $tradePlan, array $setupContext, array $marketContext): string
    {
        $setupCode = (string) ($setupContext['primary']['code'] ?? '');

        $riskPercent = (float) ($tradePlan['risk_percent'] ?? 999.0);
        $rewardPercent = (float) ($tradePlan['reward_percent'] ?? 0.0);
        $rrRatio = (float) ($tradePlan['rr_ratio'] ?? 0.0);

        $blockedSetup = in_array($setupCode, [
            SetupCode::EXTENDED_ASSET->value,
            SetupCode::SIDEWAYS_NO_EDGE->value,
            SetupCode::RISK_TOO_HIGH->value,
        ], true);

        $marketCorrection = (bool) ($marketContext['market_correction'] ?? false);
        $marketBias = (string) ($marketContext['market_bias'] ?? 'neutro');
        $marketAllowed = in_array($marketBias, ['favoravel', 'cautelosamente_favoravel', 'neutro'], true) && ! $marketCorrection;

        $hardVeto = $riskPercent > 4.0 || $rewardPercent < 6.0 || $rrRatio < 1.5 || $blockedSetup;

        if ($finalScore >= 70 && ! $hardVeto && $marketAllowed && $setupCode !== '') {
            return Recommendation::ENTER->value;
        }

        if ($finalScore >= 55 || ($finalScore >= 70 && $hardVeto)) {
            return Recommendation::WATCH->value;
        }

        return Recommendation::AVOID->value;
    }

    /**
     * @param  array{entry: float|null, stop: float|null, target: float|null, risk_percent: float|null, reward_percent: float|null, rr_ratio: float|null}  $tradePlan
     * @param  array<string, mixed>  $setupContext
     * @param  array<string, mixed>  $marketContext
     * @return array<int, string>
     */
    private function vetoAlerts(array $tradePlan, array $setupContext, array $marketContext): array
    {
        $alerts = [];

        if (($tradePlan['risk_percent'] ?? 999) > 4) {
            $alerts[] = 'Stop técnico acima de 4% invalida entrada imediata';
        }

        if (($tradePlan['reward_percent'] ?? 0) < 6) {
            $alerts[] = 'Alvo projetado abaixo do mínimo operacional de 6%';
        }

        if (($tradePlan['rr_ratio'] ?? 0) < 1.5) {
            $alerts[] = 'Relação risco/retorno abaixo de 1.5';
        }

        $setupCode = (string) ($setupContext['primary']['code'] ?? '');
        if ($setupCode === SetupCode::EXTENDED_ASSET->value) {
            $alerts[] = 'Ativo esticado: timing piorado apesar da força direcional';
        }

        if (($marketContext['market_correction'] ?? false) === true) {
            $alerts[] = 'Mercado em correção forte, reduzir agressividade operacional';
        }

        return $alerts;
    }

    /**
     * @param  array<string, mixed>  $currentIndicator
     * @param  array<string, mixed>  $setupContext
     * @param  array<int, string>  $alerts
     */
    private function buildRationale(
        string $symbol,
        array $currentIndicator,
        array $setupContext,
        string $recommendation,
        float $score,
        array $alerts,
    ): string {
        $setupLabel = (string) ($setupContext['primary']['label'] ?? 'Sem setup de alta convicção');
        $ema21 = round((float) ($currentIndicator['ema_21'] ?? 0.0), 2);
        $sma50 = round((float) ($currentIndicator['sma_50'] ?? 0.0), 2);
        $sma200 = round((float) ($currentIndicator['sma_200'] ?? 0.0), 2);

        $base = "{$symbol} avaliado com score {$score}. Estrutura principal: {$setupLabel}. "
            ."Referências de tendência EMA21={$ema21}, SMA50={$sma50}, SMA200={$sma200}. ";

        if ($alerts !== []) {
            return $base.'Alertas críticos: '.implode('; ', array_slice($alerts, 0, 3)).". Recomendação final: {$recommendation}.";
        }

        return $base."Condições sem veto crítico. Recomendação final: {$recommendation}.";
    }
}
