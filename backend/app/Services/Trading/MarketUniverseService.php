<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\AssetQuoteRepositoryInterface;
use App\Contracts\MarketUniverseMembershipRepositoryInterface;
use App\Contracts\MarketUniverseServiceInterface;
use App\Contracts\MonitoredAssetRepositoryInterface;
use App\Enums\UniverseEventType;
use App\Enums\UniverseType;
use App\Models\AssetQuote;
use App\Models\MarketUniverseMembership;
use App\Models\MonitoredAsset;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MarketUniverseService implements MarketUniverseServiceInterface
{
    public function __construct(
        private readonly MonitoredAssetRepositoryInterface $monitoredAssetRepository,
        private readonly AssetQuoteRepositoryInterface $assetQuoteRepository,
        private readonly MarketUniverseMembershipRepositoryInterface $membershipRepository,
    ) {
    }

    public function summary(): array
    {
        $counts = $this->membershipRepository->countActiveByType();

        $promoted = $this->membershipRepository->listRecentEvents(UniverseEventType::PROMOTED->value, 8);
        $demoted  = $this->membershipRepository->listRecentEvents(UniverseEventType::DEMOTED->value, 8);

        $assetsInReview = $this->monitoredAssetRepository
            ->findStaleUniverseReview(5, 12)
            ->map(static fn (MonitoredAsset $asset): array => [
                'id'                    => (int) $asset->id,
                'ticker'                => $asset->ticker,
                'name'                  => $asset->name,
                'universe_type'         => $asset->universe_type,
                'last_universe_review_at' => $asset->last_universe_review_at?->toIso8601String(),
            ])
            ->all();

        $averages = $this->monitoredAssetRepository->averageMetricsForDataCollection();

        $dataCount     = (int) ($counts[UniverseType::DATA->value] ?? 0);
        $eligibleCount = (int) ($counts[UniverseType::ELIGIBLE->value] ?? 0);
        $tradingCount  = (int) ($counts[UniverseType::TRADING->value] ?? 0);

        return [
            'totals' => [
                'data_universe'     => $dataCount,
                'eligible_universe' => $eligibleCount,
                'trading_universe'  => $tradingCount,
            ],
            'watchlists' => [
                'full_market_watchlist' => [
                    'universe_type' => UniverseType::DATA->value,
                    'total_assets'  => $dataCount,
                ],
                'extended_watchlist' => [
                    'universe_type' => UniverseType::ELIGIBLE->value,
                    'total_assets'  => $eligibleCount,
                ],
                'core_watchlist' => [
                    'universe_type' => UniverseType::TRADING->value,
                    'total_assets'  => $tradingCount,
                ],
            ],
            'latest_promoted'  => $promoted,
            'latest_demoted'   => $demoted,
            'assets_in_review' => $assetsInReview,
            'average_metrics'  => [
                'liquidity_score'               => round((float) ($averages['avg_liquidity_score'] ?? 0.0), 4),
                'operability_score'             => round((float) ($averages['avg_operability_score'] ?? 0.0), 4),
                'avg_daily_financial_volume_20' => round((float) ($averages['avg_financial_volume'] ?? 0.0), 2),
                'volatility_20'                 => round((float) ($averages['avg_volatility_20'] ?? 0.0), 4),
            ],
        ];
    }

    public function listUniverse(string $universeType, int $limit = 200): array
    {
        $type  = $this->resolveType($universeType);
        $limit = max(1, min($limit, 500));

        $items = $this->membershipRepository
            ->findActiveByType($type->value, $limit)
            ->map(function (MarketUniverseMembership $membership): array {
                $asset    = $membership->monitoredAsset;
                $analysis = $asset?->latestAnalysisScore;

                return [
                    'asset_id'                       => (int) ($asset?->id ?? 0),
                    'ticker'                         => $asset?->ticker,
                    'name'                           => $asset?->name,
                    'sector'                         => $asset?->sector,
                    'universe_type'                  => $membership->universe_type,
                    'inclusion_reason'               => $membership->inclusion_reason,
                    'exclusion_reason'               => $membership->exclusion_reason,
                    'last_changed_at'                => $membership->last_changed_at?->toIso8601String(),
                    'liquidity_score'                => $asset?->liquidity_score !== null ? (float) $asset->liquidity_score : null,
                    'operability_score'              => $asset?->operability_score !== null ? (float) $asset->operability_score : null,
                    'avg_daily_volume_20'            => $asset?->avg_daily_volume_20 !== null ? (float) $asset->avg_daily_volume_20 : null,
                    'avg_daily_financial_volume_20'  => $asset?->avg_daily_financial_volume_20 !== null ? (float) $asset->avg_daily_financial_volume_20 : null,
                    'avg_spread_percent'             => $asset?->avg_spread_percent !== null ? (float) $asset->avg_spread_percent : null,
                    'avg_trades_count_20'            => $asset?->avg_trades_count_20 !== null ? (float) $asset->avg_trades_count_20 : null,
                    'volatility_20'                  => $asset?->volatility_20 !== null ? (float) $asset->volatility_20 : null,
                    'in_ibov'                        => (bool) ($asset?->in_ibov ?? false),
                    'in_index_small_caps'            => $asset?->in_index_small_caps,
                    'latest_analysis'                => $analysis !== null ? [
                        'trade_date'     => $analysis->trade_date?->toDateString(),
                        'final_score'    => (float) $analysis->final_score,
                        'classification' => $analysis->classification,
                        'recommendation' => $analysis->recommendation,
                        'setup_label'    => $analysis->setup_label,
                    ] : null,
                ];
            })
            ->all();

        return [
            'universe_type' => $type->value,
            'watchlist'     => $this->watchlistForType($type),
            'items'         => $items,
        ];
    }

    public function recalculateEligibleUniverse(?int $changedByUserId = null): array
    {
        $cfg              = config('market.universes.eligible');
        $minHistory       = (int)   ($cfg['min_history_days']                   ?? 90);
        $minAvgVolume     = (float) ($cfg['min_avg_daily_volume']               ?? 200000.0);
        $minFinancialVol  = (float) ($cfg['min_avg_daily_financial_volume']     ?? 5000000.0);
        $maxSpread        = (float) ($cfg['max_avg_spread_percent']             ?? 5.5);
        $minVolatility    = (float) ($cfg['min_volatility_20']                  ?? 1.1);
        $maxVolatility    = (float) ($cfg['max_volatility_20']                  ?? 8.5);
        $minOperability   = (float) ($cfg['min_operability_score']              ?? 55.0);

        $assets   = $this->monitoredAssetRepository->findAllActive();
        $reviewed = 0;
        $promoted = 0;
        $demoted  = 0;

        foreach ($assets as $asset) {
            $this->ensureMembershipRows($asset);

            $dataActive = (bool) $asset->collect_data;
            $this->setMembershipState(
                asset: $asset,
                type: UniverseType::DATA,
                isActive: $dataActive,
                automaticReason: $dataActive
                    ? 'Ativo configurado para coleta de dados.'
                    : 'Coleta ampla desativada para o ativo.',
                changedByUserId: $changedByUserId,
            );

            if (! $dataActive) {
                $this->setMembershipState($asset, UniverseType::ELIGIBLE, false, 'Ativo fora do Data Universe.', null, $changedByUserId);
                $this->setMembershipState($asset, UniverseType::TRADING,  false, 'Ativo fora do Eligible Universe.', null, $changedByUserId);
                $this->refreshAssetUniverseFlags($asset);
                $reviewed++;
                continue;
            }

            $quotes  = $this->assetQuoteRepository
                ->findByAssetDescending((int) $asset->id, max(120, $minHistory))
                ->reverse()
                ->values();

            $metrics = $this->calculateEligibilityMetrics($quotes);

            $asset->fill([
                'avg_daily_volume_20'           => $metrics['avg_daily_volume_20'],
                'avg_daily_financial_volume_20' => $metrics['avg_daily_financial_volume_20'],
                'avg_spread_percent'            => $metrics['avg_spread_percent'],
                'avg_trades_count_20'           => $metrics['avg_trades_count_20'],
                'volatility_20'                 => $metrics['volatility_20'],
                'liquidity_score'               => $metrics['liquidity_score'],
                'operability_score'             => $metrics['operability_score'],
                'last_universe_review_at'       => now(),
            ]);
            $this->monitoredAssetRepository->save($asset);

            $reasons = [];

            if ($metrics['history_count'] < $minHistory) {
                $reasons[] = "histórico insuficiente ({$metrics['history_count']} < {$minHistory})";
            }
            if ($metrics['avg_daily_volume_20'] < $minAvgVolume) {
                $reasons[] = 'volume médio diário abaixo do mínimo';
            }
            if ($metrics['avg_daily_financial_volume_20'] < $minFinancialVol) {
                $reasons[] = 'volume financeiro médio abaixo do mínimo';
            }
            if ($metrics['avg_spread_percent'] > $maxSpread) {
                $reasons[] = 'spread médio (proxy) acima do limite';
            }
            if ($metrics['volatility_20'] < $minVolatility || $metrics['volatility_20'] > $maxVolatility) {
                $reasons[] = 'volatilidade fora da faixa operacional';
            }
            if ($metrics['operability_score'] < $minOperability) {
                $reasons[] = 'operability score abaixo do mínimo';
            }

            $isEligible = $reasons === [];
            $reason     = $isEligible
                ? 'Ativo promovido automaticamente por critérios de liquidez e operabilidade.'
                : implode('; ', $reasons);

            $result = $this->setMembershipState(
                asset: $asset,
                type: UniverseType::ELIGIBLE,
                isActive: $isEligible,
                automaticReason: $reason,
                changedByUserId: $changedByUserId,
            );

            if (! $isEligible) {
                $this->setMembershipState($asset, UniverseType::TRADING, false, 'Ativo fora do Eligible Universe após revisão.', null, $changedByUserId);
            }

            if (($result['changed'] ?? false) && ($result['to_active'] ?? false)) {
                $promoted++;
            }
            if (($result['changed'] ?? false) && ($result['to_active'] ?? true) === false) {
                $demoted++;
            }

            $this->refreshAssetUniverseFlags($asset);
            $reviewed++;
        }

        return [
            'reviewed_assets' => $reviewed,
            'promoted'        => $promoted,
            'demoted'         => $demoted,
            'timestamp'       => now()->toIso8601String(),
        ];
    }

    public function diagnoseEligibleUniverse(): array
    {
        $cfg            = config('market.universes.eligible');
        $minHistory     = (int)   ($cfg['min_history_days']                   ?? 90);
        $minAvgVolume   = (float) ($cfg['min_avg_daily_volume']               ?? 200000.0);
        $minFinancial   = (float) ($cfg['min_avg_daily_financial_volume']     ?? 5000000.0);
        $maxSpread      = (float) ($cfg['max_avg_spread_percent']             ?? 5.5);
        $minVol         = (float) ($cfg['min_volatility_20']                  ?? 1.1);
        $maxVol         = (float) ($cfg['max_volatility_20']                  ?? 8.5);
        $minOperability = (float) ($cfg['min_operability_score']              ?? 55.0);

        $assets        = $this->monitoredAssetRepository->findAllActive();
        $results       = [];
        $failureCounts = [];

        foreach ($assets as $asset) {
            if (! $asset->collect_data) {
                $results[] = [
                    'ticker'        => $asset->ticker,
                    'eligible'      => false,
                    'collect_data'  => false,
                    'metrics'       => null,
                    'failed_checks' => ['collect_data desativado'],
                    'passed_checks' => [],
                ];
                $failureCounts['collect_data desativado'] = ($failureCounts['collect_data desativado'] ?? 0) + 1;
                continue;
            }

            $quotes = $this->assetQuoteRepository
                ->findByAssetDescending((int) $asset->id, max(120, $minHistory))
                ->reverse()
                ->values();

            $metrics      = $this->calculateEligibilityMetrics($quotes);
            $failedChecks = [];
            $passedChecks = [];

            $checks = [
                'histórico insuficiente'                   => $metrics['history_count'] < $minHistory,
                'volume médio diário abaixo do mínimo'     => $metrics['avg_daily_volume_20'] < $minAvgVolume,
                'volume financeiro médio abaixo do mínimo' => $metrics['avg_daily_financial_volume_20'] < $minFinancial,
                'spread médio acima do limite'             => $metrics['avg_spread_percent'] > $maxSpread,
                'volatilidade fora da faixa operacional'   => $metrics['volatility_20'] < $minVol || $metrics['volatility_20'] > $maxVol,
                'operability score abaixo do mínimo'       => $metrics['operability_score'] < $minOperability,
            ];

            foreach ($checks as $label => $failed) {
                if ($failed) {
                    $failedChecks[]        = $label;
                    $failureCounts[$label] = ($failureCounts[$label] ?? 0) + 1;
                } else {
                    $passedChecks[] = $label;
                }
            }

            $results[] = [
                'ticker'        => $asset->ticker,
                'eligible'      => $failedChecks === [],
                'collect_data'  => true,
                'metrics'       => $metrics,
                'failed_checks' => $failedChecks,
                'passed_checks' => $passedChecks,
            ];
        }

        $total    = count($results);
        $eligible = count(array_filter($results, static fn (array $r): bool => (bool) $r['eligible']));

        arsort($failureCounts);

        return [
            'thresholds'     => [
                'min_history_days'               => $minHistory,
                'min_avg_daily_volume'           => $minAvgVolume,
                'min_avg_daily_financial_volume' => $minFinancial,
                'max_avg_spread_percent'         => $maxSpread,
                'min_volatility_20'              => $minVol,
                'max_volatility_20'              => $maxVol,
                'min_operability_score'          => $minOperability,
            ],
            'summary'        => [
                'total'      => $total,
                'eligible'   => $eligible,
                'ineligible' => $total - $eligible,
            ],
            'failure_counts' => $failureCounts,
            'assets'         => $results,
        ];
    }

    public function recalculateTradingUniverse(?int $changedByUserId = null): array
    {
        $cfg              = config('market.universes.trading');
        $targetSize       = (int)   ($cfg['target_size']      ?? 35);
        $minPriority      = (float) ($cfg['min_priority_score'] ?? 58.0);

        $weights          = $cfg['weights'] ?? [];
        $liquidityWeight  = (float) ($weights['liquidity']              ?? 0.35);
        $operabilityWeight = (float) ($weights['operability']           ?? 0.35);
        $technicalWeight  = (float) ($weights['recent_technical_score'] ?? 0.20);
        $indexWeight      = (float) ($weights['index_relevance_bonus']  ?? 0.10);

        $eligibleMemberships = $this->membershipRepository->findEligibleWithAssets();

        $ranked = $eligibleMemberships
            ->map(function (MarketUniverseMembership $membership) use (
                $liquidityWeight,
                $operabilityWeight,
                $technicalWeight,
                $indexWeight,
            ): array {
                $asset       = $membership->monitoredAsset;
                $latestScore = (float) ($asset?->latestAnalysisScore?->final_score ?? 0.0);
                $liquidity   = (float) ($asset?->liquidity_score ?? 0.0);
                $operability = (float) ($asset?->operability_score ?? 0.0);
                $indexBonus  = (bool) ($asset?->in_ibov ?? false)
                    ? 100.0
                    : ((bool) ($asset?->in_index_small_caps ?? false) ? 65.0 : 0.0);

                $priority = ($liquidity * $liquidityWeight)
                    + ($operability * $operabilityWeight)
                    + ($latestScore * $technicalWeight)
                    + ($indexBonus * $indexWeight);

                return [
                    'asset_id'       => (int) ($asset?->id ?? 0),
                    'priority_score' => round($priority, 4),
                    'asset'          => $asset,
                ];
            })
            ->sortByDesc('priority_score')
            ->values();

        $selectedIds = $ranked
            ->filter(static fn (array $item): bool => $item['priority_score'] >= $minPriority)
            ->take(max(1, $targetSize))
            ->pluck('asset_id')
            ->filter()
            ->all();

        $promoted = 0;
        $demoted  = 0;
        $reviewed = 0;

        foreach ($eligibleMemberships as $membership) {
            $asset = $membership->monitoredAsset;
            if ($asset === null) {
                continue;
            }

            $isSelected = in_array((int) $asset->id, $selectedIds, true);
            $priority   = (float) ($ranked->firstWhere('asset_id', (int) $asset->id)['priority_score'] ?? 0.0);

            $result = $this->setMembershipState(
                asset: $asset,
                type: UniverseType::TRADING,
                isActive: $isSelected,
                automaticReason: $isSelected
                    ? "Ativo priorizado para Trading Universe (priority_score={$priority})."
                    : "Ativo não priorizado para Trading Universe (priority_score={$priority}).",
                changedByUserId: $changedByUserId,
            );

            if (($result['changed'] ?? false) && ($result['to_active'] ?? false)) {
                $promoted++;
            }
            if (($result['changed'] ?? false) && ($result['to_active'] ?? true) === false) {
                $demoted++;
            }

            $this->refreshAssetUniverseFlags($asset);
            $reviewed++;
        }

        foreach ($this->membershipRepository->findOrphanTrading() as $membership) {
            $asset = $membership->monitoredAsset;
            if ($asset === null) {
                continue;
            }

            $this->setMembershipState(
                asset: $asset,
                type: UniverseType::TRADING,
                isActive: false,
                automaticReason: 'Ativo removido do Trading Universe por estar fora do Eligible Universe.',
                changedByUserId: $changedByUserId,
            );
            $this->refreshAssetUniverseFlags($asset);
        }

        return [
            'reviewed_assets' => $reviewed,
            'promoted'        => $promoted,
            'demoted'         => $demoted,
            'selected_assets' => count($selectedIds),
            'target_size'     => max(1, $targetSize),
            'timestamp'       => now()->toIso8601String(),
        ];
    }

    public function updateMembership(
        int $assetId,
        string $universeType,
        bool $isActive,
        ?string $manualReason = null,
        ?int $changedByUserId = null,
    ): array {
        $asset = $this->monitoredAssetRepository->findById($assetId);
        if ($asset === null) {
            throw (new ModelNotFoundException())->setModel(MonitoredAsset::class, [$assetId]);
        }

        $type         = $this->resolveType($universeType);
        $manualReason = $manualReason !== null ? trim($manualReason) : null;
        if ($manualReason === '') {
            $manualReason = null;
        }

        DB::transaction(function () use ($asset, $type, $isActive, $manualReason, $changedByUserId): void {
            $this->ensureMembershipRows($asset);

            if ($type === UniverseType::DATA && $isActive === false) {
                $this->setMembershipState($asset, UniverseType::TRADING,  false, 'Data Universe desativado manualmente.', $manualReason, $changedByUserId);
                $this->setMembershipState($asset, UniverseType::ELIGIBLE, false, 'Data Universe desativado manualmente.', $manualReason, $changedByUserId);
            }

            if ($type === UniverseType::ELIGIBLE && $isActive === false) {
                $this->setMembershipState($asset, UniverseType::TRADING, false, 'Eligible Universe desativado manualmente.', $manualReason, $changedByUserId);
            }

            if ($type === UniverseType::ELIGIBLE && $isActive === true) {
                $this->setMembershipState($asset, UniverseType::DATA, true, 'Pré-requisito para Eligible Universe.', $manualReason, $changedByUserId);
            }

            if ($type === UniverseType::TRADING && $isActive === true) {
                $this->setMembershipState($asset, UniverseType::DATA,     true, 'Pré-requisito para Trading Universe.', $manualReason, $changedByUserId);
                $this->setMembershipState($asset, UniverseType::ELIGIBLE, true, 'Pré-requisito para Trading Universe.', $manualReason, $changedByUserId);
            }

            $this->setMembershipState(
                asset: $asset,
                type: $type,
                isActive: $isActive,
                automaticReason: $isActive ? 'Alteração manual: ativo promovido.' : 'Alteração manual: ativo rebaixado.',
                manualReason: $manualReason,
                changedByUserId: $changedByUserId,
            );

            $this->refreshAssetUniverseFlags($asset);
        });

        return $this->statusForAsset($asset->fresh(['universeMemberships']));
    }

    public function statusByTicker(string $ticker): array
    {
        $asset = $this->monitoredAssetRepository->findByTicker($ticker);

        if ($asset === null) {
            throw (new ModelNotFoundException())->setModel(MonitoredAsset::class, [$ticker]);
        }

        $this->ensureMembershipRows($asset);

        return $this->statusForAsset($asset->fresh(['universeMemberships', 'latestAnalysisScore']));
    }

    private function ensureMembershipRows(MonitoredAsset $asset): void
    {
        foreach (UniverseType::cases() as $type) {
            $this->membershipRepository->findOrCreateForAsset(
                (int) $asset->id,
                $type->value,
                [
                    'is_active'        => $type === UniverseType::DATA ? (bool) $asset->collect_data : false,
                    'inclusion_reason' => $type === UniverseType::DATA ? 'Ativo disponível para coleta ampla.' : null,
                    'exclusion_reason' => $type === UniverseType::DATA ? null : 'Aguardando elegibilidade.',
                    'last_changed_at'  => now(),
                ],
            );
        }
    }

    /**
     * @return array{changed: bool, from_active: bool|null, to_active: bool}
     */
    private function setMembershipState(
        MonitoredAsset $asset,
        UniverseType $type,
        bool $isActive,
        string $automaticReason,
        ?string $manualReason = null,
        ?int $changedByUserId = null,
    ): array {
        $membership = $this->membershipRepository->findOrCreateForAsset(
            (int) $asset->id,
            $type->value,
            ['is_active' => false],
        );

        $previous = $membership->is_active === null ? null : (bool) $membership->is_active;
        $changed  = $previous !== $isActive;

        $membership->fill([
            'is_active'        => $isActive,
            'inclusion_reason' => $isActive ? $automaticReason : $membership->inclusion_reason,
            'exclusion_reason' => $isActive ? null : $automaticReason,
            'last_changed_at'  => now(),
        ]);
        $this->membershipRepository->save($membership);

        if ($changed || $manualReason !== null) {
            $eventType = UniverseEventType::REVIEW->value;
            if ($manualReason !== null) {
                $eventType = UniverseEventType::MANUAL_OVERRIDE->value;
            } elseif ($changed && $isActive) {
                $eventType = UniverseEventType::PROMOTED->value;
            } elseif ($changed && ! $isActive) {
                $eventType = UniverseEventType::DEMOTED->value;
            }

            $this->membershipRepository->createEvent([
                'market_universe_membership_id' => $membership->id,
                'monitored_asset_id'            => $asset->id,
                'universe_type'                 => $type->value,
                'event_type'                    => $eventType,
                'from_active'                   => $previous,
                'to_active'                     => $isActive,
                'automatic_reason'              => $automaticReason,
                'manual_reason'                 => $manualReason,
                'changed_by_user_id'            => $changedByUserId,
            ]);
        }

        return [
            'changed'     => $changed,
            'from_active' => $previous,
            'to_active'   => $isActive,
        ];
    }

    private function refreshAssetUniverseFlags(MonitoredAsset $asset): void
    {
        $memberships = $this->membershipRepository->findAllForAsset((int) $asset->id);

        $dataActive     = (bool) ($memberships->get(UniverseType::DATA->value)?->is_active ?? false);
        $eligibleActive = (bool) ($memberships->get(UniverseType::ELIGIBLE->value)?->is_active ?? false);
        $tradingActive  = (bool) ($memberships->get(UniverseType::TRADING->value)?->is_active ?? false);

        if ($tradingActive && ! $eligibleActive) {
            $eligibleActive = true;
        }
        if (($eligibleActive || $tradingActive) && ! $dataActive) {
            $dataActive = true;
        }

        $universeType = UniverseType::DATA->value;
        if ($eligibleActive) {
            $universeType = UniverseType::ELIGIBLE->value;
        }
        if ($tradingActive) {
            $universeType = UniverseType::TRADING->value;
        }

        $asset->fill([
            'collect_data'           => $dataActive,
            'monitoring_enabled'     => $dataActive,
            'eligible_for_analysis'  => $eligibleActive,
            'eligible_for_calls'     => $tradingActive,
            'eligible_for_execution' => $tradingActive,
            'universe_type'          => $universeType,
        ]);
        $this->monitoredAssetRepository->save($asset);
    }

    /**
     * @param  Collection<int, AssetQuote>  $quotes
     * @return array<string, float|int>
     */
    private function calculateEligibilityMetrics(Collection $quotes): array
    {
        if ($quotes->isEmpty()) {
            return [
                'history_count'                  => 0,
                'avg_daily_volume_20'            => 0.0,
                'avg_daily_financial_volume_20'  => 0.0,
                'avg_spread_percent'             => 0.0,
                'avg_trades_count_20'            => 0.0,
                'volatility_20'                  => 0.0,
                'liquidity_score'                => 0.0,
                'operability_score'              => 0.0,
            ];
        }

        $window       = $quotes->take(-20)->values();
        $historyCount = $quotes->count();

        $avgVolume    = (float) ($window->avg('volume') ?? 0.0);
        $avgFinancial = (float) ($window->avg(static fn (AssetQuote $quote): float => (float) $quote->close * (float) $quote->volume) ?? 0.0);

        // Proxies usados por limitação da fonte: range intraday como spread e volume como trades.
        $avgSpread = (float) ($window->avg(static function (AssetQuote $quote): float {
            $close = (float) $quote->close;
            if ($close <= 0.0) {
                return 0.0;
            }
            return (((float) $quote->high - (float) $quote->low) / $close) * 100;
        }) ?? 0.0);

        $avgTradesCount = $avgVolume;

        $returns = [];
        for ($i = 1; $i < $window->count(); $i++) {
            $prev = (float) ($window[$i - 1]->close ?? 0.0);
            $curr = (float) ($window[$i]->close ?? 0.0);
            if ($prev <= 0.0) {
                continue;
            }
            $returns[] = (($curr - $prev) / $prev) * 100;
        }

        $volatility = $this->stdDev($returns);

        $liquidityTarget = (float) config('market.universes.eligible.min_avg_daily_financial_volume', 12000000.0);
        $tradesTarget    = (float) config('market.universes.eligible.min_avg_trades_count', 300000.0);
        $maxSpreadCfg    = (float) config('market.universes.eligible.max_avg_spread_percent', 3.0);
        $minVol          = (float) config('market.universes.eligible.min_volatility_20', 1.1);
        $maxVol          = (float) config('market.universes.eligible.max_volatility_20', 8.5);

        $financialComponent = min(100.0, $liquidityTarget > 0 ? ($avgFinancial / $liquidityTarget) * 100 : 100.0);
        $tradesComponent    = min(100.0, $tradesTarget > 0 ? ($avgTradesCount / $tradesTarget) * 100 : 100.0);
        $liquidityScore     = ($financialComponent * 0.7) + ($tradesComponent * 0.3);

        $spreadPenalty = $maxSpreadCfg > 0 ? min(100.0, ($avgSpread / $maxSpreadCfg) * 100) : 0.0;
        $spreadScore   = max(0.0, 100.0 - $spreadPenalty);

        $volatilityScore = 0.0;
        if ($volatility >= $minVol && $volatility <= $maxVol) {
            $volatilityScore = 100.0;
        } elseif ($volatility < $minVol && $minVol > 0) {
            $volatilityScore = max(0.0, 100.0 - (($minVol - $volatility) / $minVol) * 100);
        } elseif ($volatility > $maxVol && $maxVol > 0) {
            $volatilityScore = max(0.0, 100.0 - (($volatility - $maxVol) / $maxVol) * 100);
        }

        $historyScore     = min(100.0, ($historyCount / max(1, (int) config('market.universes.eligible.min_history_days', 90))) * 100);
        $operabilityScore = ($liquidityScore * 0.45) + ($spreadScore * 0.20) + ($volatilityScore * 0.25) + ($historyScore * 0.10);

        return [
            'history_count'                 => $historyCount,
            'avg_daily_volume_20'           => round($avgVolume, 2),
            'avg_daily_financial_volume_20' => round($avgFinancial, 2),
            'avg_spread_percent'            => round($avgSpread, 4),
            'avg_trades_count_20'           => round($avgTradesCount, 2),
            'volatility_20'                 => round($volatility, 4),
            'liquidity_score'               => round($liquidityScore, 4),
            'operability_score'             => round($operabilityScore, 4),
        ];
    }

    /**
     * @param  array<int, float>  $values
     */
    private function stdDev(array $values): float
    {
        $count = count($values);
        if ($count <= 1) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $sum  = 0.0;
        foreach ($values as $value) {
            $sum += ($value - $mean) ** 2;
        }

        return sqrt($sum / ($count - 1));
    }

    private function resolveType(string $value): UniverseType
    {
        $type = UniverseType::tryFrom($value);
        if ($type !== null) {
            return $type;
        }

        throw new \InvalidArgumentException('Universe type inválido.');
    }

    private function watchlistForType(UniverseType $type): string
    {
        return match ($type) {
            UniverseType::DATA     => 'full_market_watchlist',
            UniverseType::ELIGIBLE => 'extended_watchlist',
            UniverseType::TRADING  => 'core_watchlist',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function statusForAsset(MonitoredAsset $asset): array
    {
        $memberships = $asset->universeMemberships->keyBy('universe_type');

        return [
            'asset' => [
                'id'                            => (int) $asset->id,
                'ticker'                        => $asset->ticker,
                'name'                          => $asset->name,
                'sector'                        => $asset->sector,
                'universe_type'                 => $asset->universe_type,
                'collect_data'                  => (bool) $asset->collect_data,
                'eligible_for_analysis'         => (bool) $asset->eligible_for_analysis,
                'eligible_for_calls'            => (bool) $asset->eligible_for_calls,
                'eligible_for_execution'        => (bool) $asset->eligible_for_execution,
                'avg_daily_volume_20'           => $asset->avg_daily_volume_20 !== null ? (float) $asset->avg_daily_volume_20 : null,
                'avg_daily_financial_volume_20' => $asset->avg_daily_financial_volume_20 !== null ? (float) $asset->avg_daily_financial_volume_20 : null,
                'avg_spread_percent'            => $asset->avg_spread_percent !== null ? (float) $asset->avg_spread_percent : null,
                'avg_trades_count_20'           => $asset->avg_trades_count_20 !== null ? (float) $asset->avg_trades_count_20 : null,
                'volatility_20'                 => $asset->volatility_20 !== null ? (float) $asset->volatility_20 : null,
                'in_ibov'                       => (bool) $asset->in_ibov,
                'in_index_small_caps'           => $asset->in_index_small_caps,
                'liquidity_score'               => $asset->liquidity_score !== null ? (float) $asset->liquidity_score : null,
                'operability_score'             => $asset->operability_score !== null ? (float) $asset->operability_score : null,
                'last_universe_review_at'       => $asset->last_universe_review_at?->toIso8601String(),
                'latest_analysis'               => $asset->latestAnalysisScore !== null ? [
                    'trade_date'     => $asset->latestAnalysisScore->trade_date?->toDateString(),
                    'final_score'    => (float) $asset->latestAnalysisScore->final_score,
                    'classification' => $asset->latestAnalysisScore->classification,
                    'recommendation' => $asset->latestAnalysisScore->recommendation,
                    'setup_label'    => $asset->latestAnalysisScore->setup_label,
                ] : null,
            ],
            'memberships' => [
                UniverseType::DATA->value => $this->membershipArray($memberships->get(UniverseType::DATA->value)),
                UniverseType::ELIGIBLE->value => $this->membershipArray($memberships->get(UniverseType::ELIGIBLE->value)),
                UniverseType::TRADING->value => $this->membershipArray($memberships->get(UniverseType::TRADING->value)),
            ],
            'watchlists' => [
                'full_market_watchlist' => (bool) ($memberships->get(UniverseType::DATA->value)?->is_active ?? false),
                'extended_watchlist'    => (bool) ($memberships->get(UniverseType::ELIGIBLE->value)?->is_active ?? false),
                'core_watchlist'        => (bool) ($memberships->get(UniverseType::TRADING->value)?->is_active ?? false),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function membershipArray(?MarketUniverseMembership $membership): ?array
    {
        if ($membership === null) {
            return null;
        }

        return [
            'id' => (int) $membership->id,
            'universe_type' => $membership->universe_type,
            'is_active' => (bool) $membership->is_active,
            'inclusion_reason' => $membership->inclusion_reason,
            'exclusion_reason' => $membership->exclusion_reason,
            'last_changed_at' => $membership->last_changed_at?->toIso8601String(),
        ];
    }
}
