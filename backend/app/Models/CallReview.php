<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallReview extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'trade_call_id',
        'reviewer_id',
        'decision',
        'comments',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function tradeCall(): BelongsTo
    {
        return $this->belongsTo(TradeCall::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
