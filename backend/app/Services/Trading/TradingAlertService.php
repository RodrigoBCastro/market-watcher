<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\AssetAnalysisScoreRepositoryInterface;
use App\Contracts\MarketRegimeServiceInterface;
use App\Contracts\PortfolioRiskServiceInterface;
use App\Contracts\PortfolioServiceInterface;
use App\Contracts\SetupMetricRepositoryInterface;
use App\Contracts\TradingAlertRepositoryInterface;
use App\Contracts\TradingAlertServiceInterface;
use App\Enums\AlertSeverity;
use App\Enums\TradingAlertType;

class TradingAlertService implements TradingAlertServiceInterface
{
    public function __construct(
        private readonly PortfolioServiceInterface              $portfolioService,
        private readonly PortfolioRiskServiceInterface          $portfolioRiskService,
        private readonly MarketRegimeServiceInterface           $marketRegimeService,
        private readonly TradingAlertRepositoryInterface        $tradingAlertRepository,
        private readonly AssetAnalysisScoreRepositoryInterface  $assetAnalysisScoreRepository,
        private readonly SetupMetricRepositoryInterface         $setupMetricRepository,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(int $userId, bool $onlyUnread = false, int $limit = 100): array
    {
        return $this->tradingAlertRepository
            ->listByUser($userId, $limit, $onlyUnread)
            ->map(static fn ($alert): array => [
                'id'         => (int) $alert->id,
                'alert_type' => $alert->alert_type,
                'severity'   => $alert->severity,
                'title'      => $alert->title,
                'message'    => $alert->message,
                'payload'    => $alert->payload,
                'is_read'    => (bool) $alert->is_read,
                'created_at' => $alert->created_at?->toIso8601String(),
                'updated_at' => $alert->updated_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function markAsRead(int $userId, int $alertId): array
    {
        $alert = $this->tradingAlertRepository->findByIdForUser($alertId, $userId);

        if ($alert === null) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        $this->tradingAlertRepository->markRead($alert);

        return [
            'id'         => (int) $alert->id,
            'is_read'    => true,
            'updated_at' => $alert->updated_at?->toIso8601String(),
        ];
    }

    public function refreshForUser(int $userId): int
    {
        $this->portfolioService->refreshMarkToMarket($userId);

        $positions = $this->portfolioService->listOpen($userId);
        $risk      = $this->portfolioRiskService->summary($userId);
        $regime    = $this->marketRegimeService->current();

        $count = 0;

        foreach ($positions as $position) {
            $ticker     = (string) ($position['ticker'] ?? '');
            $positionId = (int) ($position['id'] ?? 0);

            $distanceToStop = $position['distance_to_stop_percent'] ?? null;

            if ($distanceToStop !== null && (float) $distanceToStop <= (float) config('market.alerts.near_stop_threshold_percent', 1.5)) {
                $count += $this->createAlertOnce(
                    userId:    $userId,
                    alertType: TradingAlertType::CALL_NEAR_STOP->value,
                    severity:  AlertSeverity::WARNING->value,
                    title:     "{$ticker} próximo do stop",
                    message:   'A posição está próxima do nível de stop técnico.',
                    payload:   [
                        'portfolio_position_id'    => $positionId,
                        'distance_to_stop_percent' => $distanceToStop,
                    ],
                );
            }

            $distanceToTarget = $position['distance_to_target_percent'] ?? null;

            if ($distanceToTarget !== null && (float) $distanceToTarget <= (float) config('market.alerts.near_target_threshold_percent', 2.0)) {
                $count += $this->createAlertOnce(
                    userId:    $userId,
                    alertType: TradingAlertType::CALL_NEAR_TARGET->value,
                    severity:  AlertSeverity::INFO->value,
                    title:     "{$ticker} próximo do alvo",
                    message:   'A posição está próxima do alvo projetado.',
                    payload:   [
                        'portfolio_position_id'       => $positionId,
                        'distance_to_target_percent'  => $distanceToTarget,
                    ],
                );
            }

            if ($distanceToStop !== null && (float) $distanceToStop < 0.0) {
                $count += $this->createAlertOnce(
                    userId:    $userId,
                    alertType: TradingAlertType::CALL_INVALIDATED->value,
                    severity:  AlertSeverity::CRITICAL->value,
                    title:     "{$ticker} abaixo do stop",
                    message:   'A posição ultrapassou o nível de stop e pode ter sido invalidada.',
                    payload:   [
                        'portfolio_position_id'    => $positionId,
                        'distance_to_stop_percent' => $distanceToStop,
                    ],
                );
            }

            $positionConfidenceScore = isset($position['confidence_score']) ? (float) $position['confidence_score'] : null;

            if ($positionConfidenceScore !== null && $ticker !== '') {
                $latestScore = $this->assetAnalysisScoreRepository->findLatestScoreByTicker($ticker);

                if ($latestScore !== null) {
                    $confidenceDrop = $positionConfidenceScore - $latestScore;

                    if ($confidenceDrop >= (float) config('market.alerts.confidence_drop_threshold', 12.0)) {
                        $count += $this->createAlertOnce(
                            userId:    $userId,
                            alertType: TradingAlertType::CONFIDENCE_DROPPING->value,
                            severity:  AlertSeverity::WARNING->value,
                            title:     "{$ticker} com queda de convicção",
                            message:   'A convicção operacional caiu de forma relevante para esta posição.',
                            payload:   [
                                'portfolio_position_id' => $positionId,
                                'confidence_drop'       => round($confidenceDrop, 4),
                            ],
                        );
                    }
                }
            }

            $setupCode = $position['trade_call']['setup_code'] ?? null;

            if (is_string($setupCode) && $setupCode !== '') {
                $metric = $this->setupMetricRepository->findBySetupCode($setupCode);

                if ($metric !== null && ((float) $metric->expectancy <= 0.0 || (float) $metric->winrate <= 50.0)) {
                    $count += $this->createAlertOnce(
                        userId:    $userId,
                        alertType: TradingAlertType::SETUP_DETERIORATING->value,
                        severity:  AlertSeverity::WARNING->value,
                        title:     "Setup {$setupCode} em deterioração",
                        message:   'O setup da posição perdeu edge estatístico recentemente.',
                        payload:   [
                            'portfolio_position_id' => $positionId,
                            'setup_code'            => $setupCode,
                            'expectancy'            => (float) $metric->expectancy,
                            'winrate'               => (float) $metric->winrate,
                        ],
                    );
                }
            }
        }

        if ((bool) ($risk['blocked'] ?? false)) {
            $count += $this->createAlertOnce(
                userId:    $userId,
                alertType: TradingAlertType::PORTFOLIO_RISK_LIMIT->value,
                severity:  AlertSeverity::CRITICAL->value,
                title:     'Carteira acima dos limites de risco',
                message:   'A carteira ultrapassou parâmetros de risco e requer ajuste.',
                payload:   [
                    'violations'        => $risk['violations'] ?? [],
                    'open_risk_percent' => $risk['open_risk_percent'] ?? null,
                ],
            );
        }

        $correlationPairs = (array) ($risk['correlations']['pairs'] ?? []);

        if ($correlationPairs !== []) {
            $count += $this->createAlertOnce(
                userId:    $userId,
                alertType: TradingAlertType::HIGH_CORRELATION->value,
                severity:  AlertSeverity::WARNING->value,
                title:     'Correlação elevada entre posições',
                message:   'Foram identificadas posições com correlação alta na carteira.',
                payload:   [
                    'pairs' => array_slice($correlationPairs, 0, 10),
                ],
            );
        }

        $overSectorLimit = (array) ($risk['exposure']['over_sector_limit'] ?? []);

        if ($overSectorLimit !== []) {
            $count += $this->createAlertOnce(
                userId:    $userId,
                alertType: TradingAlertType::SECTOR_CONCENTRATION->value,
                severity:  AlertSeverity::WARNING->value,
                title:     'Concentração setorial elevada',
                message:   'Um ou mais setores estão acima do limite configurado.',
                payload:   [
                    'sectors' => $overSectorLimit,
                ],
            );
        }

        if (in_array($regime->regime, ['correction', 'bear', 'high_volatility'], true)) {
            $count += $this->createAlertOnce(
                userId:    $userId,
                alertType: TradingAlertType::REGIME_WORSENING->value,
                severity:  AlertSeverity::INFO->value,
                title:     'Regime de mercado deteriorado',
                message:   'O regime de mercado atual exige postura mais defensiva.',
                payload:   [
                    'regime'        => $regime->regime,
                    'context_score' => $regime->contextScore,
                ],
            );
        }

        return $count;
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
        $signature = hash('sha1', json_encode([$userId, $alertType, $payload], JSON_THROW_ON_ERROR));

        if ($this->tradingAlertRepository->existsBySignature($userId, $alertType, $title, $signature)) {
            return 0;
        }

        $this->tradingAlertRepository->create([
            'user_id'    => $userId,
            'alert_type' => $alertType,
            'severity'   => $severity,
            'title'      => $title,
            'message'    => $message,
            'payload'    => array_merge($payload, ['signature' => $signature]),
            'is_read'    => false,
        ]);

        return 1;
    }
}
