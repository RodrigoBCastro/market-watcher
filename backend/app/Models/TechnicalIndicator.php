<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalIndicator extends Model
{
    protected $fillable = [
        'monitored_asset_id',
        'trade_date',
        'sma_5',
        'sma_9',
        'sma_10',
        'sma_20',
        'sma_21',
        'sma_30',
        'sma_40',
        'sma_50',
        'sma_72',
        'sma_80',
        'sma_100',
        'sma_120',
        'sma_150',
        'sma_200',
        'ema_5',
        'ema_8',
        'ema_9',
        'ema_12',
        'ema_17',
        'ema_20',
        'ema_21',
        'ema_26',
        'ema_34',
        'ema_50',
        'ema_72',
        'ema_100',
        'ema_144',
        'ema_200',
        'rsi_7',
        'rsi_14',
        'macd_line',
        'macd_signal',
        'macd_histogram',
        'atr_14',
        'bollinger_mid',
        'bollinger_upper',
        'bollinger_lower',
        'adx_14',
        'stochastic_k',
        'stochastic_d',
        'roc',
        'avg_volume_20',
        'change_5',
        'change_10',
        'change_20',
        'high_20',
        'low_20',
        'high_50',
        'low_50',
        'high_200',
        'low_200',
        'distance_ema_21',
        'distance_sma_50',
        'distance_sma_200',
        'recent_volatility',
        'avg_range',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trade_date' => 'date',
        ];
    }

    public function monitoredAsset(): BelongsTo
    {
        return $this->belongsTo(MonitoredAsset::class);
    }
}
