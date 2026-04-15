<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\DailyBriefGeneratorInterface;
use App\Contracts\GeneratedBriefRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateBriefRequest;
use App\Models\GeneratedBrief;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class BriefController extends Controller
{
    public function __construct(
        private readonly DailyBriefGeneratorInterface    $dailyBriefGenerator,
        private readonly GeneratedBriefRepositoryInterface $briefRepository,
    ) {
    }

    public function generate(GenerateBriefRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $date  = isset($payload['date'])
            ? CarbonImmutable::parse((string) $payload['date'])
            : CarbonImmutable::now();

        $brief = $this->dailyBriefGenerator->generate($date);
        $data  = $brief->toArray();

        $header = [
            'market_summary' => $brief->marketSummary,
            'market_bias'    => $brief->marketBias,
            'ibov_analysis'  => $brief->ibovAnalysis,
            'risk_notes'     => $brief->riskNotes,
            'conclusion'     => $brief->conclusion,
            'raw_payload'    => $data,
        ];

        $items = array_merge(
            array_values((array) ($data['ranked_ideas'] ?? [])),
            array_values((array) ($data['avoid_list'] ?? [])),
        );

        $model = $this->briefRepository->upsertWithItems($brief->briefDate->toDateString(), $header, $items);

        return response()->json($this->mapBriefResponse($model));
    }

    public function index(): JsonResponse
    {
        $items = $this->briefRepository
            ->listRecent(30)
            ->map(static fn ($brief): array => [
                'brief_date'     => $brief->brief_date?->toDateString(),
                'market_bias'    => $brief->market_bias,
                'market_summary' => $brief->market_summary,
                'conclusion'     => $brief->conclusion,
                'created_at'     => $brief->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'items' => $items,
        ]);
    }

    public function show(string $date): JsonResponse
    {
        $brief = $this->briefRepository->findByDateWithItems($date);

        return response()->json($this->mapBriefResponse($brief));
    }

    private function mapBriefResponse(GeneratedBrief $brief): array
    {
        $rankedIdeas = [];
        $avoidList   = [];

        foreach ($brief->items->sortBy('rank_position') as $item) {
            $payload = [
                'symbol'         => $item->monitoredAsset?->ticker,
                'final_score'    => (float) $item->final_score,
                'classification' => $item->classification,
                'recommendation' => $item->recommendation,
                'setup_label'    => $item->setup_label,
                'entry'          => $item->entry,
                'stop'           => $item->stop,
                'target'         => $item->target,
                'risk_percent'   => $item->risk_percent,
                'reward_percent' => $item->reward_percent,
                'rr_ratio'       => $item->rr_ratio,
                'rationale'      => $item->rationale,
                'alert_flags'    => $item->alert_flags,
            ];

            if ($item->recommendation === 'evitar') {
                $avoidList[] = $payload;
                continue;
            }

            $rankedIdeas[] = $payload;
        }

        return [
            'brief_date'     => $brief->brief_date?->toDateString(),
            'market_bias'    => $brief->market_bias,
            'market_summary' => $brief->market_summary,
            'ibov_analysis'  => $brief->ibov_analysis,
            'risk_notes'     => $brief->risk_notes,
            'conclusion'     => $brief->conclusion,
            'ranked_ideas'   => array_values($rankedIdeas),
            'avoid_list'     => array_values($avoidList),
        ];
    }
}
