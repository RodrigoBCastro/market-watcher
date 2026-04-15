<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\AssetAnalysisScoreRepositoryInterface;
use App\Contracts\MacroSnapshotRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function __construct(
        private readonly AssetAnalysisScoreRepositoryInterface $scoreRepository,
        private readonly MacroSnapshotRepositoryInterface      $macroSnapshotRepository,
    ) {
    }

    public function top(Request $request): JsonResponse
    {
        $date = (string) ($request->query('date') ?? $this->scoreRepository->latestTradeDate() ?? '');

        if ($date === '') {
            return response()->json([
                'date'         => null,
                'market_bias'  => 'neutro',
                'items'        => [],
            ]);
        }

        $items = $this->scoreRepository
            ->findTopByDate($date, 55.0, 10)
            ->map(static fn ($score): array => [
                'symbol'         => $score->monitoredAsset?->ticker,
                'final_score'    => (float) $score->final_score,
                'classification' => $score->classification,
                'recommendation' => $score->recommendation,
                'setup_label'    => $score->setup_label,
                'entry'          => $score->suggested_entry,
                'stop'           => $score->suggested_stop,
                'target'         => $score->suggested_target,
                'rr_ratio'       => $score->rr_ratio,
            ]);

        $marketBias = $this->macroSnapshotRepository->latestMarketBiasUpToDate($date) ?? 'neutro';

        return response()->json([
            'date'        => $date,
            'market_bias' => $marketBias,
            'items'       => $items,
        ]);
    }

    public function avoid(Request $request): JsonResponse
    {
        $date = (string) ($request->query('date') ?? $this->scoreRepository->latestTradeDate() ?? '');

        if ($date === '') {
            return response()->json([
                'date'        => null,
                'market_bias' => 'neutro',
                'items'       => [],
            ]);
        }

        $items = $this->scoreRepository
            ->findAvoidByDate($date, 10)
            ->map(static fn ($score): array => [
                'symbol'         => $score->monitoredAsset?->ticker,
                'final_score'    => (float) $score->final_score,
                'classification' => $score->classification,
                'recommendation' => $score->recommendation,
                'setup_label'    => $score->setup_label,
                'entry'          => $score->suggested_entry,
                'stop'           => $score->suggested_stop,
                'target'         => $score->suggested_target,
                'rr_ratio'       => $score->rr_ratio,
            ]);

        $marketBias = $this->macroSnapshotRepository->latestMarketBiasUpToDate($date) ?? 'neutro';

        return response()->json([
            'date'        => $date,
            'market_bias' => $marketBias,
            'items'       => $items,
        ]);
    }
}
