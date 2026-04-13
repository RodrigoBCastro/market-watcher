<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\PortfolioRiskServiceInterface;
use App\Contracts\PositionSizingServiceInterface;
use App\Contracts\RiskSettingsServiceInterface;
use App\DTOs\PositionSizingResultDTO;

class PositionSizingService implements PositionSizingServiceInterface
{
    public function __construct(
        private readonly RiskSettingsServiceInterface $riskSettingsService,
        private readonly PortfolioRiskServiceInterface $portfolioRiskService,
    ) {
    }

    public function calculateForUser(int $userId, array $payload): PositionSizingResultDTO
    {
        $settings = $this->riskSettingsService->getForUser($userId);

        $entryPrice = (float) ($payload['entry_price'] ?? 0.0);

        if ($entryPrice <= 0.0) {
            throw new \InvalidArgumentException('entry_price deve ser maior que zero.');
        }

        $stopDistancePercent = $this->resolveStopDistancePercent($payload, $entryPrice);

        if ($stopDistancePercent <= 0.0) {
            throw new \InvalidArgumentException('stop_distance_percent deve ser maior que zero.');
        }

        $capitalTotal = (float) ($payload['capital_total'] ?? $settings->totalCapital);
        $riskPerTradePercent = (float) ($payload['risk_per_trade_percent'] ?? $settings->riskPerTradePercent);

        if ($capitalTotal <= 0.0 || $riskPerTradePercent <= 0.0) {
            throw new \InvalidArgumentException('capital_total e risk_per_trade_percent devem ser maiores que zero.');
        }

        $riskAmount = $capitalTotal * ($riskPerTradePercent / 100);
        $basePositionValue = $riskAmount / ($stopDistancePercent / 100);

        $maxPositionValue = $capitalTotal * ($settings->maxPositionSizePercent / 100);

        $riskSummary = $this->portfolioRiskService->summary($userId);
        $capitalFree = (float) ($payload['available_capital'] ?? ($riskSummary['capital_free'] ?? $capitalTotal));

        $cappedValue = min($basePositionValue, $maxPositionValue, max(0.0, $capitalFree));
        $cappedByRiskRules = $cappedValue < $basePositionValue;

        $warnings = [];

        if ($basePositionValue > $maxPositionValue) {
            $warnings[] = 'Posição limitada pelo limite máximo por ativo.';
        }

        if ($basePositionValue > $capitalFree) {
            $warnings[] = 'Posição limitada pelo capital livre disponível.';
        }

        $shares = floor($cappedValue / $entryPrice);

        if ($shares <= 0) {
            $shares = 0;
            $cappedValue = 0.0;
            $warnings[] = 'Capital insuficiente para comprar ao menos 1 unidade no preço de entrada.';
        } else {
            $cappedValue = $shares * $entryPrice;
        }

        $allocationPercent = $capitalTotal > 0
            ? ($cappedValue / $capitalTotal) * 100
            : 0.0;

        return new PositionSizingResultDTO(
            capitalTotal: round($capitalTotal, 2),
            riskPerTradePercent: round($riskPerTradePercent, 4),
            riskAmount: round($riskAmount, 2),
            stopDistancePercent: round($stopDistancePercent, 4),
            suggestedPositionValue: round($cappedValue, 2),
            suggestedSharesQuantity: (float) $shares,
            allocationPercent: round($allocationPercent, 4),
            cappedByRiskRules: $cappedByRiskRules,
            warnings: $warnings,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveStopDistancePercent(array $payload, float $entryPrice): float
    {
        if (isset($payload['stop_distance_percent']) && (float) $payload['stop_distance_percent'] > 0.0) {
            return (float) $payload['stop_distance_percent'];
        }

        if (! isset($payload['stop_price'])) {
            return 0.0;
        }

        $stopPrice = (float) $payload['stop_price'];

        if ($stopPrice <= 0.0 || $entryPrice <= 0.0) {
            return 0.0;
        }

        return abs((($entryPrice - $stopPrice) / $entryPrice) * 100);
    }
}
