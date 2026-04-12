<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetQuote extends Model
{
    protected $fillable = [
        'monitored_asset_id',
        'trade_date',
        'open',
        'high',
        'low',
        'close',
        'adjusted_close',
        'volume',
        'source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trade_date' => 'date',
            'open' => 'float',
            'high' => 'float',
            'low' => 'float',
            'close' => 'float',
            'adjusted_close' => 'float',
            'volume' => 'integer',
        ];
    }

    public function monitoredAsset(): BelongsTo
    {
        return $this->belongsTo(MonitoredAsset::class);
    }
}
