<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\TechnicalIndicatorRepositoryInterface;
use App\Models\TechnicalIndicator;
use Illuminate\Support\Collection;

class EloquentTechnicalIndicatorRepository implements TechnicalIndicatorRepositoryInterface
{
    private const CHUNK_SIZE = 500;

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function upsertBatch(int $assetId, array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $now = now()->toDateTimeString();

        $prepared = array_map(static function (array $row) use ($assetId, $now): array {
            return array_merge($row, [
                'monitored_asset_id' => $assetId,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
        }, $rows);

        // All columns except composite PK and created_at are candidates for update
        $updateColumns = array_keys(array_diff_key(
            $prepared[0],
            array_flip(['monitored_asset_id', 'trade_date', 'created_at']),
        ));

        foreach (array_chunk($prepared, self::CHUNK_SIZE) as $chunk) {
            TechnicalIndicator::query()->upsert(
                $chunk,
                ['monitored_asset_id', 'trade_date'],
                $updateColumns,
            );
        }

        return count($rows);
    }

    public function findByAssetDescending(int $assetId, int $limit): Collection
    {
        return TechnicalIndicator::query()
            ->where('monitored_asset_id', $assetId)
            ->orderByDesc('trade_date')
            ->limit($limit)
            ->get();
    }

    public function findByAssetAscending(int $assetId, int $limit): Collection
    {
        return TechnicalIndicator::query()
            ->where('monitored_asset_id', $assetId)
            ->orderBy('trade_date')
            ->limit($limit)
            ->get();
    }
}
