<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SetupCode;
use App\Models\SetupMetric;
use Illuminate\Database\Seeder;

class SetupMetricSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SetupCode::cases() as $setupCode) {
            SetupMetric::query()->updateOrCreate([
                'setup_code' => $setupCode->value,
            ], [
                'total_trades' => 0,
                'wins' => 0,
                'losses' => 0,
                'winrate' => 0,
                'avg_gain' => 0,
                'avg_loss' => 0,
                'expectancy' => 0,
                'edge' => 0,
                'is_enabled' => true,
            ]);
        }
    }
}
