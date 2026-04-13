<?php

declare(strict_types=1);

namespace App\Enums;

enum AssetType: string
{
    case STOCK = 'stock';
    case FUND = 'fund';
    case BDR = 'bdr';
    case INDEX = 'index';
    case UNKNOWN = 'unknown';

    public static function normalize(?string $rawType, ?string $symbol = null): self
    {
        $value = strtolower(trim((string) $rawType));
        $symbol = strtoupper(trim((string) $symbol));

        if ($value === '' && str_starts_with($symbol, '^')) {
            return self::INDEX;
        }

        if (str_contains($value, 'index') || str_contains($value, 'indice')) {
            return self::INDEX;
        }

        if (str_contains($value, 'bdr')) {
            return self::BDR;
        }

        if (
            str_contains($value, 'fund')
            || str_contains($value, 'fii')
            || str_contains($value, 'etf')
            || str_contains($value, 'fiagro')
        ) {
            return self::FUND;
        }

        if (
            $value === 'stock'
            || str_contains($value, 'equity')
            || str_contains($value, 'unit')
            || str_contains($value, 'on')
            || str_contains($value, 'pn')
        ) {
            return self::STOCK;
        }

        return self::UNKNOWN;
    }
}

