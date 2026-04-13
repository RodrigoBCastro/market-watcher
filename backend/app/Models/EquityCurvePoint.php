<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquityCurvePoint extends Model
{
    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'reference_date',
        'equity_value',
        'cash_value',
        'invested_value',
        'open_risk_percent',
        'cumulative_return_percent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reference_date' => 'date',
            'equity_value' => 'float',
            'cash_value' => 'float',
            'invested_value' => 'float',
            'open_risk_percent' => 'float',
            'cumulative_return_percent' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
