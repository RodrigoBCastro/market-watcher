<?php

declare(strict_types=1);

namespace App\Contracts;

interface BrokerIntegrationInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function placeOrder(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function cancelOrder(string $externalOrderId): array;
}
