<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketIndexMaster extends Model
{
    protected $table = 'market_index_master';

    protected $fillable = [
        'symbol',
        'name',
        'source',
        'source_payload',
        'is_active',
        'first_seen_at',
        'last_seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_payload' => 'array',
            'is_active' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}

