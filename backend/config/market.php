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
];
