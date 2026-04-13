<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketUniverseMembership extends Model
{
    protected $fillable = [
        'monitored_asset_id',
        'universe_type',
        'is_active',
        'inclusion_reason',
        'exclusion_reason',
        'last_changed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_changed_at' => 'datetime',
        ];
    }

    public function monitoredAsset(): BelongsTo
    {
        return $this->belongsTo(MonitoredAsset::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MarketUniverseEvent::class);
    }
}

