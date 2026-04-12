<?php

declare(strict_types=1);

namespace App\Enums;

enum ScoreClassification: string
{
    case EXCELLENT = 'Excelente entrada';
    case GOOD = 'Boa entrada';
    case NEUTRAL = 'Neutra / seletiva';
    case WEAK = 'Fraca';
    case AVOID = 'Evitar';

    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 85 => self::EXCELLENT,
            $score >= 70 => self::GOOD,
            $score >= 55 => self::NEUTRAL,
            $score >= 40 => self::WEAK,
            default => self::AVOID,
        };
    }
}
