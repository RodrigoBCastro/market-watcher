<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BacktestResult extends Model
{
    protected $fillable = [
        'strategy_name',
        'total_trades',
        'winrate',
        'total_return',
        'max_drawdown',
        'profit_factor',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_trades' => 'int',
            'winrate' => 'float',
            'total_return' => 'float',
            'max_drawdown' => 'float',
            'profit_factor' => 'float',
            'metadata' => 'array',
        ];
    }
}
