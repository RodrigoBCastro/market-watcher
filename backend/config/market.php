<?php

declare(strict_types=1);

return [
    'sync' => [
        'asset_days' => (int) env('MARKET_SYNC_ASSET_DAYS', 90),
        'start_years_back' => (int) env('MARKET_SYNC_START_YEARS_BACK', 10),
        'batch_size' => (int) env('MARKET_SYNC_BATCH_SIZE', 20),
    ],
    'asset_master' => [
        'delist_after_missing_syncs' => (int) env('ASSET_MASTER_DELIST_AFTER_MISSING_SYNCS', 3),
        'exclude_fractional_symbols' => (bool) env('ASSET_MASTER_EXCLUDE_FRACTIONAL_SYMBOLS', true),
    ],
    'bootstrap' => [
        'default_asset_types' => ['stock'],
        'default_limit' => (int) env('BOOTSTRAP_DATA_UNIVERSE_LIMIT', 1000),
    ],
    'universes' => [
        'eligible' => [
            'min_history_days' => (int) env('UNIVERSE_ELIGIBLE_MIN_HISTORY_DAYS', 90),
            'min_avg_daily_volume' => (float) env('UNIVERSE_ELIGIBLE_MIN_AVG_DAILY_VOLUME', 350000),
            'min_avg_daily_financial_volume' => (float) env('UNIVERSE_ELIGIBLE_MIN_AVG_DAILY_FINANCIAL_VOLUME', 12000000),
            'min_avg_trades_count' => (float) env('UNIVERSE_ELIGIBLE_MIN_AVG_TRADES_COUNT', 300000),
            'max_avg_spread_percent' => (float) env('UNIVERSE_ELIGIBLE_MAX_AVG_SPREAD_PERCENT', 3.0),
            'min_volatility_20' => (float) env('UNIVERSE_ELIGIBLE_MIN_VOLATILITY_20', 1.1),
            'max_volatility_20' => (float) env('UNIVERSE_ELIGIBLE_MAX_VOLATILITY_20', 8.5),
            'min_operability_score' => (float) env('UNIVERSE_ELIGIBLE_MIN_OPERABILITY_SCORE', 55.0),
        ],
        'trading' => [
            'target_size' => (int) env('UNIVERSE_TRADING_TARGET_SIZE', 35),
            'min_priority_score' => (float) env('UNIVERSE_TRADING_MIN_PRIORITY_SCORE', 58.0),
            'weights' => [
                'liquidity' => (float) env('UNIVERSE_TRADING_WEIGHT_LIQUIDITY', 0.35),
                'operability' => (float) env('UNIVERSE_TRADING_WEIGHT_OPERABILITY', 0.35),
                'recent_technical_score' => (float) env('UNIVERSE_TRADING_WEIGHT_RECENT_SCORE', 0.20),
                'index_relevance_bonus' => (float) env('UNIVERSE_TRADING_WEIGHT_INDEX_BONUS', 0.10),
            ],
        ],
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
    'risk' => [
        'default_total_capital' => (float) env('RISK_DEFAULT_TOTAL_CAPITAL', 10000),
        'default_risk_per_trade_percent' => (float) env('RISK_DEFAULT_RISK_PER_TRADE_PERCENT', 1.0),
        'default_max_portfolio_risk_percent' => (float) env('RISK_DEFAULT_MAX_PORTFOLIO_RISK_PERCENT', 8.0),
        'default_max_open_positions' => (int) env('RISK_DEFAULT_MAX_OPEN_POSITIONS', 8),
        'default_max_position_size_percent' => (float) env('RISK_DEFAULT_MAX_POSITION_SIZE_PERCENT', 25.0),
        'default_max_sector_exposure_percent' => (float) env('RISK_DEFAULT_MAX_SECTOR_EXPOSURE_PERCENT', 40.0),
        'default_max_correlated_positions' => (int) env('RISK_DEFAULT_MAX_CORRELATED_POSITIONS', 3),
        'default_allow_pyramiding' => (bool) env('RISK_DEFAULT_ALLOW_PYRAMIDING', false),
    ],
    'regime' => [
        'high_volatility_threshold' => (float) env('REGIME_HIGH_VOLATILITY_THRESHOLD', 38.0),
        'rules' => [
            'bull' => [
                'min_score' => (float) env('REGIME_BULL_MIN_SCORE', 70),
                'max_calls' => (int) env('REGIME_BULL_MAX_CALLS', 7),
            ],
            'neutral' => [
                'min_score' => (float) env('REGIME_NEUTRAL_MIN_SCORE', 75),
                'max_calls' => (int) env('REGIME_NEUTRAL_MAX_CALLS', 5),
            ],
            'correction' => [
                'min_score' => (float) env('REGIME_CORRECTION_MIN_SCORE', 80),
                'max_calls' => (int) env('REGIME_CORRECTION_MAX_CALLS', 2),
            ],
            'bear' => [
                'min_score' => (float) env('REGIME_BEAR_MIN_SCORE', 80),
                'max_calls' => (int) env('REGIME_BEAR_MAX_CALLS', 2),
            ],
            'high_volatility' => [
                'min_score' => (float) env('REGIME_HIGH_VOL_MIN_SCORE', 82),
                'max_calls' => (int) env('REGIME_HIGH_VOL_MAX_CALLS', 2),
            ],
        ],
        'context_scores' => [
            'bull' => (float) env('REGIME_CONTEXT_SCORE_BULL', 86),
            'neutral' => (float) env('REGIME_CONTEXT_SCORE_NEUTRAL', 70),
            'correction' => (float) env('REGIME_CONTEXT_SCORE_CORRECTION', 48),
            'bear' => (float) env('REGIME_CONTEXT_SCORE_BEAR', 35),
            'high_volatility' => (float) env('REGIME_CONTEXT_SCORE_HIGH_VOL', 42),
        ],
    ],
    'confidence' => [
        'weights' => [
            'technical' => (float) env('CONFIDENCE_WEIGHT_TECHNICAL', 0.5),
            'expectancy' => (float) env('CONFIDENCE_WEIGHT_EXPECTANCY', 0.3),
            'market_context' => (float) env('CONFIDENCE_WEIGHT_MARKET_CONTEXT', 0.2),
        ],
        'expectancy_min' => (float) env('CONFIDENCE_EXPECTANCY_MIN', -5.0),
        'expectancy_max' => (float) env('CONFIDENCE_EXPECTANCY_MAX', 5.0),
    ],
    'correlations' => [
        'lookback_days' => (int) env('CORRELATION_LOOKBACK_DAYS', 90),
        'high_threshold' => (float) env('CORRELATION_HIGH_THRESHOLD', 0.75),
    ],
    'alerts' => [
        'near_stop_threshold_percent' => (float) env('ALERT_NEAR_STOP_THRESHOLD_PERCENT', 1.5),
        'near_target_threshold_percent' => (float) env('ALERT_NEAR_TARGET_THRESHOLD_PERCENT', 2.0),
        'confidence_drop_threshold' => (float) env('ALERT_CONFIDENCE_DROP_THRESHOLD', 12.0),
    ],
];
