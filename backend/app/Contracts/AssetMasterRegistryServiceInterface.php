<?php

declare(strict_types=1);

namespace App\Contracts;

interface AssetMasterRegistryServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array;

    /**
     * @return array<string, mixed>
     */
    public function getBySymbol(string $symbol): array;

    /**
     * @return array<string, mixed>
     */
    public function synchronizeFromProvider(): array;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function listIndexes(array $filters = []): array;
}

