<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MonitoredAsset extends Model
{
    protected $fillable = [
        'ticker',
        'name',
        'sector',
        'is_active',
        'monitoring_enabled',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'monitoring_enabled' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(AssetQuote::class);
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(TechnicalIndicator::class);
    }

    public function analysisScores(): HasMany
    {
        return $this->hasMany(AssetAnalysisScore::class);
    }

    public function latestAnalysisScore(): HasOne
    {
        return $this->hasOne(AssetAnalysisScore::class)->latestOfMany('trade_date');
    }

    public function briefItems(): HasMany
    {
        return $this->hasMany(GeneratedBriefItem::class);
    }

    public function tradeCalls(): HasMany
    {
        return $this->hasMany(TradeCall::class);
    }

    public function tradeOutcomes(): HasMany
    {
        return $this->hasMany(TradeOutcome::class);
    }
}
