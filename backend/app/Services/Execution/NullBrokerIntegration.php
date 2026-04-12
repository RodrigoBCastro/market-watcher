<?php

declare(strict_types=1);

namespace App\Services\Execution;

use App\Contracts\BrokerIntegrationInterface;

class NullBrokerIntegration implements BrokerIntegrationInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function placeOrder(array $payload): array
    {
        return [
            'ok' => false,
            'message' => 'BrokerIntegrationInterface ainda sem implementação de corretora.',
            'payload' => $payload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelOrder(string $externalOrderId): array
    {
        return [
            'ok' => false,
            'message' => 'BrokerIntegrationInterface ainda sem implementação de cancelamento.',
            'external_order_id' => $externalOrderId,
        ];
    }
}
