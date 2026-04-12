<?php

declare(strict_types=1);

return [
    'sync' => [
        'asset_days' => (int) env('MARKET_SYNC_ASSET_DAYS', 90),
    ],
    'auth' => [
        'token_ttl_days' => (int) env('API_TOKEN_TTL_DAYS', 30),
    ],
    'limits' => [
        'max_stop_percent' => 4.0,
        'min_target_percent' => 6.0,
        'min_rr_ratio' => 1.5,
    ],
    'calls' => [
        'max_calls_per_cycle' => (int) env('CALLS_MAX_PER_CYCLE', 8),
        'min_score' => (float) env('CALLS_MIN_SCORE', 70),
        'min_rr' => (float) env('CALLS_MIN_RR', 1.5),
        'min_history' => (int) env('CALLS_MIN_HISTORY', 8),
        'max_holding_days' => (int) env('CALLS_MAX_HOLDING_DAYS', 20),
    ],
    'ranking' => [
        'technical_weight' => (float) env('RANKING_TECHNICAL_WEIGHT', 0.6),
        'expectancy_weight' => (float) env('RANKING_EXPECTANCY_WEIGHT', 0.4),
    ],
    'optimizer' => [
        'min_rank' => (float) env('OPTIMIZER_MIN_RANK', 55),
    ],
    'quant' => [
        'alert_drawdown_threshold' => (float) env('QUANT_ALERT_DRAWDOWN_THRESHOLD', 8.0),
    ],
];
