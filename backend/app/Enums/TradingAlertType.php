<?php

declare(strict_types=1);

namespace App\Enums;

enum TradingAlertType: string
{
    case CALL_NEAR_STOP = 'call_near_stop';
    case CALL_NEAR_TARGET = 'call_near_target';
    case CALL_INVALIDATED = 'call_invalidated';
    case PORTFOLIO_RISK_LIMIT = 'portfolio_risk_limit';
    case HIGH_CORRELATION = 'high_correlation';
    case SECTOR_CONCENTRATION = 'sector_concentration';
    case CONFIDENCE_DROPPING = 'confidence_dropping';
    case SETUP_DETERIORATING = 'setup_deteriorating';
    case REGIME_WORSENING = 'regime_worsening';
}
