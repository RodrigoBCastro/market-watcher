<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\EquityCurvePoint;
use Illuminate\Database\Eloquent\Builder;

interface EquityCurvePointRepositoryInterface
{
    /**
     * Returns an Eloquent Builder scoped to the given user's equity curve
     * points. The caller may apply additional where clauses (date filters)
     * and ordering before executing the query.
     *
     * @return Builder<EquityCurvePoint>
     */
    public function queryByUser(int $userId): Builder;
}
