<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioPositionEvent extends Model
{
    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'portfolio_position_id',
        'event_type',
        'event_date',
        'price',
        'quantity',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'price' => 'float',
            'quantity' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function portfolioPosition(): BelongsTo
    {
        return $this->belongsTo(PortfolioPosition::class);
    }
}
