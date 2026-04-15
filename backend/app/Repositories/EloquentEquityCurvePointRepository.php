<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\EquityCurvePointRepositoryInterface;
use App\Models\EquityCurvePoint;
use Illuminate\Database\Eloquent\Builder;

class EloquentEquityCurvePointRepository implements EquityCurvePointRepositoryInterface
{
    /**
     * @return Builder<EquityCurvePoint>
     */
    public function queryByUser(int $userId): Builder
    {
        return EquityCurvePoint::query()->where('user_id', $userId);
    }
}
