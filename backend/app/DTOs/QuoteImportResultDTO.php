<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class QuoteImportResultDTO
{
    public function __construct(
        public int $processed,
        public ?string $earliestTradeDate,
        public ?string $latestTradeDate,
    ) {
    }
}
