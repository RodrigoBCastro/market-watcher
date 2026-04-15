<?php

declare(strict_types=1);

namespace App\Services\Briefing;

use App\Contracts\AssetAnalysisScoreRepositoryInterface;
use App\Contracts\DailyBriefGeneratorInterface;
use App\Contracts\MacroSnapshotRepositoryInterface;
use App\DTOs\DailyBriefDTO;
use Carbon\CarbonImmutable;

class DailyBriefGenerator implements DailyBriefGeneratorInterface
{
    public function __construct(
        private readonly MacroSnapshotRepositoryInterface      $macroSnapshotRepository,
        private readonly AssetAnalysisScoreRepositoryInterface $scoreRepository,
    ) {
    }

    public function generate(\DateTimeInterface $briefDate): DailyBriefDTO
    {
        $date = CarbonImmutable::instance((\DateTimeImmutable::createFromInterface($briefDate)))->toDateString();

        $macro      = $this->macroSnapshotRepository->findLatestUpToDate($date);
        $marketBias = (string) ($macro?->market_bias ?? 'neutro');
        $scores     = $this->scoreRepository->findAllByDate($date);

        $rankedIdeas = $scores
            ->filter(static fn ($score): bool => $score->recommendation !== 'evitar')
            ->take(10)
            ->values()
            ->map(static function ($score, int $index): array {
                return [
                    'symbol'         => $score->monitoredAsset?->ticker,
                    'rank_position'  => $index + 1,
                    'final_score'    => (float) $score->final_score,
                    'classification' => $score->classification,
                    'recommendation' => $score->recommendation,
                    'setup_label'    => $score->setup_label,
                    'entry'          => $score->suggested_entry,
                    'stop'           => $score->suggested_stop,
                    'target'         => $score->suggested_target,
                    'risk_percent'   => $score->risk_percent,
                    'reward_percent' => $score->reward_percent,
                    'rr_ratio'       => $score->rr_ratio,
                    'rationale'      => $score->rationale,
                    'alert_flags'    => $score->alert_flags,
                ];
            })->all();

        $avoidList = $scores
            ->filter(static fn ($score): bool => $score->recommendation === 'evitar')
            ->take(10)
            ->values()
            ->map(static fn ($score): array => [
                'symbol'         => $score->monitoredAsset?->ticker,
                'final_score'    => (float) $score->final_score,
                'classification' => $score->classification,
                'recommendation' => $score->recommendation,
                'setup_label'    => $score->setup_label,
                'rationale'      => $score->rationale,
                'alert_flags'    => $score->alert_flags,
            ])->all();

        $marketSummary = $this->buildMarketSummary($marketBias, count($rankedIdeas), count($avoidList));
        $ibovAnalysis  = $this->buildIbovAnalysis($marketBias, $macro?->ibov_close);
        $riskNotes     = $this->buildRiskNotes($marketBias, $avoidList);
        $conclusion    = $this->buildConclusion($marketBias, $rankedIdeas);

        return new DailyBriefDTO(
            briefDate: CarbonImmutable::parse($date),
            marketBias: $marketBias,
            marketSummary: $marketSummary,
            ibovAnalysis: $ibovAnalysis,
            riskNotes: $riskNotes,
            conclusion: $conclusion,
            rankedIdeas: $rankedIdeas,
            avoidList: $avoidList,
        );
    }

    private function buildMarketSummary(string $marketBias, int $opportunities, int $avoidCount): string
    {
        $tone = match ($marketBias) {
            'favoravel'              => 'ambiente construtivo para operações de continuidade',
            'cautelosamente_favoravel' => 'ambiente favorável, porém com necessidade de seletividade',
            'fraco'                  => 'ambiente defensivo com baixa assimetria',
            default                  => 'ambiente neutro e seletivo',
        };

        return "Mercado em {$tone}. {$opportunities} ativos aparecem com viabilidade operacional e {$avoidCount} exigem cautela elevada.";
    }

    private function buildIbovAnalysis(string $marketBias, ?float $ibovClose): string
    {
        $ibovValue = $ibovClose !== null ? number_format($ibovClose, 2, ',', '.') : 'indisponível';

        return match ($marketBias) {
            'favoravel'              => "IBOV em {$ibovValue} mantendo estrutura de alta com contexto pró-risco.",
            'cautelosamente_favoravel' => "IBOV em {$ibovValue} com viés construtivo, porém sem aceleração ampla.",
            'fraco'                  => "IBOV em {$ibovValue} sob pressão, com menor probabilidade de follow-through comprador.",
            default                  => "IBOV em {$ibovValue} em leitura neutra, favorecendo apenas entradas de maior qualidade.",
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $avoidList
     */
    private function buildRiskNotes(string $marketBias, array $avoidList): ?string
    {
        if ($marketBias === 'favoravel' && count($avoidList) < 3) {
            return 'Risco controlado; manter disciplina de stop e evitar perseguir ativos esticados.';
        }

        if ($marketBias === 'fraco') {
            return 'Contexto fraco: reduzir exposição, priorizar caixa e evitar rompimentos sem volume.';
        }

        return 'Priorizar setups com stop <= 4%, alvo >= 6% e volume de confirmação acima da média.';
    }

    /**
     * @param  array<int, array<string, mixed>>  $rankedIdeas
     */
    private function buildConclusion(string $marketBias, array $rankedIdeas): string
    {
        if ($rankedIdeas === []) {
            return 'Sem oportunidades robustas hoje. Postura recomendada: preservação de capital e observação.';
        }

        if ($marketBias === 'fraco') {
            return 'Mesmo com oportunidades pontuais, o contexto pede entradas menores e execução conservadora.';
        }

        return 'Dia operacional com foco em seletividade: executar apenas ativos com confirmação de setup e volume.';
    }
}
