<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\DailyBriefGeneratorInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateBriefRequest;
use App\Models\GeneratedBrief;
use App\Models\MonitoredAsset;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class BriefController extends Controller
{
    public function __construct(private readonly DailyBriefGeneratorInterface $dailyBriefGenerator)
    {
    }

    public function generate(GenerateBriefRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $date = isset($payload['date'])
            ? CarbonImmutable::parse((string) $payload['date'])
            : CarbonImmutable::now();

        $brief = $this->dailyBriefGenerator->generate($date);
        $model = $this->persistBrief($brief->toArray());

        return response()->json($this->mapBriefResponse($model));
    }

    public function index(): JsonResponse
    {
        $items = GeneratedBrief::query()
            ->orderByDesc('brief_date')
            ->limit(30)
            ->get(['brief_date', 'market_bias', 'market_summary', 'conclusion', 'created_at'])
            ->map(static fn (GeneratedBrief $brief): array => [
                'brief_date' => $brief->brief_date?->toDateString(),
                'market_bias' => $brief->market_bias,
                'market_summary' => $brief->market_summary,
                'conclusion' => $brief->conclusion,
                'created_at' => $brief->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'items' => $items,
        ]);
    }

    public function show(string $date): JsonResponse
    {
        $brief = GeneratedBrief::query()
            ->with(['items.monitoredAsset:id,ticker'])
            ->where('brief_date', $date)
            ->firstOrFail();

        return response()->json($this->mapBriefResponse($brief));
    }

    /**
     * @param  array<string, mixed>  $briefData
     */
    private function persistBrief(array $briefData): GeneratedBrief
    {
        $brief = GeneratedBrief::query()->updateOrCreate([
            'brief_date' => $briefData['brief_date'],
        ], [
            'market_summary' => $briefData['market_summary'],
            'market_bias' => $briefData['market_bias'],
            'ibov_analysis' => $briefData['ibov_analysis'],
            'risk_notes' => $briefData['risk_notes'],
            'conclusion' => $briefData['conclusion'],
            'raw_payload' => $briefData,
        ]);

        $brief->items()->delete();

        $rank = 1;

        foreach ((array) $briefData['ranked_ideas'] as $item) {
            $assetId = MonitoredAsset::query()->where('ticker', $item['symbol'] ?? '')->value('id');
            if ($assetId === null) {
                continue;
            }

            $brief->items()->create([
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
        }

        foreach ((array) $briefData['avoid_list'] as $item) {
            $assetId = MonitoredAsset::query()->where('ticker', $item['symbol'] ?? '')->value('id');
            if ($assetId === null) {
                continue;
            }

            $brief->items()->create([
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
        }

        $fresh = $brief->fresh(['items.monitoredAsset']);

        return $fresh ?? $brief->load('items.monitoredAsset');
    }

    private function mapBriefResponse(GeneratedBrief $brief): array
    {
        $rankedIdeas = [];
        $avoidList = [];

        foreach ($brief->items->sortBy('rank_position') as $item) {
            $payload = [
                'symbol' => $item->monitoredAsset?->ticker,
                'final_score' => (float) $item->final_score,
                'classification' => $item->classification,
                'recommendation' => $item->recommendation,
                'setup_label' => $item->setup_label,
                'entry' => $item->entry,
                'stop' => $item->stop,
                'target' => $item->target,
                'risk_percent' => $item->risk_percent,
                'reward_percent' => $item->reward_percent,
                'rr_ratio' => $item->rr_ratio,
                'rationale' => $item->rationale,
                'alert_flags' => $item->alert_flags,
            ];

            if ($item->recommendation === 'evitar') {
                $avoidList[] = $payload;
                continue;
            }

            $rankedIdeas[] = $payload;
        }

        return [
            'brief_date' => $brief->brief_date?->toDateString(),
            'market_bias' => $brief->market_bias,
            'market_summary' => $brief->market_summary,
            'ibov_analysis' => $brief->ibov_analysis,
            'risk_notes' => $brief->risk_notes,
            'conclusion' => $brief->conclusion,
            'ranked_ideas' => array_values($rankedIdeas),
            'avoid_list' => array_values($avoidList),
        ];
    }
}
