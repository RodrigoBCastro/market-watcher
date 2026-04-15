<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

interface TechnicalIndicatorRepositoryInterface
{
    /**
     * Bulk-upserts calculated indicator rows for a given asset.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function upsertBatch(int $assetId, array $rows): int;

    public function findByAssetDescending(int $assetId, int $limit): Collection;

    public function findByAssetAscending(int $assetId, int $limit): Collection;
}
