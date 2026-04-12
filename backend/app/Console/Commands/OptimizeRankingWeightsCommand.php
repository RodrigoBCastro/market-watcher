<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\ScoreOptimizerInterface;
use Illuminate\Console\Command;

class OptimizeRankingWeightsCommand extends Command
{
    protected $signature = 'market:optimize-ranking-weights {--apply : Aplica automaticamente os melhores pesos encontrados}';

    protected $description = 'Testa combinações de pesos para ranking técnico x expectancy';

    public function handle(ScoreOptimizerInterface $scoreOptimizer): int
    {
        $result = $scoreOptimizer->optimize();

        $this->info('Otimização concluída.');
        $this->line('Best technical weight: '.$result->bestWeights['technical_weight']);
        $this->line('Best expectancy weight: '.$result->bestWeights['expectancy_weight']);
        $this->line('Selected trades: '.$result->selectedTrades);
        $this->line('Performance score: '.$result->performanceScore);

        if ((bool) $this->option('apply')) {
            $scoreOptimizer->apply($result->bestWeights);
            $this->info('Pesos aplicados e salvos em cache.');
        }

        return self::SUCCESS;
    }
}
