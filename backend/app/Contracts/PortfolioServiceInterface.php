<?php

declare(strict_types=1);

namespace App\Contracts;

interface PortfolioServiceInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(int $userId): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOpen(int $userId): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listClosed(int $userId): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function create(int $userId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function update(int $userId, int $positionId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function close(int $userId, int $positionId, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function partialClose(int $userId, int $positionId, array $payload): array;

    public function refreshMarkToMarket(int $userId): int;
}
