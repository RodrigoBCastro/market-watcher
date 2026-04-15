<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AssetAnalysisScoreRepositoryInterface;
use App\Models\AssetAnalysisScore;
use Illuminate\Support\Collection;

class EloquentAssetAnalysisScoreRepository implements AssetAnalysisScoreRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertScore(int $assetId, string $tradeDate, array $data): void
    {
        AssetAnalysisScore::query()->updateOrCreate(
            ['monitored_asset_id' => $assetId, 'trade_date' => $tradeDate],
            $data,
        );
    }

    public function latestTradeDate(): ?string
    {
        $value = AssetAnalysisScore::query()->max('trade_date');

        return $value !== null ? (string) $value : null;
    }

    public function findTopByDate(string $date, float $minScore, int $limit): Collection
    {
        return AssetAnalysisScore::query()
            ->with('monitoredAsset:id,ticker')
            ->whereDate('trade_date', $date)
            ->where('final_score', '>=', $minScore)
            ->orderByDesc('final_score')
            ->limit($limit)
            ->get();
    }

    public function findAvoidByDate(string $date, int $limit): Collection
    {
        return AssetAnalysisScore::query()
            ->with('monitoredAsset:id,ticker')
            ->whereDate('trade_date', $date)
            ->where(static function ($query): void {
                $query->where('recommendation', 'evitar')
                    ->orWhere('final_score', '<', 55);
            })
            ->orderBy('final_score')
            ->limit($limit)
            ->get();
    }

    public function findLatestByAsset(int $assetId): ?AssetAnalysisScore
    {
        /** @var AssetAnalysisScore|null $score */
        $score = AssetAnalysisScore::query()
            ->where('monitored_asset_id', $assetId)
            ->orderByDesc('trade_date')
            ->first();

        return $score;
    }

    public function findAllByDate(string $date): Collection
    {
        return AssetAnalysisScore::query()
            ->with('monitoredAsset:id,ticker')
            ->whereDate('trade_date', $date)
            ->orderByDesc('final_score')
            ->get();
    }

    public function countByDate(string $date): int
    {
        return AssetAnalysisScore::query()
            ->whereDate('trade_date', $date)
            ->count();
    }

    public function countAboveScoreByDate(string $date, float $minScore): int
    {
        return AssetAnalysisScore::query()
            ->whereDate('trade_date', $date)
            ->where('final_score', '>=', $minScore)
            ->count();
    }

    public function findEligibleByDate(string $date, float $minScore): Collection
    {
        return AssetAnalysisScore::query()
            ->with('monitoredAsset:id,ticker')
            ->whereDate('trade_date', $date)
            ->where('final_score', '>=', $minScore)
            ->whereHas('monitoredAsset', static function ($query): void {
                $query->where('eligible_for_calls', true)->where('is_active', true);
            })
            ->orderByDesc('final_score')
            ->get();
    }

    /**
     * @return Collection<int, AssetAnalysisScore>
     */
    public function queryInDateRange(?string $from, ?string $to): Collection
    {
        return AssetAnalysisScore::query()
            ->when($from !== null, static function ($query) use ($from): void {
                $query->whereDate('trade_date', '>=', $from);
            })
            ->when($to !== null, static function ($query) use ($to): void {
                $query->whereDate('trade_date', '<=', $to);
            })
            ->orderBy('trade_date')
            ->get();
    }

    public function findLatestScoreByTicker(string $ticker): ?float
    {
        $value = AssetAnalysisScore::query()
            ->whereHas('monitoredAsset', static function ($query) use ($ticker): void {
                $query->where('ticker', $ticker);
            })
            ->orderByDesc('trade_date')
            ->value('final_score');

        return $value !== null ? (float) $value : null;
    }
}
