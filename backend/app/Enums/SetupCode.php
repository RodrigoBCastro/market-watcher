<?php

declare(strict_types=1);

namespace App\Enums;

enum SetupCode: string
{
    case PULLBACK_EMA21 = 'PULLBACK_EMA21';
    case PULLBACK_SMA50 = 'PULLBACK_SMA50';
    case BREAKOUT_20D = 'BREAKOUT_20D';
    case CONSOLIDATION_BREAK = 'CONSOLIDATION_BREAK';
    case EXTENDED_ASSET = 'EXTENDED_ASSET';
    case SIDEWAYS_NO_EDGE = 'SIDEWAYS_NO_EDGE';
    case RISK_TOO_HIGH = 'RISK_TOO_HIGH';

    public function label(): string
    {
        return match ($this) {
            self::PULLBACK_EMA21 => 'Pullback na EMA 21 em tendência',
            self::PULLBACK_SMA50 => 'Pullback na SMA 50 com defesa',
            self::BREAKOUT_20D => 'Rompimento de máxima de 20 períodos',
            self::CONSOLIDATION_BREAK => 'Rompimento de consolidação construtiva',
            self::EXTENDED_ASSET => 'Ativo esticado sem timing',
            self::SIDEWAYS_NO_EDGE => 'Lateralização sem vantagem estatística',
            self::RISK_TOO_HIGH => 'Risco técnico acima do limite operacional',
        };
    }
}
