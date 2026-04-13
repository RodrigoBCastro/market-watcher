<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TradeCall extends Model
{
    protected $fillable = [
        'monitored_asset_id',
        'trade_date',
        'setup_code',
        'setup_label',
        'entry_price',
        'stop_price',
        'target_price',
        'risk_percent',
        'reward_percent',
        'rr_ratio',
        'score',
        'final_rank_score',
        'advanced_classification',
        'confidence_score',
        'confidence_label',
        'market_regime',
        'expectancy_snapshot',
        'market_context_score_snapshot',
        'status',
        'generated_by_engine',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trade_date' => 'date',
            'entry_price' => 'float',
            'stop_price' => 'float',
            'target_price' => 'float',
            'risk_percent' => 'float',
            'reward_percent' => 'float',
            'rr_ratio' => 'float',
            'score' => 'float',
            'final_rank_score' => 'float',
            'confidence_score' => 'float',
            'expectancy_snapshot' => 'float',
            'market_context_score_snapshot' => 'float',
            'generated_by_engine' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function monitoredAsset(): BelongsTo
    {
        return $this->belongsTo(MonitoredAsset::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CallReview::class);
    }

    public function outcome(): HasOne
    {
        return $this->hasOne(TradeOutcome::class);
    }

    public function portfolioPositions(): HasMany
    {
        return $this->hasMany(PortfolioPosition::class);
    }
}
