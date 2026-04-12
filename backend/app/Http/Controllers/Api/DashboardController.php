<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetAnalysisScore;
use App\Models\GeneratedBrief;
use App\Models\MacroSnapshot;
use App\Models\MonitoredAsset;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $macro = MacroSnapshot::query()->orderByDesc('snapshot_date')->first();

        $latestDate = AssetAnalysisScore::query()->max('trade_date');

        $scores = collect();

        if ($latestDate !== null) {
            $scores = AssetAnalysisScore::query()
                ->with('monitoredAsset:id,ticker,name')
                ->whereDate('trade_date', $latestDate)
                ->orderByDesc('final_score')
                ->get();
        }

        $watchlist = $scores->map(static fn (AssetAnalysisScore $score): array => [
            'symbol' => $score->monitoredAsset?->ticker,
            'name' => $score->monitoredAsset?->name,
            'final_score' => (float) $score->final_score,
            'classification' => $score->classification,
            'recommendation' => $score->recommendation,
            'setup_label' => $score->setup_label,
            'entry' => $score->suggested_entry,
            'stop' => $score->suggested_stop,
            'target' => $score->suggested_target,
            'rr_ratio' => $score->rr_ratio,
        ]);

        $classificationCounts = [
            'excelente' => $scores->where('classification', 'Excelente entrada')->count(),
            'boa' => $scores->where('classification', 'Boa entrada')->count(),
            'neutra' => $scores->where('classification', 'Neutra / seletiva')->count(),
            'fraca' => $scores->where('classification', 'Fraca')->count(),
            'evitar' => $scores->where('classification', 'Evitar')->count(),
        ];

        $setups = $scores->groupBy('setup_code')->map(static fn ($rows, $setupCode): array => [
            'setup_code' => $setupCode,
            'count' => count($rows),
            'avg_score' => round((float) collect($rows)->avg('final_score'), 2),
        ])->values();

        $latestBrief = GeneratedBrief::query()->orderByDesc('brief_date')->first();

        return response()->json([
            'market_cards' => [
                'ibov_close' => $macro?->ibov_close,
                'usd_brl' => $macro?->usd_brl,
                'market_bias' => $macro?->market_bias,
                'snapshot_date' => $macro?->snapshot_date?->toDateString(),
                'monitored_assets' => MonitoredAsset::query()->where('monitoring_enabled', true)->count(),
            ],
            'watchlist' => $watchlist,
            'classifications' => $classificationCounts,
            'setups' => $setups,
            'brief' => $latestBrief !== null ? [
                'brief_date' => $latestBrief->brief_date?->toDateString(),
                'market_bias' => $latestBrief->market_bias,
                'market_summary' => $latestBrief->market_summary,
                'conclusion' => $latestBrief->conclusion,
            ] : null,
        ]);
    }
}
