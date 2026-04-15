<?php

declare(strict_types=1);

namespace App\Services\Analysis;

use App\Contracts\MacroSnapshotRepositoryInterface;
use App\Contracts\MarketIndexRepositoryInterface;
use Carbon\CarbonImmutable;

class MarketContextService
{
    public function __construct(
        private readonly MacroSnapshotRepositoryInterface $macroSnapshotRepository,
        private readonly MarketIndexRepositoryInterface   $marketIndexRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(?\DateTimeInterface $tradeDate = null): array
    {
        $date = $tradeDate !== null
            ? CarbonImmutable::instance((\DateTimeImmutable::createFromInterface($tradeDate)))->toDateString()
            : CarbonImmutable::now()->toDateString();

        $macro       = $this->macroSnapshotRepository->findLatestUpToDate($date);
        $ibovHistory = $this->marketIndexRepository->findBySymbolUpToDateAscending('IBOV', $date, 30);

        $marketCorrection = false;

        if ($ibovHistory->count() >= 5) {
            $last       = (float) ($ibovHistory->last()->close ?? 0.0);
            $fiveAgo    = (float) ($ibovHistory[$ibovHistory->count() - 5]->close ?? $last);
            $change     = $fiveAgo > 0 ? (($last - $fiveAgo) / $fiveAgo) * 100 : 0.0;
            $marketCorrection = $change <= -3.5;
        }

        $usdPressurePercent = 0.0;

        if ($macro !== null && $macro->usd_brl > 0 && $macro->ibov_close > 0) {
            $usdPressurePercent = (($macro->usd_brl - 5.30) / 5.30) * 100;
        }

        return [
            'trade_date'           => $date,
            'market_bias'          => $macro?->market_bias ?? 'neutro',
            'ibov_close'           => $macro?->ibov_close,
            'usd_brl'              => $macro?->usd_brl,
            'usd_pressure_percent' => $usdPressurePercent,
            'market_correction'    => $marketCorrection,
            'source'               => $macro?->source,
        ];
    }
}
