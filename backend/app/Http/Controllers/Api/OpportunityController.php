<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetAnalysisScore;
use App\Models\MacroSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function top(Request $request): JsonResponse
    {
        $date = (string) ($request->query('date') ?? AssetAnalysisScore::query()->max('trade_date'));

        if ($date === '') {
            return response()->json([
                'date' => null,
                'market_bias' => 'neutro',
                'items' => [],
            ]);
        }

        $items = AssetAnalysisScore::query()
            ->with('monitoredAsset:id,ticker')
            ->whereDate('trade_date', $date)
            ->where('final_score', '>=', 55)
            ->orderByDesc('final_score')
            ->limit(10)
            ->get()
            ->map(static fn (AssetAnalysisScore $score): array => [
                'symbol' => $score->monitoredAsset?->ticker,
                'final_score' => (float) $score->final_score,
                'classification' => $score->classification,
                'recommendation' => $score->recommendation,
                'setup_label' => $score->setup_label,
                'entry' => $score->suggested_entry,
                'stop' => $score->suggested_stop,
                'target' => $score->suggested_target,
                'rr_ratio' => $score->rr_ratio,
            ]);

        $marketBias = MacroSnapshot::query()
            ->where('snapshot_date', '<=', $date)
            ->orderByDesc('snapshot_date')
            ->value('market_bias') ?? 'neutro';

        return response()->json([
            'date' => $date,
            'market_bias' => $marketBias,
            'items' => $items,
        ]);
    }

    public function avoid(Request $request): JsonResponse
    {
        $date = (string) ($request->query('date') ?? AssetAnalysisScore::query()->max('trade_date'));

        if ($date === '') {
            return response()->json([
                'date' => null,
                'market_bias' => 'neutro',
                'items' => [],
            ]);
        }

        $items = AssetAnalysisScore::query()
            ->with('monitoredAsset:id,ticker')
            ->whereDate('trade_date', $date)
            ->where(static function ($query): void {
                $query->where('recommendation', 'evitar')
                    ->orWhere('final_score', '<', 55);
            })
            ->orderBy('final_score')
            ->limit(10)
            ->get()
            ->map(static fn (AssetAnalysisScore $score): array => [
                'symbol' => $score->monitoredAsset?->ticker,
                'final_score' => (float) $score->final_score,
                'classification' => $score->classification,
                'recommendation' => $score->recommendation,
                'setup_label' => $score->setup_label,
                'entry' => $score->suggested_entry,
                'stop' => $score->suggested_stop,
                'target' => $score->suggested_target,
                'rr_ratio' => $score->rr_ratio,
            ]);

        $marketBias = MacroSnapshot::query()
            ->where('snapshot_date', '<=', $date)
            ->orderByDesc('snapshot_date')
            ->value('market_bias') ?? 'neutro';

        return response()->json([
            'date' => $date,
            'market_bias' => $marketBias,
            'items' => $items,
        ]);
    }
}
