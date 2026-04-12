<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketIndex extends Model
{
    protected $table = 'market_indexes';

    protected $fillable = [
        'symbol',
        'trade_date',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trade_date' => 'date',
            'open' => 'float',
            'high' => 'float',
            'low' => 'float',
            'close' => 'float',
            'volume' => 'integer',
        ];
    }
}
