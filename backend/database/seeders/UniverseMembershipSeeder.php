<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UniverseType;
use App\Models\MarketUniverseMembership;
use App\Models\MonitoredAsset;
use Illuminate\Database\Seeder;

class UniverseMembershipSeeder extends Seeder
{
    public function run(): void
    {
        $eligibleTickers = [
            'PETR4', 'PRIO3', 'VBBR3', 'VALE3', 'CSNA3', 'GGBR4', 'ITUB4', 'BBDC4', 'BBAS3', 'BPAC11',
            'ABEV3', 'TOTS3', 'SBSP3', 'CPLE3', 'SLCE3',
        ];

        $tradingTickers = [
            'PETR4', 'PRIO3', 'VALE3', 'ITUB4', 'BBDC4', 'BBAS3', 'BPAC11', 'TOTS3',
        ];

        $ibovTickers = [
            'PETR4', 'VALE3', 'ITUB4', 'BBDC4', 'BBAS3', 'ABEV3', 'BPAC11', 'SBSP3',
        ];

        $smallCapsTickers = [
            'PRIO3', 'SLCE3', 'INTB3', 'CYRE3',
        ];

        $assets = MonitoredAsset::query()->get();

        foreach ($assets as $asset) {
            $isEligible = in_array($asset->ticker, $eligibleTickers, true);
            $isTrading = in_array($asset->ticker, $tradingTickers, true);

            $this->syncMembership($asset->id, UniverseType::DATA->value, true, 'Ativo pertence ao Data Universe inicial.');
            $this->syncMembership($asset->id, UniverseType::ELIGIBLE->value, $isEligible, $isEligible
                ? 'Ativo selecionado no seed inicial do Eligible Universe.'
                : 'Ativo aguardando critérios de elegibilidade.');
            $this->syncMembership($asset->id, UniverseType::TRADING->value, $isTrading, $isTrading
                ? 'Ativo priorizado no seed inicial do Trading Universe.'
                : 'Ativo fora do core operacional inicial.');

            $asset->update([
                'collect_data' => true,
                'eligible_for_analysis' => $isEligible,
                'eligible_for_calls' => $isTrading,
                'eligible_for_execution' => $isTrading,
                'universe_type' => $isTrading ? UniverseType::TRADING->value : ($isEligible ? UniverseType::ELIGIBLE->value : UniverseType::DATA->value),
                'in_ibov' => in_array($asset->ticker, $ibovTickers, true),
                'in_index_small_caps' => in_array($asset->ticker, $smallCapsTickers, true),
                'last_universe_review_at' => now(),
            ]);
        }
    }

    private function syncMembership(int $assetId, string $type, bool $isActive, string $reason): void
    {
        MarketUniverseMembership::query()->updateOrCreate(
            [
                'monitored_asset_id' => $assetId,
                'universe_type' => $type,
            ],
            [
                'is_active' => $isActive,
                'inclusion_reason' => $isActive ? $reason : null,
                'exclusion_reason' => $isActive ? null : $reason,
                'last_changed_at' => now(),
            ],
        );
    }
}
