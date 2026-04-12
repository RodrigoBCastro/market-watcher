<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SetupMetric extends Model
{
    protected $fillable = [
        'setup_code',
        'total_trades',
        'wins',
        'losses',
        'winrate',
        'avg_gain',
        'avg_loss',
        'expectancy',
        'edge',
        'is_enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_trades' => 'int',
            'wins' => 'int',
            'losses' => 'int',
            'winrate' => 'float',
            'avg_gain' => 'float',
            'avg_loss' => 'float',
            'expectancy' => 'float',
            'edge' => 'float',
            'is_enabled' => 'boolean',
        ];
    }
}
