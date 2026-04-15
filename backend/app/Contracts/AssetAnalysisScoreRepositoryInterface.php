<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\AssetAnalysisScore;
use Illuminate\Support\Collection;

interface AssetAnalysisScoreRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertScore(int $assetId, string $tradeDate, array $data): void;

    public function latestTradeDate(): ?string;

    /** Top N scores for a date ordered desc by final_score, with monitoredAsset eager-loaded. */
    public function findTopByDate(string $date, float $minScore, int $limit): Collection;

    /** Bottom N scores (recommendation=evitar OR score<55) for a date, with monitoredAsset. */
    public function findAvoidByDate(string $date, int $limit): Collection;

    /** Latest score for a specific asset, ordered desc by trade_date. */
    public function findLatestByAsset(int $assetId): ?AssetAnalysisScore;

    /**
     * All scores for a specific date, ordered desc by final_score, with monitoredAsset:id,ticker.
     * Used by DailyBriefGenerator to build the full brief.
     */
    public function findAllByDate(string $date): Collection;

    /** Total number of scores on a specific date. */
    public function countByDate(string $date): int;

    /** Number of scores on a specific date with final_score >= $minScore. */
    public function countAboveScoreByDate(string $date, float $minScore): int;

    /**
     * Scores on a specific date with final_score >= $minScore that belong to eligible assets,
     * ordered desc by final_score. Eager-loads monitoredAsset:id,ticker.
     */
    public function findEligibleByDate(string $date, float $minScore): Collection;

    /**
     * All scores within an optional date range, ordered ascending by trade_date.
     * Used by BacktestEngine to iterate over historical signals.
     *
     * @return Collection<int, AssetAnalysisScore>
     */
    public function queryInDateRange(?string $from, ?string $to): Collection;

    /**
     * Latest final_score for an asset identified by ticker.
     * Returns null when no score exists.
     */
    public function findLatestScoreByTicker(string $ticker): ?float;
}
