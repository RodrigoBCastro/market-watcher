<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MacroSnapshot;
use Illuminate\Database\Seeder;

class MacroSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        MacroSnapshot::query()->updateOrCreate([
            'snapshot_date' => now()->toDateString(),
        ], [
            'usd_brl' => 5.35,
            'ibov_close' => 128500.00,
            'market_bias' => 'neutro',
            'source' => 'seed',
            'raw_payload' => ['seeded' => true],
        ]);
    }
}
