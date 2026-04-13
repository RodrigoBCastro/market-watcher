<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\DTOs\PortfolioPositionSnapshotDTO;
use App\Models\AssetQuote;
use App\Models\PortfolioPosition;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class PortfolioMarkToMarketService
{
    public function refreshForUser(int $userId): int
    {
        $positions = PortfolioPosition::query()
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->get(['id', 'monitored_asset_id', 'current_price']);

        if ($positions->isEmpty()) {
            return 0;
        }

        $updated = 0;

        foreach ($positions as $position) {
            $latestClose = AssetQuote::query()
                ->where('monitored_asset_id', $position->monitored_asset_id)
                ->orderByDesc('trade_date')
                ->value('close');

            if ($latestClose === null) {
                continue;
            }

            $latestClose = round((float) $latestClose, 4);

            if ((float) $position->current_price !== $latestClose) {
                $position->current_price = $latestClose;
                $position->save();
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * @param  Collection<int, PortfolioPosition>  $positions
     * @return array<int, PortfolioPositionSnapshotDTO>
     */
    public function snapshots(Collection $positions): array
    {
        return $positions
            ->map(fn (PortfolioPosition $position): PortfolioPositionSnapshotDTO => $this->snapshot($position))
            ->all();
    }

    public function snapshot(PortfolioPosition $position): PortfolioPositionSnapshotDTO
    {
        $entryPrice = (float) $position->entry_price;
        $quantity = (float) $position->quantity;

        $currentPrice = $position->current_price !== null
            ? (float) $position->current_price
            : $entryPrice;

        $currentValue = round($currentPrice * $quantity, 2);
        $unrealizedPnl = round(($currentPrice - $entryPrice) * $quantity, 2);

        $unrealizedPnlPercent = $entryPrice > 0
            ? round((($currentPrice - $entryPrice) / $entryPrice) * 100, 4)
            : 0.0;

        $distanceToStop = null;

        if ($position->stop_price !== null && $currentPrice > 0.0) {
            $distanceToStop = round((($currentPrice - (float) $position->stop_price) / $currentPrice) * 100, 4);
        }

        $distanceToTarget = null;

        if ($position->target_price !== null && $currentPrice > 0.0) {
            $distanceToTarget = round((((float) $position->target_price - $currentPrice) / $currentPrice * 100), 4);
        }

        $entryDate = CarbonImmutable::parse((string) $position->entry_date?->toDateString());
        $daysInTrade = max(0, $entryDate->diffInDays(CarbonImmutable::today()));

        $asset = $position->monitoredAsset;
        $sector = $asset?->sectorMapping?->sector ?? $asset?->sector ?? 'Outros';

        $tradeCall = null;

        if ($position->tradeCall !== null) {
            $tradeCall = [
                'id' => (int) $position->tradeCall->id,
                'setup_code' => $position->tradeCall->setup_code,
                'setup_label' => $position->tradeCall->setup_label,
                'score' => (float) $position->tradeCall->score,
                'confidence_score' => $position->tradeCall->confidence_score !== null
                    ? (float) $position->tradeCall->confidence_score
                    : null,
            ];
        }

        return new PortfolioPositionSnapshotDTO(
            id: (int) $position->id,
            ticker: (string) ($asset?->ticker ?? ''),
            assetName: (string) ($asset?->name ?? ''),
            sector: (string) $sector,
            status: (string) $position->status,
            entryDate: $entryDate->toDateString(),
            entryPrice: round($entryPrice, 4),
            quantity: round($quantity, 4),
            investedAmount: round((float) $position->invested_amount, 2),
            currentPrice: round($currentPrice, 4),
            stopPrice: $position->stop_price !== null ? round((float) $position->stop_price, 4) : null,
            targetPrice: $position->target_price !== null ? round((float) $position->target_price, 4) : null,
            confidenceScore: $position->confidence_score !== null ? round((float) $position->confidence_score, 4) : null,
            confidenceLabel: $position->confidence_label,
            marketRegime: $position->market_regime,
            currentValue: $currentValue,
            unrealizedPnl: $unrealizedPnl,
            unrealizedPnlPercent: $unrealizedPnlPercent,
            daysInTrade: $daysInTrade,
            distanceToStopPercent: $distanceToStop,
            distanceToTargetPercent: $distanceToTarget,
            tradeCall: $tradeCall,
            notes: $position->notes,
        );
    }
}
