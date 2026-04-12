<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MonitoredAsset;
use Illuminate\Database\Seeder;

class MonitoredAssetSeeder extends Seeder
{
    public function run(): void
    {
        $assets = [
            ['ticker' => 'PETR4', 'name' => 'Petrobras PN', 'sector' => 'Energia e Petróleo'],
            ['ticker' => 'PRIO3', 'name' => 'PetroRio ON', 'sector' => 'Energia e Petróleo'],
            ['ticker' => 'VBBR3', 'name' => 'Vibra Energia ON', 'sector' => 'Energia e Petróleo'],
            ['ticker' => 'CPLE3', 'name' => 'Copel ON', 'sector' => 'Energia e Petróleo'],
            ['ticker' => 'VALE3', 'name' => 'Vale ON', 'sector' => 'Mineração e Siderurgia'],
            ['ticker' => 'CSNA3', 'name' => 'CSN ON', 'sector' => 'Mineração e Siderurgia'],
            ['ticker' => 'GGBR4', 'name' => 'Gerdau PN', 'sector' => 'Mineração e Siderurgia'],
            ['ticker' => 'GOAU4', 'name' => 'Metalúrgica Gerdau PN', 'sector' => 'Mineração e Siderurgia'],
            ['ticker' => 'ITUB4', 'name' => 'Itaú Unibanco PN', 'sector' => 'Financeiro'],
            ['ticker' => 'BBDC4', 'name' => 'Bradesco PN', 'sector' => 'Financeiro'],
            ['ticker' => 'BBAS3', 'name' => 'Banco do Brasil ON', 'sector' => 'Financeiro'],
            ['ticker' => 'BPAC11', 'name' => 'BTG Pactual UNT', 'sector' => 'Financeiro'],
            ['ticker' => 'SLCE3', 'name' => 'SLC Agrícola ON', 'sector' => 'Agronegócio'],
            ['ticker' => 'AGRO3', 'name' => 'BrasilAgro ON', 'sector' => 'Agronegócio'],
            ['ticker' => 'ABEV3', 'name' => 'Ambev ON', 'sector' => 'Consumo e Construção'],
            ['ticker' => 'CYRE3', 'name' => 'Cyrela ON', 'sector' => 'Consumo e Construção'],
            ['ticker' => 'FLRY3', 'name' => 'Fleury ON', 'sector' => 'Saúde'],
            ['ticker' => 'INTB3', 'name' => 'Intelbras ON', 'sector' => 'Tecnologia'],
            ['ticker' => 'TOTS3', 'name' => 'Totvs ON', 'sector' => 'Tecnologia'],
            ['ticker' => 'SBSP3', 'name' => 'Sabesp ON', 'sector' => 'Saneamento'],
        ];

        foreach ($assets as $asset) {
            MonitoredAsset::query()->updateOrCreate([
                'ticker' => $asset['ticker'],
            ], [
                'name' => $asset['name'],
                'sector' => $asset['sector'],
                'is_active' => true,
                'monitoring_enabled' => true,
                'metadata' => ['seeded' => true],
            ]);
        }
    }
}
