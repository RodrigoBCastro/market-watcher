<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\BootstrapDataUniverseFromMasterJob;
use Illuminate\Console\Command;

class BootstrapDataUniverseCommand extends Command
{
    protected $signature = 'market:bootstrap-data-universe
        {--types=stock : Tipos de ativos separados por vírgula}
        {--sectors= : Setores separados por vírgula}
        {--price-min= : Preço mínimo}
        {--market-cap-min= : Market cap mínimo}
        {--volume-min= : Volume mínimo}
        {--limit=1000 : Limite de ativos}
        {--now : Executa imediatamente sem fila}';

    protected $description = 'Promove ativos do cadastro mestre para o Data Universe (monitored_assets).';

    public function handle(): int
    {
        $runNow = (bool) $this->option('now');

        $filters = [
            'asset_types' => $this->parseCsv((string) $this->option('types')),
            'sectors' => $this->parseCsv((string) $this->option('sectors')),
            'price_min' => $this->nullableFloat($this->option('price-min')),
            'market_cap_min' => $this->nullableFloat($this->option('market-cap-min')),
            'volume_min' => $this->nullableFloat($this->option('volume-min')),
            'limit' => (int) $this->option('limit'),
        ];

        if ($runNow) {
            BootstrapDataUniverseFromMasterJob::dispatchSync($filters);
            $this->info('Bootstrap do Data Universe executado em modo síncrono.');

            return self::SUCCESS;
        }

        BootstrapDataUniverseFromMasterJob::dispatch($filters);
        $this->info('Job de bootstrap do Data Universe enfileirado.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function parseCsv(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map(static fn (string $item): string => trim($item), explode(',', $value))));
    }

    /**
     * @param  mixed  $value
     */
    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}

