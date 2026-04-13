<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetHistorySyncState extends Model
{
    protected $fillable = [
        'monitored_asset_id',
        'status',
        'bootstrap_from_date',
        'earliest_quote_date_found',
        'latest_quote_date_synced',
        'last_mode_used',
        'last_bootstrap_at',
        'last_rolling_at',
        'bootstrap_completed_at',
        'last_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bootstrap_from_date' => 'date',
            'earliest_quote_date_found' => 'date',
            'latest_quote_date_synced' => 'date',
            'last_bootstrap_at' => 'datetime',
            'last_rolling_at' => 'datetime',
            'bootstrap_completed_at' => 'datetime',
        ];
    }

    public function monitoredAsset(): BelongsTo
    {
        return $this->belongsTo(MonitoredAsset::class);
    }
}
