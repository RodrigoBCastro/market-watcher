<?php

declare(strict_types=1);

namespace App\Services\Calls;

use App\Enums\TradeOutcomeResult;

class TradeOutcomeEvaluatorService
{
    /**
     * @param  array<int, array<string, mixed>>  $quotes
     * @return array<string, float|int|string>|null
     */
    public function evaluate(
        array $quotes,
        float $entry,
        float $stop,
        float $target,
        int $maxHoldingDays,
        bool $allowTimeoutExit = true,
    ): ?array {
        if ($entry <= 0 || $stop <= 0 || $target <= 0 || $quotes === []) {
            return null;
        }

        $window = array_slice($quotes, 0, max($maxHoldingDays, 1));

        foreach ($window as $index => $quote) {
            $high = (float) ($quote['high'] ?? 0.0);
            $low = (float) ($quote['low'] ?? 0.0);

            if ($low <= $stop && $high >= $target) {
                return $this->payload(TradeOutcomeResult::LOSS->value, $entry, $stop, $index + 1);
            }

            if ($low <= $stop) {
                return $this->payload(TradeOutcomeResult::LOSS->value, $entry, $stop, $index + 1);
            }

            if ($high >= $target) {
                return $this->payload(TradeOutcomeResult::WIN->value, $entry, $target, $index + 1);
            }
        }

        if (! $allowTimeoutExit) {
            return null;
        }

        $last = $window[count($window) - 1] ?? null;
        if ($last === null) {
            return null;
        }

        $exit = (float) ($last['close'] ?? 0.0);
        if ($exit <= 0.0) {
            return null;
        }

        $result = $exit >= $entry ? TradeOutcomeResult::WIN->value : TradeOutcomeResult::LOSS->value;

        return $this->payload($result, $entry, $exit, count($window));
    }

    /**
     * @return array<string, float|int|string>
     */
    private function payload(string $result, float $entry, float $exit, int $durationDays): array
    {
        $pnl = (($exit - $entry) / $entry) * 100;

        return [
            'result' => $result,
            'exit_price' => round($exit, 4),
            'pnl_percent' => round($pnl, 4),
            'duration_days' => $durationDays,
        ];
    }
}
