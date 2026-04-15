<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\MacroSnapshot;

interface MacroSnapshotRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(string $snapshotDate, array $data): void;

    public function latestMarketBiasUpToDate(string $date): ?string;

    /**
     * Returns the most recent MacroSnapshot whose snapshot_date is <= $date.
     */
    public function findLatestUpToDate(string $date): ?MacroSnapshot;
}
