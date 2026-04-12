<?php

declare(strict_types=1);

namespace App\Services\Execution;

use App\Contracts\BrokerIntegrationInterface;

class OrderExecutionService
{
    public function __construct(private readonly BrokerIntegrationInterface $brokerIntegration)
    {
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    public function execute(array $order): array
    {
        return $this->brokerIntegration->placeOrder($order);
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(string $externalOrderId): array
    {
        return $this->brokerIntegration->cancelOrder($externalOrderId);
    }
}
