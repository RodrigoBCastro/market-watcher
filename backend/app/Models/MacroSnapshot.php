<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MacroSnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'usd_brl',
        'ibov_close',
        'market_bias',
        'source',
        'raw_payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'usd_brl' => 'float',
            'ibov_close' => 'float',
            'raw_payload' => 'array',
        ];
    }
}
