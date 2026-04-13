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
        'collect_data',
        'eligible_for_analysis',
        'eligible_for_calls',
        'eligible_for_execution',
        'universe_type',
        'avg_daily_volume_20',
        'avg_daily_financial_volume_20',
        'avg_spread_percent',
        'avg_trades_count_20',
        'volatility_20',
        'in_ibov',
        'in_index_small_caps',
        'liquidity_score',
        'operability_score',
        'last_universe_review_at',
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
            'collect_data' => 'boolean',
            'eligible_for_analysis' => 'boolean',
            'eligible_for_calls' => 'boolean',
            'eligible_for_execution' => 'boolean',
            'avg_daily_volume_20' => 'float',
            'avg_daily_financial_volume_20' => 'float',
            'avg_spread_percent' => 'float',
            'avg_trades_count_20' => 'float',
            'volatility_20' => 'float',
            'in_ibov' => 'boolean',
            'in_index_small_caps' => 'boolean',
            'liquidity_score' => 'float',
            'operability_score' => 'float',
            'last_universe_review_at' => 'datetime',
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

    public function sectorMapping(): HasOne
    {
        return $this->hasOne(AssetSectorMapping::class);
    }

    public function portfolioPositions(): HasMany
    {
        return $this->hasMany(PortfolioPosition::class);
    }

    public function universeMemberships(): HasMany
    {
        return $this->hasMany(MarketUniverseMembership::class);
    }
}
