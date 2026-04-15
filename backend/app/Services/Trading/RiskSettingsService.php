<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Contracts\RiskSettingRepositoryInterface;
use App\Contracts\RiskSettingsServiceInterface;
use App\DTOs\RiskSettingDTO;
use App\Models\RiskSetting;

class RiskSettingsService implements RiskSettingsServiceInterface
{
    public function __construct(
        private readonly RiskSettingRepositoryInterface $riskSettingRepository,
    ) {
    }

    public function getForUser(int $userId): RiskSettingDTO
    {
        $model = $this->riskSettingRepository->findOrCreateForUser($userId, $this->defaultSettings());

        return $this->toDto($model);
    }

    public function updateForUser(int $userId, array $payload): RiskSettingDTO
    {
        $model = $this->riskSettingRepository->findOrCreateForUser($userId, $this->defaultSettings());

        $model->fill([
            'total_capital'               => (float) ($payload['total_capital'] ?? $model->total_capital),
            'risk_per_trade_percent'       => (float) ($payload['risk_per_trade_percent'] ?? $model->risk_per_trade_percent),
            'max_portfolio_risk_percent'   => (float) ($payload['max_portfolio_risk_percent'] ?? $model->max_portfolio_risk_percent),
            'max_open_positions'           => (int)   ($payload['max_open_positions'] ?? $model->max_open_positions),
            'max_position_size_percent'    => (float) ($payload['max_position_size_percent'] ?? $model->max_position_size_percent),
            'max_sector_exposure_percent'  => (float) ($payload['max_sector_exposure_percent'] ?? $model->max_sector_exposure_percent),
            'max_correlated_positions'     => (int)   ($payload['max_correlated_positions'] ?? $model->max_correlated_positions),
            'allow_pyramiding'             => (bool)  ($payload['allow_pyramiding'] ?? $model->allow_pyramiding),
        ]);

        $this->riskSettingRepository->save($model);

        return $this->toDto($model->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSettings(): array
    {
        return [
            'total_capital'               => (float) config('market.risk.default_total_capital', 10000),
            'risk_per_trade_percent'       => (float) config('market.risk.default_risk_per_trade_percent', 1.0),
            'max_portfolio_risk_percent'   => (float) config('market.risk.default_max_portfolio_risk_percent', 8.0),
            'max_open_positions'           => (int)   config('market.risk.default_max_open_positions', 8),
            'max_position_size_percent'    => (float) config('market.risk.default_max_position_size_percent', 25.0),
            'max_sector_exposure_percent'  => (float) config('market.risk.default_max_sector_exposure_percent', 40.0),
            'max_correlated_positions'     => (int)   config('market.risk.default_max_correlated_positions', 3),
            'allow_pyramiding'             => (bool)  config('market.risk.default_allow_pyramiding', false),
        ];
    }

    private function toDto(RiskSetting $model): RiskSettingDTO
    {
        return new RiskSettingDTO(
            id:                      (int)   $model->id,
            userId:                  (int)   $model->user_id,
            totalCapital:            round((float) $model->total_capital, 2),
            riskPerTradePercent:     round((float) $model->risk_per_trade_percent, 4),
            maxPortfolioRiskPercent: round((float) $model->max_portfolio_risk_percent, 4),
            maxOpenPositions:        (int)   $model->max_open_positions,
            maxPositionSizePercent:  round((float) $model->max_position_size_percent, 4),
            maxSectorExposurePercent:round((float) $model->max_sector_exposure_percent, 4),
            maxCorrelatedPositions:  (int)   $model->max_correlated_positions,
            allowPyramiding:         (bool)  $model->allow_pyramiding,
        );
    }
}
