<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\RiskSetting;

interface RiskSettingRepositoryInterface
{
    /**
     * Returns the existing RiskSetting for the user or creates one with the
     * provided defaults.
     *
     * @param  array<string, mixed>  $defaults
     */
    public function findOrCreateForUser(int $userId, array $defaults = []): RiskSetting;

    public function save(RiskSetting $settings): void;
}
