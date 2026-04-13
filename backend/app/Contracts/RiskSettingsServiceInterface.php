<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\RiskSettingDTO;

interface RiskSettingsServiceInterface
{
    public function getForUser(int $userId): RiskSettingDTO;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateForUser(int $userId, array $payload): RiskSettingDTO;
}
