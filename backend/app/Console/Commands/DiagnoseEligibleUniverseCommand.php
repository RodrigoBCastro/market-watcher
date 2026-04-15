<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\MarketUniverseServiceInterface;
use Illuminate\Console\Command;

class DiagnoseEligibleUniverseCommand extends Command
{
    protected $signature = 'market:diagnose-eligible-universe
                            {--ticker= : Diagnostica apenas um ativo específico (ex: PETR4)}';

    protected $description = 'Diagnóstico read-only do Eligible Universe: mostra por que cada ativo passa ou falha nos critérios de elegibilidade';

    public function __construct(private readonly MarketUniverseServiceInterface $marketUniverseService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tickerFilter = $this->option('ticker')
            ? strtoupper((string) $this->option('ticker'))
            : null;

        $this->line('');
        $this->line('<fg=cyan>MarketWatcher — Diagnóstico Eligible Universe</>');
        $this->line('<fg=cyan>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>');
        $this->line('');

        $diagnosis = $this->marketUniverseService->diagnoseEligibleUniverse();

        // Thresholds ativos
        $t = $diagnosis['thresholds'];
        $this->line('<fg=yellow>Thresholds ativos:</>');
        $this->line("  Histórico mín.        : {$t['min_history_days']} dias de pregão");
        $this->line('  Volume mín.           : ' . number_format($t['min_avg_daily_volume'], 0, ',', '.') . ' ações/dia');
        $this->line('  Volume financeiro mín.: R$ ' . number_format($t['min_avg_daily_financial_volume'], 0, ',', '.') . '/dia');
        $this->line("  Spread máx. (range)   : {$t['max_avg_spread_percent']}%");
        $this->line("  Volatilidade          : [{$t['min_volatility_20']}% – {$t['max_volatility_20']}%]");
        $this->line("  Operability mín.      : {$t['min_operability_score']}");
        $this->line('');

        // Filtra se --ticker foi passado
        $assets = $diagnosis['assets'];
        if ($tickerFilter !== null) {
            $assets = array_filter($assets, static fn (array $a): bool => $a['ticker'] === $tickerFilter);
            if (empty($assets)) {
                $this->error("Ativo '{$tickerFilter}' não encontrado ou inativo.");
                return self::FAILURE;
            }
        }

        // Monta tabela
        $headers = ['Ticker', 'Hist.', 'Volume', 'Vol. Fin. (R$)', 'Spread', 'Volatil.', 'Operab.', 'STATUS'];
        $rows    = [];

        foreach ($assets as $asset) {
            if (! $asset['collect_data']) {
                $rows[] = [
                    $asset['ticker'],
                    '—', '—', '—', '—', '—', '—',
                    '<fg=red>FAIL — sem coleta</>',
                ];
                continue;
            }

            $m      = $asset['metrics'];
            $failed = $asset['failed_checks'];

            $rows[] = [
                $asset['ticker'],
                $this->fmt($m['history_count'],                    null, in_array('histórico insuficiente', $failed)),
                $this->fmt($m['avg_daily_volume_20'],              0,    in_array('volume médio diário abaixo do mínimo', $failed)),
                $this->fmtMoney($m['avg_daily_financial_volume_20'],     in_array('volume financeiro médio abaixo do mínimo', $failed)),
                $this->fmt($m['avg_spread_percent'],               2,    in_array('spread médio acima do limite', $failed), '%'),
                $this->fmt($m['volatility_20'],                    2,    in_array('volatilidade fora da faixa operacional', $failed), '%'),
                $this->fmt($m['operability_score'],                1,    in_array('operability score abaixo do mínimo', $failed)),
                $asset['eligible'] ? '<fg=green>PASS</>' : '<fg=red>FAIL</>',
            ];
        }

        $this->table($headers, $rows);
        $this->line('');

        // Resumo de falhas (somente se não filtrado por ticker)
        if ($tickerFilter === null && ! empty($diagnosis['failure_counts'])) {
            $total = $diagnosis['summary']['total'];
            $this->line('<fg=yellow>MOTIVOS DE FALHA (por frequência):</>');
            foreach ($diagnosis['failure_counts'] as $reason => $count) {
                $pct  = $total > 0 ? round($count / $total * 100, 1) : 0;
                $bar  = str_pad($reason, 46);
                $this->line("  {$bar}  <fg=red>{$count} ativos</> ({$pct}%)");
            }
            $this->line('');
        }

        // Totais finais
        $s = $diagnosis['summary'];
        $this->line("<fg=green>Elegíveis  : {$s['eligible']}</>");
        $this->line("<fg=red>Inelegíveis: {$s['ineligible']}</>");
        $this->line('');

        return self::SUCCESS;
    }

    private function fmt(float|int $value, ?int $decimals, bool $failed, string $suffix = ''): string
    {
        $formatted = $decimals === null
            ? number_format((int) $value, 0, ',', '.')
            : number_format((float) $value, $decimals, ',', '.');

        $display = ($failed ? '✗' : '✓') . ' ' . $formatted . $suffix;

        return $failed ? "<fg=red>{$display}</>" : "<fg=green>{$display}</>";
    }

    private function fmtMoney(float $value, bool $failed): string
    {
        if ($value >= 1_000_000_000) {
            $formatted = number_format($value / 1_000_000_000, 2, ',', '.') . 'B';
        } elseif ($value >= 1_000_000) {
            $formatted = number_format($value / 1_000_000, 1, ',', '.') . 'M';
        } else {
            $formatted = number_format($value, 0, ',', '.');
        }

        $display = ($failed ? '✗' : '✓') . ' ' . $formatted;

        return $failed ? "<fg=red>{$display}</>" : "<fg=green>{$display}</>";
    }
}
