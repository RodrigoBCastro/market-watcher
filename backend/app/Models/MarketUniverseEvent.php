<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketUniverseEvent extends Model
{
    protected $fillable = [
        'market_universe_membership_id',
        'monitored_asset_id',
        'universe_type',
        'event_type',
        'from_active',
        'to_active',
        'automatic_reason',
        'manual_reason',
        'changed_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_active' => 'boolean',
            'to_active' => 'boolean',
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(MarketUniverseMembership::class, 'market_universe_membership_id');
    }

    public function monitoredAsset(): BelongsTo
    {
        return $this->belongsTo(MonitoredAsset::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}

