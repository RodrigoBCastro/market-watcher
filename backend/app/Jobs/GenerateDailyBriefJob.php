<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\DailyBriefGeneratorInterface;
use App\Models\GeneratedBrief;
use App\Models\MonitoredAsset;
use App\Services\MarketData\SyncLogger;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateDailyBriefJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public readonly ?string $date = null)
    {
    }

    public function handle(DailyBriefGeneratorInterface $dailyBriefGenerator, SyncLogger $syncLogger): void
    {
        $run = $syncLogger->start('generate_daily_brief');

        $processed = 0;
        $failed = 0;

        try {
            $date = $this->date !== null ? CarbonImmutable::parse($this->date) : CarbonImmutable::now();
            $brief = $dailyBriefGenerator->generate($date);

            $briefModel = GeneratedBrief::query()->updateOrCreate([
                'brief_date' => $brief->briefDate->toDateString(),
            ], [
                'market_summary' => $brief->marketSummary,
                'market_bias' => $brief->marketBias,
                'ibov_analysis' => $brief->ibovAnalysis,
                'risk_notes' => $brief->riskNotes,
                'conclusion' => $brief->conclusion,
                'raw_payload' => $brief->toArray(),
            ]);

            $briefModel->items()->delete();

            $rank = 1;

            foreach ($brief->rankedIdeas as $item) {
                $assetId = MonitoredAsset::query()->where('ticker', $item['symbol'] ?? '')->value('id');

                if ($assetId === null) {
                    continue;
                }

                $briefModel->items()->create([
                    'monitored_asset_id' => $assetId,
                    'rank_position' => $rank++,
                    'final_score' => $item['final_score'] ?? 0,
                    'classification' => $item['classification'] ?? 'N/A',
                    'setup_label' => $item['setup_label'] ?? null,
                    'recommendation' => $item['recommendation'] ?? 'observar',
                    'entry' => $item['entry'] ?? null,
                    'stop' => $item['stop'] ?? null,
                    'target' => $item['target'] ?? null,
                    'risk_percent' => $item['risk_percent'] ?? null,
                    'reward_percent' => $item['reward_percent'] ?? null,
                    'rr_ratio' => $item['rr_ratio'] ?? null,
                    'rationale' => $item['rationale'] ?? null,
                    'alert_flags' => $item['alert_flags'] ?? null,
                ]);

                $processed++;
            }

            foreach ($brief->avoidList as $item) {
                $assetId = MonitoredAsset::query()->where('ticker', $item['symbol'] ?? '')->value('id');

                if ($assetId === null) {
                    continue;
                }

                $briefModel->items()->create([
                    'monitored_asset_id' => $assetId,
                    'rank_position' => $rank++,
                    'final_score' => $item['final_score'] ?? 0,
                    'classification' => $item['classification'] ?? 'Evitar',
                    'setup_label' => $item['setup_label'] ?? null,
                    'recommendation' => $item['recommendation'] ?? 'evitar',
                    'entry' => null,
                    'stop' => null,
                    'target' => null,
                    'risk_percent' => null,
                    'reward_percent' => null,
                    'rr_ratio' => null,
                    'rationale' => $item['rationale'] ?? null,
                    'alert_flags' => $item['alert_flags'] ?? null,
                ]);

                $processed++;
            }

            $syncLogger->log($run, 'info', 'Brief diário gerado', [
                'brief_date' => $brief->briefDate->toDateString(),
                'items' => $processed,
            ]);
        } catch (Throwable $exception) {
            $failed++;
            $syncLogger->log($run, 'error', 'Falha ao gerar brief diário', [
                'error' => $exception->getMessage(),
            ]);
        }

        $status = $failed > 0 ? ($processed > 0 ? 'partial' : 'failed') : 'success';

        $syncLogger->finish($run, $status, $processed, $failed);
    }
}
