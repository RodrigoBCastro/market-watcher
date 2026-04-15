<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\RiskSettingRepositoryInterface;
use App\Models\RiskSetting;

class EloquentRiskSettingRepository implements RiskSettingRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $defaults
     */
    public function findOrCreateForUser(int $userId, array $defaults = []): RiskSetting
    {
        /** @var RiskSetting $setting */
        $setting = RiskSetting::query()->firstOrCreate(
            ['user_id' => $userId],
            $defaults,
        );

        return $setting;
    }

    public function save(RiskSetting $settings): void
    {
        $settings->save();
    }
}
