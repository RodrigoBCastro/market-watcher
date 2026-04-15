<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\BacktestResultRepositoryInterface;
use App\Models\BacktestResult;
use Illuminate\Support\Collection;

class EloquentBacktestResultRepository implements BacktestResultRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BacktestResult
    {
        /** @var BacktestResult $result */
        $result = BacktestResult::query()->create($data);

        return $result;
    }

    /**
     * @return Collection<int, BacktestResult>
     */
    public function listByUser(int $limit): Collection
    {
        return BacktestResult::query()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
