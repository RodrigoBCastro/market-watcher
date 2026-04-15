<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\BacktestResult;
use Illuminate\Support\Collection;

interface BacktestResultRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BacktestResult;

    /**
     * Returns results ordered by created_at descending.
     *
     * @return Collection<int, BacktestResult>
     */
    public function listByUser(int $limit): Collection;
}
