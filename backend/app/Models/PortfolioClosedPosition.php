<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioClosedPosition extends Model
{
    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'portfolio_position_id',
        'exit_date',
        'exit_price',
        'quantity',
        'gross_pnl',
        'gross_pnl_percent',
        'result',
        'duration_days',
        'exit_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exit_date' => 'date',
            'exit_price' => 'float',
            'quantity' => 'float',
            'gross_pnl' => 'float',
            'gross_pnl_percent' => 'float',
            'duration_days' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function portfolioPosition(): BelongsTo
    {
        return $this->belongsTo(PortfolioPosition::class);
    }
}
