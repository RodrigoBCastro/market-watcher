<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\DailyBriefGeneratorInterface;
use App\Contracts\GeneratedBriefRepositoryInterface;
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

    public function handle(
        DailyBriefGeneratorInterface $dailyBriefGenerator,
        GeneratedBriefRepositoryInterface $briefRepository,
        SyncLogger $syncLogger,
    ): void {
        $run = $syncLogger->start('generate_daily_brief');

        $processed = 0;
        $failed    = 0;

        try {
            $date  = $this->date !== null ? CarbonImmutable::parse($this->date) : CarbonImmutable::now();
            $brief = $dailyBriefGenerator->generate($date);
            $data  = $brief->toArray();

            $header = [
                'market_summary' => $brief->marketSummary,
                'market_bias'    => $brief->marketBias,
                'ibov_analysis'  => $brief->ibovAnalysis,
                'risk_notes'     => $brief->riskNotes,
                'conclusion'     => $brief->conclusion,
                'raw_payload'    => $data,
            ];

            // Merge ranked_ideas + avoid_list into a single items array.
            // The repository resolves ticker → asset_id in bulk (1 query).
            $items = array_merge(
                array_values((array) ($data['ranked_ideas'] ?? [])),
                array_values((array) ($data['avoid_list'] ?? [])),
            );

            $model     = $briefRepository->upsertWithItems($brief->briefDate->toDateString(), $header, $items);
            $processed = $model->items->count();

            $syncLogger->log($run, 'info', 'Brief diário gerado', [
                'brief_date' => $brief->briefDate->toDateString(),
                'items'      => $processed,
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
