<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\BacktestEngineInterface;
use Illuminate\Console\Command;

class RunBacktestCommand extends Command
{
    protected $signature = 'market:run-backtest
        {strategy=default_quant : Nome da estratégia}
        {--from= : Data inicial (YYYY-MM-DD)}
        {--to= : Data final (YYYY-MM-DD)}
        {--holding=20 : Máximo de dias em posição}';

    protected $description = 'Executa backtest do motor de calls e persiste resultado';

    public function handle(BacktestEngineInterface $backtestEngine): int
    {
        $strategy = (string) $this->argument('strategy');

        $result = $backtestEngine->run($strategy, [
            'from' => $this->option('from'),
            'to' => $this->option('to'),
            'max_holding_days' => (int) $this->option('holding'),
        ]);

        $this->info('Backtest finalizado.');
        $this->line('Strategy: '.$result->strategyName);
        $this->line('Total trades: '.$result->totalTrades);
        $this->line('Winrate: '.$result->winrate.'%');
        $this->line('Total return: '.$result->totalReturn.'%');
        $this->line('Max drawdown: '.$result->maxDrawdown.'%');
        $this->line('Profit factor: '.($result->profitFactor ?? 'N/A'));

        return self::SUCCESS;
    }
}
