<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\MarketRegimeServiceInterface;
use App\Contracts\PortfolioRiskServiceInterface;
use App\Contracts\PortfolioServiceInterface;
use App\Contracts\TradingAlertServiceInterface;
use App\Enums\AlertSeverity;
use App\Enums\TradingAlertType;
use App\Models\AssetAnalysisScore;
use App\Models\SetupMetric;
use App\Models\TradingAlert;
use Carbon\CarbonImmutable;

class TradingAlertService implements TradingAlertServiceInterface
{
    public function __construct(
        private readonly PortfolioServiceInterface $portfolioService,
        private readonly PortfolioRiskServiceInterface $portfolioRiskService,
        private readonly MarketRegimeServiceInterface $marketRegimeService,
    ) {
    }

    public function listForUser(int $userId, bool $onlyUnread = false, int $limit = 100): array
    {
        return TradingAlert::query()
            ->where('user_id', $userId)
            ->when($onlyUnread, static function ($query): void {
                $query->where('is_read', false);
            })
            ->orderByDesc('created_at')
            ->limit(max(1, min($limit, 300)))
            ->get()
            ->map(static fn (TradingAlert $alert): array => [
                'id' => (int) $alert->id,
                'alert_type' => $alert->alert_type,
                'severity' => $alert->severity,
                'title' => $alert->title,
                'message' => $alert->message,
                'payload' => $alert->payload,
                'is_read' => (bool) $alert->is_read,
                'created_at' => $alert->created_at?->toIso8601String(),
                'updated_at' => $alert->updated_at?->toIso8601String(),
            ])
            ->all();
    }

    public function markAsRead(int $userId, int $alertId): array
    {
        $alert = TradingAlert::query()
            ->where('user_id', $userId)
            ->findOrFail($alertId);

        $alert->update(['is_read' => true]);

        return [
            'id' => (int) $alert->id,
            'is_read' => true,
            'updated_at' => $alert->updated_at?->toIso8601String(),
        ];
    }

    public function refreshForUser(int $userId): int
    {
        $this->portfolioService->refreshMarkToMarket($userId);

        $openPositions = $this->portfolioService->listOpen($userId);
        $risk = $this->portfolioRiskService->summary($userId);
        $regime = $this->marketRegimeService->current();

        $created = 0;

        foreach ($openPositions as $position) {
            $ticker = (string) ($position['ticker'] ?? '');
            $positionId = (int) ($position['id'] ?? 0);

            $distanceStop = $position['distance_to_stop_percent'] ?? null;
            if ($distanceStop !== null && (float) $distanceStop <= (float) config('market.alerts.near_stop_threshold_percent', 1.5)) {
                $created += $this->createAlertOnce(
                    userId: $userId,
                    alertType: TradingAlertType::CALL_NEAR_STOP->value,
                    severity: AlertSeverity::WARNING->value,
                    title: "{$ticker} próximo do stop",
                    message: 'A posição está próxima do nível de stop técnico.',
                    payload: [
                        'portfolio_position_id' => $positionId,
                        'distance_to_stop_percent' => $distanceStop,
                    ],
                );
            }

            $distanceTarget = $position['distance_to_target_percent'] ?? null;
            if ($distanceTarget !== null && (float) $distanceTarget <= (float) config('market.alerts.near_target_threshold_percent', 2.0)) {
                $created += $this->createAlertOnce(
                    userId: $userId,
                    alertType: TradingAlertType::CALL_NEAR_TARGET->value,
                    severity: AlertSeverity::INFO->value,
                    title: "{$ticker} próximo do alvo",
                    message: 'A posição está próxima do alvo projetado.',
                    payload: [
                        'portfolio_position_id' => $positionId,
                        'distance_to_target_percent' => $distanceTarget,
                    ],
                );
            }

            if ($distanceStop !== null && (float) $distanceStop < 0.0) {
                $created += $this->createAlertOnce(
                    userId: $userId,
                    alertType: TradingAlertType::CALL_INVALIDATED->value,
                    severity: AlertSeverity::CRITICAL->value,
                    title: "{$ticker} abaixo do stop",
                    message: 'A posição ultrapassou o nível de stop e pode ter sido invalidada.',
                    payload: [
                        'portfolio_position_id' => $positionId,
                        'distance_to_stop_percent' => $distanceStop,
                    ],
                );
            }

            $confidenceScore = isset($position['confidence_score']) ? (float) $position['confidence_score'] : null;
            if ($confidenceScore !== null) {
                $latestScore = AssetAnalysisScore::query()
                    ->whereHas('monitoredAsset', static function ($query) use ($ticker): void {
                        $query->where('ticker', $ticker);
                    })
                    ->orderByDesc('trade_date')
                    ->value('final_score');

                if ($latestScore !== null) {
                    $drop = $confidenceScore - (float) $latestScore;

                    if ($drop >= (float) config('market.alerts.confidence_drop_threshold', 12.0)) {
                        $created += $this->createAlertOnce(
                            userId: $userId,
                            alertType: TradingAlertType::CONFIDENCE_DROPPING->value,
                            severity: AlertSeverity::WARNING->value,
                            title: "{$ticker} com queda de convicção",
                            message: 'A convicção operacional caiu de forma relevante para esta posição.',
                            payload: [
                                'portfolio_position_id' => $positionId,
                                'confidence_drop' => round($drop, 4),
                            ],
                        );
                    }
                }
            }

            $setupCode = $position['trade_call']['setup_code'] ?? null;

            if (is_string($setupCode) && $setupCode !== '') {
                $metric = SetupMetric::query()->where('setup_code', $setupCode)->first();

                if ($metric !== null && ((float) $metric->expectancy <= 0.0 || (float) $metric->winrate <= 50.0)) {
                    $created += $this->createAlertOnce(
                        userId: $userId,
                        alertType: TradingAlertType::SETUP_DETERIORATING->value,
                        severity: AlertSeverity::WARNING->value,
                        title: "Setup {$setupCode} em deterioração",
                        message: 'O setup da posição perdeu edge estatístico recentemente.',
                        payload: [
                            'portfolio_position_id' => $positionId,
                            'setup_code' => $setupCode,
                            'expectancy' => (float) $metric->expectancy,
                            'winrate' => (float) $metric->winrate,
                        ],
                    );
                }
            }
        }

        if ((bool) ($risk['blocked'] ?? false)) {
            $created += $this->createAlertOnce(
                userId: $userId,
                alertType: TradingAlertType::PORTFOLIO_RISK_LIMIT->value,
                severity: AlertSeverity::CRITICAL->value,
                title: 'Carteira acima dos limites de risco',
                message: 'A carteira ultrapassou parâmetros de risco e requer ajuste.',
                payload: [
                    'violations' => $risk['violations'] ?? [],
                    'open_risk_percent' => $risk['open_risk_percent'] ?? null,
                ],
            );
        }

        $highCorrelationPairs = (array) ($risk['correlations']['pairs'] ?? []);

        if ($highCorrelationPairs !== []) {
            $created += $this->createAlertOnce(
                userId: $userId,
                alertType: TradingAlertType::HIGH_CORRELATION->value,
                severity: AlertSeverity::WARNING->value,
                title: 'Correlação elevada entre posições',
                message: 'Foram identificadas posições com correlação alta na carteira.',
                payload: [
                    'pairs' => array_slice($highCorrelationPairs, 0, 10),
                ],
            );
        }

        $overSector = (array) ($risk['exposure']['over_sector_limit'] ?? []);

        if ($overSector !== []) {
            $created += $this->createAlertOnce(
                userId: $userId,
                alertType: TradingAlertType::SECTOR_CONCENTRATION->value,
                severity: AlertSeverity::WARNING->value,
                title: 'Concentração setorial elevada',
                message: 'Um ou mais setores estão acima do limite configurado.',
                payload: [
                    'sectors' => $overSector,
                ],
            );
        }

        if (in_array($regime->regime, ['correction', 'bear', 'high_volatility'], true)) {
            $created += $this->createAlertOnce(
                userId: $userId,
                alertType: TradingAlertType::REGIME_WORSENING->value,
                severity: AlertSeverity::INFO->value,
                title: 'Regime de mercado deteriorado',
                message: 'O regime de mercado atual exige postura mais defensiva.',
                payload: [
                    'regime' => $regime->regime,
                    'context_score' => $regime->contextScore,
                ],
            );
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createAlertOnce(
        int $userId,
        string $alertType,
        string $severity,
        string $title,
        string $message,
        array $payload,
    ): int {
        $signature = hash('sha1', json_encode([$alertType, $title, $payload], JSON_THROW_ON_ERROR));

        $todayStart = CarbonImmutable::today()->startOfDay();

        $alreadyExists = TradingAlert::query()
            ->where('user_id', $userId)
            ->where('alert_type', $alertType)
            ->where('title', $title)
            ->where('created_at', '>=', $todayStart)
            ->where('payload->signature', $signature)
            ->exists();

        if ($alreadyExists) {
            return 0;
        }

        TradingAlert::query()->create([
            'user_id' => $userId,
            'alert_type' => $alertType,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'payload' => [
                ...$payload,
                'signature' => $signature,
            ],
            'is_read' => false,
        ]);

        return 1;
    }
}
