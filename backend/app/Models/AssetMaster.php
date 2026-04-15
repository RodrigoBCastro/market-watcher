<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssetMaster extends Model
{
    protected $table = 'asset_master';

    protected $fillable = [
        'symbol',
        'name',
        'asset_type',
        'sector',
        'logo_url',
        'last_close',
        'last_change_percent',
        'last_volume',
        'market_cap',
        'source',
        'source_payload',
        'is_listed',
        'is_blacklisted_for_monitoring',
        'missing_sync_count',
        'first_seen_at',
        'last_seen_at',
        'delisted_at',
        'delisting_reason',
        'blacklisted_at',
        'blacklist_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_close' => 'float',
            'last_change_percent' => 'float',
            'last_volume' => 'integer',
            'market_cap' => 'float',
            'source_payload' => 'array',
            'is_listed' => 'boolean',
            'is_blacklisted_for_monitoring' => 'boolean',
            'missing_sync_count' => 'integer',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'delisted_at' => 'datetime',
            'blacklisted_at' => 'datetime',
        ];
    }

    public function monitoredAsset(): HasOne
    {
        return $this->hasOne(MonitoredAsset::class, 'asset_master_id');
    }
}
