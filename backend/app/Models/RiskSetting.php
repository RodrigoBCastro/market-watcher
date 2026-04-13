<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskSetting extends Model
{
    protected $fillable = [
        'user_id',
        'total_capital',
        'risk_per_trade_percent',
        'max_portfolio_risk_percent',
        'max_open_positions',
        'max_position_size_percent',
        'max_sector_exposure_percent',
        'max_correlated_positions',
        'allow_pyramiding',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_capital' => 'float',
            'risk_per_trade_percent' => 'float',
            'max_portfolio_risk_percent' => 'float',
            'max_open_positions' => 'integer',
            'max_position_size_percent' => 'float',
            'max_sector_exposure_percent' => 'float',
            'max_correlated_positions' => 'integer',
            'allow_pyramiding' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
