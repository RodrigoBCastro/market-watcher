<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneratedBrief extends Model
{
    protected $fillable = [
        'brief_date',
        'market_summary',
        'market_bias',
        'ibov_analysis',
        'risk_notes',
        'conclusion',
        'raw_payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'brief_date' => 'date',
            'raw_payload' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(GeneratedBriefItem::class);
    }
}
