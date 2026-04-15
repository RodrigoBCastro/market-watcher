<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\SetupMetricRepositoryInterface;
use App\Models\SetupMetric;
use Illuminate\Support\Collection;

class EloquentSetupMetricRepository implements SetupMetricRepositoryInterface
{
    public function findBySetupCode(string $setupCode): ?SetupMetric
    {
        /** @var SetupMetric|null $metric */
        $metric = SetupMetric::query()
            ->where('setup_code', $setupCode)
            ->first();

        return $metric;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertBySetupCode(string $setupCode, array $data): SetupMetric
    {
        /** @var SetupMetric $metric */
        $metric = SetupMetric::query()->updateOrCreate(
            ['setup_code' => $setupCode],
            $data,
        );

        return $metric;
    }

    /**
     * @return Collection<int, SetupMetric>
     */
    public function listAllOrderedByExpectancy(): Collection
    {
        return SetupMetric::query()
            ->orderByDesc('expectancy')
            ->get();
    }

    public function disableDeteriorating(int $minHistory): int
    {
        return SetupMetric::query()
            ->where('total_trades', '>=', $minHistory)
            ->where(static function ($query): void {
                $query->where('expectancy', '<=', 0)
                    ->orWhere('winrate', '<=', 50);
            })
            ->update(['is_enabled' => false]);
    }

    /**
     * @return string[]
     */
    public function listDeterioratingSetupCodes(int $minHistory): array
    {
        return SetupMetric::query()
            ->where('total_trades', '>=', $minHistory)
            ->where(static function ($query): void {
                $query->where('expectancy', '<=', 0)
                    ->orWhere('winrate', '<=', 50);
            })
            ->pluck('setup_code')
            ->map(static fn ($value): string => (string) $value)
            ->all();
    }

    /**
     * @param  array<int, string>           $setupCodes
     * @return Collection<string, SetupMetric>
     */
    public function findByCodes(array $setupCodes): Collection
    {
        if ($setupCodes === []) {
            return collect();
        }

        return SetupMetric::query()
            ->whereIn('setup_code', $setupCodes)
            ->get()
            ->keyBy('setup_code');
    }

    /**
     * @return array<string, float>
     */
    public function pluckExpectancyBySetupCode(): array
    {
        return SetupMetric::query()
            ->pluck('expectancy', 'setup_code')
            ->map(static fn ($value): float => (float) $value)
            ->all();
    }
}
