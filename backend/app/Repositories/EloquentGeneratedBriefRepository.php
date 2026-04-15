<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\GeneratedBriefRepositoryInterface;
use App\Models\GeneratedBrief;
use App\Models\MonitoredAsset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentGeneratedBriefRepository implements GeneratedBriefRepositoryInterface
{
    private const CHUNK_SIZE = 500;

    /**
     * @param  array<string, mixed>             $header
     * @param  array<int, array<string, mixed>> $items
     */
    public function upsertWithItems(string $briefDate, array $header, array $items): GeneratedBrief
    {
        return DB::transaction(function () use ($briefDate, $header, $items): GeneratedBrief {
            $brief = GeneratedBrief::query()->updateOrCreate(
                ['brief_date' => $briefDate],
                $header,
            );

            $brief->items()->delete();

            if ($items === []) {
                return $brief->load('items.monitoredAsset');
            }

            // Bulk-resolve ticker → asset_id in a single query, fixing the N+1 pattern.
            $symbols = array_values(array_filter(array_column($items, 'symbol')));
            $tickerToId = MonitoredAsset::query()
                ->whereIn('ticker', array_map('strtoupper', $symbols))
                ->pluck('id', 'ticker')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            $now = now()->toDateTimeString();
            $rows = [];
            $rank = 1;

            foreach ($items as $item) {
                $ticker  = strtoupper((string) ($item['symbol'] ?? ''));
                $assetId = $tickerToId[$ticker] ?? null;

                if ($assetId === null) {
                    continue;
                }

                $rows[] = [
                    'generated_brief_id' => $brief->id,
                    'monitored_asset_id' => $assetId,
                    'rank_position'      => $rank++,
                    'final_score'        => $item['final_score'] ?? 0,
                    'classification'     => $item['classification'] ?? 'N/A',
                    'setup_label'        => $item['setup_label'] ?? null,
                    'recommendation'     => $item['recommendation'] ?? 'observar',
                    'entry'              => $item['entry'] ?? null,
                    'stop'               => $item['stop'] ?? null,
                    'target'             => $item['target'] ?? null,
                    'risk_percent'       => $item['risk_percent'] ?? null,
                    'reward_percent'     => $item['reward_percent'] ?? null,
                    'rr_ratio'           => $item['rr_ratio'] ?? null,
                    'rationale'          => $item['rationale'] ?? null,
                    'alert_flags'        => isset($item['alert_flags'])
                        ? json_encode($item['alert_flags'], JSON_THROW_ON_ERROR)
                        : null,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];
            }

            foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                DB::table('generated_brief_items')->insert($chunk);
            }

            return $brief->load('items.monitoredAsset');
        });
    }

    public function listRecent(int $limit): Collection
    {
        return GeneratedBrief::query()
            ->orderByDesc('brief_date')
            ->limit($limit)
            ->get(['brief_date', 'market_bias', 'market_summary', 'conclusion', 'created_at']);
    }

    public function findByDateWithItems(string $date): GeneratedBrief
    {
        return GeneratedBrief::query()
            ->with(['items.monitoredAsset:id,ticker'])
            ->where('brief_date', $date)
            ->firstOrFail();
    }
}
