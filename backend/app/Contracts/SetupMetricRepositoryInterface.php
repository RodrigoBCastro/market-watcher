<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\SetupMetric;
use Illuminate\Support\Collection;

interface SetupMetricRepositoryInterface
{
    public function findBySetupCode(string $setupCode): ?SetupMetric;

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertBySetupCode(string $setupCode, array $data): SetupMetric;

    /**
     * @return Collection<int, SetupMetric>
     */
    public function listAllOrderedByExpectancy(): Collection;

    /**
     * Bulk-sets is_enabled = false for setups with total_trades >= $minHistory
     * whose expectancy <= 0 or winrate <= 50.
     *
     * Returns the number of rows updated.
     */
    public function disableDeteriorating(int $minHistory): int;

    /**
     * Returns an array of setup_codes that are considered deteriorating
     * (total_trades >= $minHistory AND (expectancy <= 0 OR winrate <= 50)).
     *
     * @return string[]
     */
    public function listDeterioratingSetupCodes(int $minHistory): array;

    /**
     * Bulk-fetch metrics for a list of setup codes, keyed by setup_code.
     * Avoids N+1 when multiple calls need setup metrics simultaneously.
     *
     * @param  array<int, string>           $setupCodes
     * @return Collection<string, SetupMetric>
     */
    public function findByCodes(array $setupCodes): Collection;

    /**
     * Returns expectancy values keyed by setup_code.
     * Used by ScoreOptimizerService to score all outcomes without N+1 queries.
     *
     * @return array<string, float>
     */
    public function pluckExpectancyBySetupCode(): array;
}
