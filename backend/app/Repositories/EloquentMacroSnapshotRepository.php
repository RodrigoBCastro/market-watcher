<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\MacroSnapshotRepositoryInterface;
use App\Models\MacroSnapshot;

class EloquentMacroSnapshotRepository implements MacroSnapshotRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(string $snapshotDate, array $data): void
    {
        MacroSnapshot::query()->updateOrCreate(
            ['snapshot_date' => $snapshotDate],
            $data,
        );
    }

    public function latestMarketBiasUpToDate(string $date): ?string
    {
        $value = MacroSnapshot::query()
            ->where('snapshot_date', '<=', $date)
            ->orderByDesc('snapshot_date')
            ->value('market_bias');

        return $value !== null ? (string) $value : null;
    }

    public function findLatestUpToDate(string $date): ?MacroSnapshot
    {
        return MacroSnapshot::query()
            ->where('snapshot_date', '<=', $date)
            ->orderByDesc('snapshot_date')
            ->first();
    }
}
