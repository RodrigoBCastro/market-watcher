<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\MonitoredAssetRepositoryInterface;
use App\Models\AssetAnalysisScore;
use App\Models\MonitoredAsset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class EloquentMonitoredAssetRepository implements MonitoredAssetRepositoryInterface
{
    public function cursorForAnalysis(?string $ticker = null): LazyCollection
    {
        return MonitoredAsset::query()
            ->where('eligible_for_analysis', true)
            ->when($ticker, static function ($query, string $t): void {
                $query->where('ticker', strtoupper($t));
            })
            ->select(['id', 'ticker'])
            ->orderBy('id')
            ->cursor();
    }

    public function cursorForCalls(?string $ticker = null): LazyCollection
    {
        return MonitoredAsset::query()
            ->where('eligible_for_calls', true)
            ->when($ticker, static function ($query, string $t): void {
                $query->where('ticker', strtoupper($t));
            })
            ->select(['id', 'ticker'])
            ->orderBy('id')
            ->cursor();
    }

    public function findForDataCollection(?string $ticker = null): Collection
    {
        return MonitoredAsset::query()
            ->where('collect_data', true)
            ->when($ticker, static function ($query, string $t): void {
                $query->where('ticker', strtoupper($t));
            })
            ->select(['id', 'ticker'])
            ->orderBy('ticker')
            ->get();
    }

    public function findAll(): Collection
    {
        return MonitoredAsset::query()
            ->orderBy('ticker')
            ->get();
    }

    public function findStaleUniverseReview(int $staleAfterDays, int $limit): Collection
    {
        return MonitoredAsset::query()
            ->where(static function ($query) use ($staleAfterDays): void {
                $query->whereNull('last_universe_review_at')
                    ->orWhere('last_universe_review_at', '<', now()->subDays($staleAfterDays));
            })
            ->orderByDesc('updated_at')
            ->limit(max(1, min($limit, 500)))
            ->get(['id', 'ticker', 'name', 'universe_type', 'last_universe_review_at']);
    }

    public function findByTicker(string $ticker): ?MonitoredAsset
    {
        return MonitoredAsset::query()
            ->where('ticker', strtoupper($ticker))
            ->first();
    }

    public function findOrFailByTicker(string $ticker): MonitoredAsset
    {
        return MonitoredAsset::query()
            ->where('ticker', strtoupper($ticker))
            ->firstOrFail();
    }

    public function findById(int $id): ?MonitoredAsset
    {
        return MonitoredAsset::query()->find($id);
    }

    public function findOrFailById(int $id): MonitoredAsset
    {
        return MonitoredAsset::query()->findOrFail($id);
    }

    public function findAllForListing(): Collection
    {
        return MonitoredAsset::query()
            ->with(['assetMaster', 'latestAnalysisScore', 'universeMemberships', 'historySyncState'])
            ->orderBy('ticker')
            ->get();
    }

    public function paginateForListing(int $page, int $perPage, string $sortBy, string $sortDirection): LengthAwarePaginator
    {
        $safePerPage = max(1, min($perPage, 200));
        $safePage = max(1, $page);
        $safeSortDirection = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';
        $allowedSortColumns = [
            'ticker' => 'ticker',
            'name' => 'name',
            'sector' => 'sector',
            'universe_type' => 'universe_type',
            'collect_data' => 'collect_data',
            'eligible_for_analysis' => 'eligible_for_analysis',
            'eligible_for_calls' => 'eligible_for_calls',
        ];

        $query = MonitoredAsset::query()
            ->with(['assetMaster', 'latestAnalysisScore', 'universeMemberships', 'historySyncState']);

        if ($sortBy === 'latest_score') {
            $latestScoreSubquery = AssetAnalysisScore::query()
                ->select('final_score')
                ->whereColumn('asset_analysis_scores.monitored_asset_id', 'monitored_assets.id')
                ->orderByDesc('trade_date')
                ->limit(1);

            $query->orderBy($latestScoreSubquery, $safeSortDirection)
                ->orderBy('ticker');
        } else {
            $column = $allowedSortColumns[$sortBy] ?? 'ticker';
            $query->orderBy($column, $safeSortDirection)
                ->orderBy('ticker');
        }

        return $query->paginate($safePerPage, ['*'], 'page', $safePage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MonitoredAsset
    {
        return MonitoredAsset::query()->create($data);
    }

    public function save(MonitoredAsset $asset): void
    {
        $asset->save();
    }

    public function delete(MonitoredAsset $asset): void
    {
        $asset->delete();
    }

    public function findIdByTicker(string $ticker): ?int
    {
        $value = MonitoredAsset::query()
            ->where('ticker', strtoupper($ticker))
            ->value('id');

        return $value !== null ? (int) $value : null;
    }

    /**
     * @param  array<int, string>  $tickers
     * @return array<string, int>
     */
    public function findIdsByTickers(array $tickers): array
    {
        if ($tickers === []) {
            return [];
        }

        return MonitoredAsset::query()
            ->whereIn('ticker', array_map('strtoupper', $tickers))
            ->pluck('id', 'ticker')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return array<string, float|null>
     */
    public function averageMetricsForDataCollection(): array
    {
        $row = MonitoredAsset::query()
            ->where('collect_data', true)
            ->selectRaw('
                avg(liquidity_score) as avg_liquidity_score,
                avg(operability_score) as avg_operability_score,
                avg(avg_daily_financial_volume_20) as avg_financial_volume,
                avg(volatility_20) as avg_volatility_20
            ')
            ->first();

        return [
            'avg_liquidity_score'  => $row?->avg_liquidity_score !== null ? (float) $row->avg_liquidity_score : null,
            'avg_operability_score'=> $row?->avg_operability_score !== null ? (float) $row->avg_operability_score : null,
            'avg_financial_volume' => $row?->avg_financial_volume !== null ? (float) $row->avg_financial_volume : null,
            'avg_volatility_20'    => $row?->avg_volatility_20 !== null ? (float) $row->avg_volatility_20 : null,
        ];
    }
}
