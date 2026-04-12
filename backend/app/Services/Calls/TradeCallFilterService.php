<?php

declare(strict_types=1);

namespace App\Services\Calls;

use App\Enums\SetupCode;
use App\Models\AssetAnalysisScore;
use App\Models\SetupMetric;

class TradeCallFilterService
{
    /**
     * @return array{eligible: bool, reasons: array<int, string>}
     */
    public function evaluate(AssetAnalysisScore $score, ?SetupMetric $metric): array
    {
        $reasons = [];

        $minScore = (float) config('market.calls.min_score', 70);
        $minRr = (float) config('market.calls.min_rr', 1.5);
        $maxStop = (float) config('market.limits.max_stop_percent', 4.0);
        $minTarget = (float) config('market.limits.min_target_percent', 6.0);
        $minHistory = (int) config('market.calls.min_history', 8);

        $setupCode = (string) ($score->setup_code ?? '');

        if ((float) $score->final_score < $minScore) {
            $reasons[] = 'score abaixo do mínimo';
        }

        if ($score->risk_percent === null || (float) $score->risk_percent > $maxStop) {
            $reasons[] = 'stop técnico acima do limite';
        }

        if ($score->reward_percent === null || (float) $score->reward_percent < $minTarget) {
            $reasons[] = 'target abaixo do mínimo';
        }

        if ($score->rr_ratio === null || (float) $score->rr_ratio < $minRr) {
            $reasons[] = 'relação risco/retorno insuficiente';
        }

        if ($setupCode === '') {
            $reasons[] = 'setup inválido';
        }

        if (in_array($setupCode, [
            SetupCode::EXTENDED_ASSET->value,
            SetupCode::SIDEWAYS_NO_EDGE->value,
            SetupCode::RISK_TOO_HIGH->value,
        ], true)) {
            $reasons[] = 'setup bloqueado pelo motor';
        }

        if ($metric === null) {
            $reasons[] = 'sem histórico probabilístico para setup';
        } else {
            if (! $metric->is_enabled) {
                $reasons[] = 'setup desativado por deterioração';
            }

            if ($metric->total_trades < $minHistory) {
                $reasons[] = 'histórico mínimo de setup não atingido';
            }

            if ((float) $metric->expectancy <= 0.0) {
                $reasons[] = 'expectancy não positiva';
            }

            if ((float) $metric->winrate <= 50.0) {
                $reasons[] = 'winrate abaixo de 50%';
            }
        }

        return [
            'eligible' => $reasons === [],
            'reasons' => $reasons,
        ];
    }
}
