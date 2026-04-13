<?php

declare(strict_types=1);

namespace App\Contracts;

interface AssetUniverseBootstrapServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function bootstrapDataUniverse(array $filters = [], ?int $changedByUserId = null): array;
}

