<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\AssetAnalysisScoreRepositoryInterface;
use App\Contracts\AssetQuoteRepositoryInterface;
use App\Contracts\ConfidenceScoreServiceInterface;
use App\Contracts\MarketRegimeServiceInterface;
use App\Contracts\MonitoredAssetRepositoryInterface;
use App\Contracts\PortfolioClosedPositionRepositoryInterface;
use App\Contracts\PortfolioPositionRepositoryInterface;
use App\Contracts\PortfolioRiskServiceInterface;
use App\Contracts\PortfolioServiceInterface;
use App\Contracts\RiskSettingsServiceInterface;
use App\Contracts\SetupMetricRepositoryInterface;
use App\Contracts\TradeCallRepositoryInterface;
use App\Enums\PortfolioCloseResult;
use App\Enums\PortfolioExitReason;
use App\Enums\PortfolioPositionEventType;
use App\Enums\PortfolioPositionStatus;
use App\Models\PortfolioClosedPosition;
use App\Models\PortfolioPosition;
use App\Models\TradeCall;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PortfolioService implements PortfolioServiceInterface
{
    public function __construct(
        private readonly PortfolioRiskServiceInterface              $portfolioRiskService,
        private readonly PortfolioMarkToMarketService               $markToMarketService,
        private readonly RiskSettingsServiceInterface               $riskSettingsService,
        private readonly MarketRegimeServiceInterface               $marketRegimeService,
        private readonly ConfidenceScoreServiceInterface            $confidenceScoreService,
        private readonly PortfolioPositionRepositoryInterface       $portfolioPositionRepository,
        private readonly PortfolioClosedPositionRepositoryInterface $closedPositionRepository,
        private readonly MonitoredAssetRepositoryInterface          $monitoredAssetRepository,
        private readonly AssetQuoteRepositoryInterface              $assetQuoteRepository,
        private readonly SetupMetricRepositoryInterface             $setupMetricRepository,
        private readonly AssetAnalysisScoreRepositoryInterface      $assetAnalysisScoreRepository,
        private readonly TradeCallRepositoryInterface               $tradeCallRepository,
    ) {
    }

    public function listAll(int $userId): array
    {
        $this->markToMarketService->refreshForUser($userId);

        $positions = $this->portfolioPositionRepository->findAllByUser($userId);

        return array_map(
            static fn ($dto): array => $dto->toArray(),
            $this->markToMarketService->snapshots($positions),
        );
    }

    public function listOpen(int $userId): array
    {
        $this->markToMarketService->refreshForUser($userId);

        $positions = $this->portfolioPositionRepository->findOpenByUserWithRelations($userId);

        return array_map(
            static fn ($dto): array => $dto->toArray(),
            $this->markToMarketService->snapshots($positions),
        );
    }

    public function listClosed(int $userId): array
    {
        return $this->closedPositionRepository->listByUser($userId)
            ->map(static function (PortfolioClosedPosition $item): array {
                $position = $item->portfolioPosition;
                $asset    = $position?->monitoredAsset;
                $sector   = $asset?->sectorMapping?->sector ?? $asset?->sector ?? 'Outros';

                return [
                    'id'                    => (int) $item->id,
                    'portfolio_position_id' => (int) $item->portfolio_position_id,
                    'ticker'                => $asset?->ticker,
                    'asset_name'            => $asset?->name,
                    'sector'                => $sector,
                    'entry_date'            => $position?->entry_date?->toDateString(),
                    'entry_price'           => $position !== null ? (float) $position->entry_price : null,
                    'exit_date'             => $item->exit_date?->toDateString(),
                    'exit_price'            => (float) $item->exit_price,
                    'quantity'              => (float) $item->quantity,
                    'gross_pnl'             => (float) $item->gross_pnl,
                    'gross_pnl_percent'     => (float) $item->gross_pnl_percent,
                    'result'                => $item->result,
                    'duration_days'         => (int) $item->duration_days,
                    'exit_reason'           => $item->exit_reason,
                    'created_at'            => $item->created_at?->toIso8601String(),
                ];
            })
            ->all();
    }

    public function create(int $userId, array $payload): array
    {
        $assetId = (int) ($payload['monitored_asset_id'] ?? 0);

        if ($assetId <= 0) {
            throw new \InvalidArgumentException('monitored_asset_id é obrigatório.');
        }

        $asset = $this->monitoredAssetRepository->findById($assetId);

        if ($asset === null) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        $settings = $this->riskSettingsService->getForUser($userId);

        if (! $settings->allowPyramiding) {
            $alreadyOpen = $this->portfolioPositionRepository->findOpenByUserAndAsset($userId, $assetId) !== null;

            if ($alreadyOpen) {
                throw new \DomainException('Pyramiding desabilitado: já existe posição aberta neste ativo.');
            }
        }

        $tradeCall = null;

        if (isset($payload['trade_call_id'])) {
            $tradeCall = $this->tradeCallRepository->findById((int) $payload['trade_call_id']);
        }

        $entryDate = isset($payload['entry_date'])
            ? CarbonImmutable::parse((string) $payload['entry_date'])
            : CarbonImmutable::today();

        $entryPrice = (float) ($payload['entry_price'] ?? $tradeCall?->entry_price ?? 0.0);
        $quantity   = (float) ($payload['quantity'] ?? 0.0);

        if ($entryPrice <= 0.0 || $quantity <= 0.0) {
            throw new \InvalidArgumentException('entry_price e quantity devem ser maiores que zero.');
        }

        $investedAmount = (float) ($payload['invested_amount'] ?? ($entryPrice * $quantity));

        $stopPrice = isset($payload['stop_price'])
            ? (float) $payload['stop_price']
            : ($tradeCall?->stop_price !== null ? (float) $tradeCall->stop_price : null);

        $targetPrice = isset($payload['target_price'])
            ? (float) $payload['target_price']
            : ($tradeCall?->target_price !== null ? (float) $tradeCall->target_price : null);

        $riskAmount = $stopPrice !== null && $entryPrice > $stopPrice
            ? ($entryPrice - $stopPrice) * $quantity
            : 0.0;

        $gate = $this->portfolioRiskService->canOpenPosition(
            userId:           $userId,
            monitoredAssetId: $assetId,
            positionValue:    $investedAmount,
            riskAmount:       $riskAmount,
        );

        if (! $gate['allowed']) {
            throw new \DomainException(implode(' ', $gate['violations']));
        }

        $confidence  = $this->resolveConfidence($assetId, $tradeCall);
        $latestClose = $this->assetQuoteRepository->latestCloseByAsset($assetId);

        $position = DB::transaction(function () use (
            $assetId,
            $confidence,
            $entryDate,
            $entryPrice,
            $gate,
            $investedAmount,
            $latestClose,
            $payload,
            $quantity,
            $stopPrice,
            $targetPrice,
            $tradeCall,
            $userId,
        ): PortfolioPosition {
            $position = $this->portfolioPositionRepository->create([
                'user_id'            => $userId,
                'monitored_asset_id' => $assetId,
                'trade_call_id'      => $tradeCall?->id,
                'entry_date'         => $entryDate->toDateString(),
                'entry_price'        => round($entryPrice, 4),
                'quantity'           => round($quantity, 4),
                'invested_amount'    => round($investedAmount, 2),
                'current_price'      => $latestClose !== null ? round($latestClose, 4) : round($entryPrice, 4),
                'stop_price'         => $stopPrice !== null ? round($stopPrice, 4) : null,
                'target_price'       => $targetPrice !== null ? round($targetPrice, 4) : null,
                'status'             => PortfolioPositionStatus::OPEN->value,
                'confidence_score'   => $confidence['score'],
                'confidence_label'   => $confidence['label'],
                'market_regime'      => $confidence['market_regime'],
                'notes'              => isset($payload['notes']) ? (string) $payload['notes'] : null,
            ]);

            $position->events()->create([
                'event_type' => PortfolioPositionEventType::CREATED->value,
                'event_date' => now(),
                'price'      => $entryPrice,
                'quantity'   => $quantity,
                'notes'      => $gate['warnings'] !== [] ? implode(' | ', $gate['warnings']) : null,
            ]);

            return $position;
        });

        $position->load($this->positionRelations());

        return [
            'position'  => $this->markToMarketService->snapshot($position)->toArray(),
            'risk_gate' => $gate,
        ];
    }

    public function update(int $userId, int $positionId, array $payload): array
    {
        $position = $this->portfolioPositionRepository->findOrFailByIdForUser($positionId, $userId);

        $initialStop   = $position->stop_price !== null ? (float) $position->stop_price : null;
        $initialTarget = $position->target_price !== null ? (float) $position->target_price : null;
        $initialStatus = (string) $position->status;

        $updates = [];

        foreach (['stop_price', 'target_price', 'current_price'] as $field) {
            if (array_key_exists($field, $payload)) {
                $value           = $payload[$field];
                $updates[$field] = $value !== null ? round((float) $value, 4) : null;
            }
        }

        if (array_key_exists('notes', $payload)) {
            $updates['notes'] = $payload['notes'] !== null ? (string) $payload['notes'] : null;
        }

        if (array_key_exists('status', $payload)) {
            $status = (string) $payload['status'];

            if (! in_array($status, [PortfolioPositionStatus::OPEN->value, PortfolioPositionStatus::CANCELED->value], true)) {
                throw new \InvalidArgumentException('status inválido para atualização manual.');
            }

            $updates['status'] = $status;
        }

        $position = DB::transaction(function () use ($initialStatus, $initialStop, $initialTarget, $position, $updates): PortfolioPosition {
            if ($updates !== []) {
                $position->update($updates);
            }

            $eventType = PortfolioPositionEventType::UPDATED->value;

            if (($updates['status'] ?? $initialStatus) === PortfolioPositionStatus::CANCELED->value) {
                $eventType = PortfolioPositionEventType::CANCELED->value;
            } elseif (array_key_exists('stop_price', $updates) && (float) ($updates['stop_price'] ?? 0.0) !== (float) ($initialStop ?? 0.0)) {
                $eventType = PortfolioPositionEventType::STOP_ADJUSTED->value;
            } elseif (array_key_exists('target_price', $updates) && (float) ($updates['target_price'] ?? 0.0) !== (float) ($initialTarget ?? 0.0)) {
                $eventType = PortfolioPositionEventType::TARGET_ADJUSTED->value;
            }

            $position->events()->create([
                'event_type' => $eventType,
                'event_date' => now(),
                'price'      => $position->current_price,
                'quantity'   => $position->quantity,
                'notes'      => isset($updates['notes']) ? (string) $updates['notes'] : null,
            ]);

            return $position;
        });

        $position->load($this->positionRelations());

        return $this->markToMarketService->snapshot($position)->toArray();
    }

    public function close(int $userId, int $positionId, array $payload): array
    {
        $position = $this->portfolioPositionRepository->findOrFailOpenByIdForUser($positionId, $userId);

        $quantity = isset($payload['quantity']) ? (float) $payload['quantity'] : (float) $position->quantity;

        if ($quantity <= 0.0) {
            throw new \InvalidArgumentException('quantity deve ser maior que zero.');
        }

        if ($quantity < (float) $position->quantity) {
            throw new \InvalidArgumentException('Para saída parcial, utilize /partial-close.');
        }

        if ($quantity > (float) $position->quantity) {
            throw new \InvalidArgumentException('quantity não pode ser maior que a posição atual.');
        }

        $exitPrice  = $this->resolveExitPrice($position, $payload);
        $exitDate   = isset($payload['exit_date'])
            ? CarbonImmutable::parse((string) $payload['exit_date'])
            : CarbonImmutable::today();

        $exitReason = (string) ($payload['exit_reason'] ?? PortfolioExitReason::MANUAL->value);

        if (! in_array($exitReason, array_map(static fn (PortfolioExitReason $item): string => $item->value, PortfolioExitReason::cases()), true)) {
            throw new \InvalidArgumentException('exit_reason inválido.');
        }

        return DB::transaction(function () use ($exitDate, $exitPrice, $exitReason, $position, $quantity): array {
            $closed = $this->registerClosedPosition($position, $quantity, $exitPrice, $exitDate, $exitReason);

            $position->update([
                'status'        => PortfolioPositionStatus::CLOSED->value,
                'current_price' => round($exitPrice, 4),
            ]);

            $position->events()->create([
                'event_type' => PortfolioPositionEventType::FULL_EXIT->value,
                'event_date' => now(),
                'price'      => $exitPrice,
                'quantity'   => $quantity,
                'notes'      => $exitReason,
            ]);

            $position->load($this->positionRelations());

            return [
                'position'        => $this->markToMarketService->snapshot($position)->toArray(),
                'closed_position' => $this->closedPositionToArray($closed),
            ];
        });
    }

    public function partialClose(int $userId, int $positionId, array $payload): array
    {
        $position = $this->portfolioPositionRepository->findOrFailOpenByIdForUser($positionId, $userId);

        $quantity = (float) ($payload['quantity'] ?? 0.0);

        if ($quantity <= 0.0) {
            throw new \InvalidArgumentException('quantity deve ser maior que zero para saída parcial.');
        }

        if ($quantity >= (float) $position->quantity) {
            throw new \InvalidArgumentException('quantity da saída parcial deve ser menor que a posição atual.');
        }

        $exitPrice  = $this->resolveExitPrice($position, $payload);
        $exitDate   = isset($payload['exit_date'])
            ? CarbonImmutable::parse((string) $payload['exit_date'])
            : CarbonImmutable::today();

        $exitReason = (string) ($payload['exit_reason'] ?? PortfolioExitReason::MANUAL->value);

        if (! in_array($exitReason, array_map(static fn (PortfolioExitReason $item): string => $item->value, PortfolioExitReason::cases()), true)) {
            throw new \InvalidArgumentException('exit_reason inválido.');
        }

        return DB::transaction(function () use ($exitDate, $exitPrice, $exitReason, $position, $quantity): array {
            $closed = $this->registerClosedPosition($position, $quantity, $exitPrice, $exitDate, $exitReason);

            $remainingQuantity = round((float) $position->quantity - $quantity, 4);
            $remainingInvested = round((float) $position->entry_price * $remainingQuantity, 2);

            $position->update([
                'quantity'        => $remainingQuantity,
                'invested_amount' => $remainingInvested,
                'current_price'   => round($exitPrice, 4),
                'status'          => $remainingQuantity > 0 ? PortfolioPositionStatus::OPEN->value : PortfolioPositionStatus::CLOSED->value,
            ]);

            $position->events()->create([
                'event_type' => PortfolioPositionEventType::PARTIAL_EXIT->value,
                'event_date' => now(),
                'price'      => $exitPrice,
                'quantity'   => $quantity,
                'notes'      => $exitReason,
            ]);

            $position->load($this->positionRelations());

            return [
                'position'        => $this->markToMarketService->snapshot($position)->toArray(),
                'closed_position' => $this->closedPositionToArray($closed),
            ];
        });
    }

    public function refreshMarkToMarket(int $userId): int
    {
        return $this->markToMarketService->refreshForUser($userId);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveConfidence(int $monitoredAssetId, ?TradeCall $tradeCall): array
    {
        $regime = $this->marketRegimeService->current();

        if ($tradeCall !== null && $tradeCall->confidence_score !== null) {
            return [
                'score'         => round((float) $tradeCall->confidence_score, 4),
                'label'         => (string) ($tradeCall->confidence_label ?? ''),
                'market_regime' => (string) ($tradeCall->market_regime ?? $regime->regime),
            ];
        }

        $technicalScore = 50.0;
        $expectancy     = 0.0;

        if ($tradeCall !== null) {
            $technicalScore = (float) $tradeCall->score;
            $expectancy     = (float) ($tradeCall->expectancy_snapshot ?? 0.0);

            if ($expectancy === 0.0 && $tradeCall->setup_code !== null) {
                $metric     = $this->setupMetricRepository->findBySetupCode((string) $tradeCall->setup_code);
                $expectancy = (float) ($metric?->expectancy ?? 0.0);
            }
        } else {
            $latestAnalysis = $this->assetAnalysisScoreRepository->findLatestByAsset($monitoredAssetId);
            $technicalScore = (float) ($latestAnalysis?->final_score ?? 50.0);

            if ($latestAnalysis?->setup_code !== null) {
                $metric     = $this->setupMetricRepository->findBySetupCode((string) $latestAnalysis->setup_code);
                $expectancy = (float) ($metric?->expectancy ?? 0.0);
            }
        }

        $confidence = $this->confidenceScoreService->calculate($technicalScore, $expectancy, $regime->regime);

        return [
            'score'         => round($confidence->score, 4),
            'label'         => $confidence->label,
            'market_regime' => $regime->regime,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveExitPrice(PortfolioPosition $position, array $payload): float
    {
        if (isset($payload['exit_price']) && (float) $payload['exit_price'] > 0.0) {
            return (float) $payload['exit_price'];
        }

        if ($position->current_price !== null && (float) $position->current_price > 0.0) {
            return (float) $position->current_price;
        }

        $latest = $this->assetQuoteRepository->latestCloseByAsset((int) $position->monitored_asset_id);

        if ($latest !== null && $latest > 0.0) {
            return $latest;
        }

        return (float) $position->entry_price;
    }

    private function registerClosedPosition(
        PortfolioPosition $position,
        float $quantity,
        float $exitPrice,
        CarbonImmutable $exitDate,
        string $exitReason,
    ): PortfolioClosedPosition {
        $entryPrice = (float) $position->entry_price;

        $grossPnl        = round(($exitPrice - $entryPrice) * $quantity, 2);
        $grossPnlPercent = $entryPrice > 0.0
            ? round((($exitPrice - $entryPrice) / $entryPrice) * 100, 4)
            : 0.0;

        $result = PortfolioCloseResult::BREAKEVEN->value;

        if ($grossPnl > 0.0) {
            $result = PortfolioCloseResult::WIN->value;
        } elseif ($grossPnl < 0.0) {
            $result = PortfolioCloseResult::LOSS->value;
        }

        $durationDays = max(
            0,
            CarbonImmutable::parse((string) $position->entry_date?->toDateString())->diffInDays($exitDate),
        );

        return $this->closedPositionRepository->create([
            'portfolio_position_id' => $position->id,
            'exit_date'             => $exitDate->toDateString(),
            'exit_price'            => round($exitPrice, 4),
            'quantity'              => round($quantity, 4),
            'gross_pnl'             => $grossPnl,
            'gross_pnl_percent'     => $grossPnlPercent,
            'result'                => $result,
            'duration_days'         => $durationDays,
            'exit_reason'           => $exitReason,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function closedPositionToArray(PortfolioClosedPosition $closed): array
    {
        return [
            'id'                    => (int) $closed->id,
            'portfolio_position_id' => (int) $closed->portfolio_position_id,
            'exit_date'             => $closed->exit_date?->toDateString(),
            'exit_price'            => (float) $closed->exit_price,
            'quantity'              => (float) $closed->quantity,
            'gross_pnl'             => (float) $closed->gross_pnl,
            'gross_pnl_percent'     => (float) $closed->gross_pnl_percent,
            'result'                => $closed->result,
            'duration_days'         => (int) $closed->duration_days,
            'exit_reason'           => $closed->exit_reason,
            'created_at'            => $closed->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function positionRelations(): array
    {
        return [
            'monitoredAsset:id,ticker,name,sector',
            'monitoredAsset.sectorMapping:monitored_asset_id,sector,subsector,segment',
            'tradeCall:id,setup_code,setup_label,score,confidence_score,confidence_label,market_regime',
        ];
    }
}
