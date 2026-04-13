<?php

declare(strict_types=1);

namespace App\Contracts;

interface CorrelationAnalysisServiceInterface
{
    /**
     * @param  array<int, string>  $tickers
     * @return array<int, array<string, mixed>>
     */
    public function correlationsForTickers(array $tickers, int $lookbackDays = 90): array;

    /**
     * @param  array<int, string>  $tickers
     * @return array<string, mixed>
     */
    public function highCorrelationSummary(array $tickers, int $lookbackDays = 90): array;
}
