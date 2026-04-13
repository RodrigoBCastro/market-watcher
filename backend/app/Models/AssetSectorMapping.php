<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetSectorMapping extends Model
{
    protected $fillable = [
        'monitored_asset_id',
        'sector',
        'subsector',
        'segment',
    ];

    public function monitoredAsset(): BelongsTo
    {
        return $this->belongsTo(MonitoredAsset::class);
    }
}
