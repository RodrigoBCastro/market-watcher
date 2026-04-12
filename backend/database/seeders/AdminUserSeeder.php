<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => 'admin@marketwatcher.local',
        ], [
            'name' => 'Admin MarketWatcher',
            'password' => Hash::make('Admin@123456'),
            'is_admin' => true,
        ]);
    }
}
