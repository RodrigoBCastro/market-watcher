<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeOutcome extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'trade_call_id',
        'monitored_asset_id',
        'setup_code',
        'entry_price',
        'stop_price',
        'target_price',
        'exit_price',
        'result',
        'pnl_percent',
        'duration_days',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_price' => 'float',
            'stop_price' => 'float',
            'target_price' => 'float',
            'exit_price' => 'float',
            'pnl_percent' => 'float',
            'duration_days' => 'int',
            'created_at' => 'datetime',
        ];
    }

    public function tradeCall(): BelongsTo
    {
        return $this->belongsTo(TradeCall::class);
    }

    public function monitoredAsset(): BelongsTo
    {
        return $this->belongsTo(MonitoredAsset::class);
    }
}
