<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAnalysisScore extends Model
{
    protected $fillable = [
        'monitored_asset_id',
        'trade_date',
        'trend_score',
        'moving_average_score',
        'structure_score',
        'momentum_score',
        'volume_score',
        'risk_score',
        'market_context_score',
        'final_score',
        'classification',
        'setup_code',
        'setup_label',
        'recommendation',
        'suggested_entry',
        'suggested_stop',
        'suggested_target',
        'risk_percent',
        'reward_percent',
        'rr_ratio',
        'alert_flags',
        'rationale',
        'raw_payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trade_date' => 'date',
            'alert_flags' => 'array',
            'raw_payload' => 'array',
            'trend_score' => 'float',
            'moving_average_score' => 'float',
            'structure_score' => 'float',
            'momentum_score' => 'float',
            'volume_score' => 'float',
            'risk_score' => 'float',
            'market_context_score' => 'float',
            'final_score' => 'float',
            'suggested_entry' => 'float',
            'suggested_stop' => 'float',
            'suggested_target' => 'float',
            'risk_percent' => 'float',
            'reward_percent' => 'float',
            'rr_ratio' => 'float',
        ];
    }

    public function monitoredAsset(): BelongsTo
    {
        return $this->belongsTo(MonitoredAsset::class);
    }
}
