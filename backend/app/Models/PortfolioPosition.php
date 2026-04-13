<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortfolioPosition extends Model
{
    protected $fillable = [
        'user_id',
        'monitored_asset_id',
        'trade_call_id',
        'entry_date',
        'entry_price',
        'quantity',
        'invested_amount',
        'current_price',
        'stop_price',
        'target_price',
        'status',
        'confidence_score',
        'confidence_label',
        'market_regime',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'entry_price' => 'float',
            'quantity' => 'float',
            'invested_amount' => 'float',
            'current_price' => 'float',
            'stop_price' => 'float',
            'target_price' => 'float',
            'confidence_score' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function monitoredAsset(): BelongsTo
    {
        return $this->belongsTo(MonitoredAsset::class);
    }

    public function tradeCall(): BelongsTo
    {
        return $this->belongsTo(TradeCall::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PortfolioPositionEvent::class);
    }

    public function closedPositions(): HasMany
    {
        return $this->hasMany(PortfolioClosedPosition::class);
    }
}
