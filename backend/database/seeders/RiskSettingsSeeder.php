<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\RiskSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class RiskSettingsSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->orderBy('id')->each(static function (User $user): void {
            RiskSetting::query()->updateOrCreate([
                'user_id' => $user->id,
            ], [
                'total_capital' => (float) config('market.risk.default_total_capital', 10000),
                'risk_per_trade_percent' => (float) config('market.risk.default_risk_per_trade_percent', 1.0),
                'max_portfolio_risk_percent' => (float) config('market.risk.default_max_portfolio_risk_percent', 8.0),
                'max_open_positions' => (int) config('market.risk.default_max_open_positions', 8),
                'max_position_size_percent' => (float) config('market.risk.default_max_position_size_percent', 25.0),
                'max_sector_exposure_percent' => (float) config('market.risk.default_max_sector_exposure_percent', 40.0),
                'max_correlated_positions' => (int) config('market.risk.default_max_correlated_positions', 3),
                'allow_pyramiding' => (bool) config('market.risk.default_allow_pyramiding', false),
            ]);
        });
    }
}
