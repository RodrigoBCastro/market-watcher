<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\TradeCallDTO;

interface TradeCallServiceInterface
{
    /**
     * @return array<int, TradeCallDTO>
     */
    public function generateDraftCalls(?\DateTimeInterface $referenceDate = null): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCalls(?string $status = null, int $limit = 100): array;

    public function getCall(int $id): TradeCallDTO;

    public function approve(int $id, int $reviewerId, ?string $comments = null): TradeCallDTO;

    public function reject(int $id, int $reviewerId, ?string $comments = null): TradeCallDTO;

    public function publish(int $id): TradeCallDTO;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOutcomes(int $limit = 100): array;
}
