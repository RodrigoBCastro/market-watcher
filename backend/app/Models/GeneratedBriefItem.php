<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedBriefItem extends Model
{
    protected $fillable = [
        'generated_brief_id',
        'monitored_asset_id',
        'rank_position',
        'final_score',
        'classification',
        'setup_label',
        'recommendation',
        'entry',
        'stop',
        'target',
        'risk_percent',
        'reward_percent',
        'rr_ratio',
        'rationale',
        'alert_flags',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'final_score' => 'float',
            'entry' => 'float',
            'stop' => 'float',
            'target' => 'float',
            'risk_percent' => 'float',
            'reward_percent' => 'float',
            'rr_ratio' => 'float',
            'alert_flags' => 'array',
        ];
    }

    public function brief(): BelongsTo
    {
        return $this->belongsTo(GeneratedBrief::class, 'generated_brief_id');
    }

    public function monitoredAsset(): BelongsTo
    {
        return $this->belongsTo(MonitoredAsset::class);
    }
}
