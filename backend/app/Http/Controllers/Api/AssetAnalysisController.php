<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\TradeDecisionEngineInterface;
use App\Http\Controllers\Controller;
use App\Models\AssetAnalysisScore;
use App\Models\MonitoredAsset;
use App\Services\Analysis\MarketContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetAnalysisController extends Controller
{
    public function __construct(
        private readonly TradeDecisionEngineInterface $tradeDecisionEngine,
        private readonly MarketContextService $marketContextService,
    ) {
    }

    public function quotes(Request $request, string $ticker): JsonResponse
    {
        $limit = (int) $request->integer('limit', 120);

        $asset = MonitoredAsset::query()->where('ticker', strtoupper($ticker))->firstOrFail();

        $rows = $asset->quotes()
            ->orderByDesc('trade_date')
            ->limit(max(1, min($limit, 500)))
            ->get()
            ->map(static fn ($quote): array => [
                'trade_date' => $quote->trade_date?->toDateString(),
                'open' => (float) $quote->open,
                'high' => (float) $quote->high,
                'low' => (float) $quote->low,
                'close' => (float) $quote->close,
                'adjusted_close' => $quote->adjusted_close,
                'volume' => (int) $quote->volume,
                'source' => $quote->source,
            ])
            ->values();

        return response()->json([
            'symbol' => $asset->ticker,
            'items' => $rows,
        ]);
    }

    public function indicators(Request $request, string $ticker): JsonResponse
    {
        $limit = (int) $request->integer('limit', 120);

        $asset = MonitoredAsset::query()->where('ticker', strtoupper($ticker))->firstOrFail();

        $rows = $asset->indicators()
            ->orderByDesc('trade_date')
            ->limit(max(1, min($limit, 500)))
            ->get()
            ->map(static fn ($row): array => $row->only([
                'trade_date',
                'sma_5', 'sma_9', 'sma_10', 'sma_20', 'sma_21', 'sma_30', 'sma_40', 'sma_50', 'sma_72', 'sma_80', 'sma_100', 'sma_120', 'sma_150', 'sma_200',
                'ema_5', 'ema_8', 'ema_9', 'ema_12', 'ema_17', 'ema_20', 'ema_21', 'ema_26', 'ema_34', 'ema_50', 'ema_72', 'ema_100', 'ema_144', 'ema_200',
                'rsi_7', 'rsi_14',
                'macd_line', 'macd_signal', 'macd_histogram',
                'atr_14',
                'bollinger_mid', 'bollinger_upper', 'bollinger_lower',
                'adx_14',
                'stochastic_k', 'stochastic_d',
                'roc',
                'avg_volume_20',
                'change_5', 'change_10', 'change_20',
                'high_20', 'low_20', 'high_50', 'low_50', 'high_200', 'low_200',
                'distance_ema_21', 'distance_sma_50', 'distance_sma_200',
                'recent_volatility', 'avg_range',
            ]))
            ->values();

        return response()->json([
            'symbol' => $asset->ticker,
            'items' => $rows,
        ]);
    }

    public function analysis(string $ticker): JsonResponse
    {
        $asset = MonitoredAsset::query()->where('ticker', strtoupper($ticker))->firstOrFail();

        $latestQuote = $asset->quotes()->orderByDesc('trade_date')->first();
        $latestScore = $asset->analysisScores()->orderByDesc('trade_date')->first();

        if ($latestQuote === null) {
            return response()->json([
                'message' => 'Não há dados de cotação para o ativo.',
            ], 404);
        }

        if ($latestScore !== null) {
            return response()->json($this->mapStoredAnalysis($asset->ticker, $latestQuote->close, $latestScore));
        }

        $quotes = $asset->quotes()->orderBy('trade_date')->get()->map(static fn ($quote): array => [
            'trade_date' => $quote->trade_date?->toDateString(),
            'open' => (float) $quote->open,
            'high' => (float) $quote->high,
            'low' => (float) $quote->low,
            'close' => (float) $quote->close,
            'volume' => (int) $quote->volume,
        ])->all();

        $indicators = $asset->indicators()->orderBy('trade_date')->get()->map(static fn ($row): array => $row->toArray())->all();

        if ($indicators === []) {
            return response()->json([
                'message' => 'Indicadores ainda não calculados para o ativo.',
            ], 404);
        }

        $marketContext = $this->marketContextService->resolve($latestQuote->trade_date);
        $decision = $this->tradeDecisionEngine->evaluate($asset->ticker, $quotes, [
            'current' => $indicators[count($indicators) - 1],
            'history' => array_slice($indicators, -5),
        ], $marketContext);

        return response()->json([
            ...$decision->toArray(),
            'prices' => [
                'current' => (float) $latestQuote->close,
                'entry' => $decision->entry,
                'stop' => $decision->stop,
                'target' => $decision->target,
            ],
        ]);
    }

    private function mapStoredAnalysis(string $symbol, float $currentPrice, AssetAnalysisScore $score): array
    {
        return [
            'symbol' => $symbol,
            'trade_date' => $score->trade_date?->toDateString(),
            'classification' => $score->classification,
            'recommendation' => $score->recommendation,
            'setup' => [
                'code' => $score->setup_code,
                'label' => $score->setup_label,
            ],
            'prices' => [
                'current' => $currentPrice,
                'entry' => $score->suggested_entry,
                'stop' => $score->suggested_stop,
                'target' => $score->suggested_target,
            ],
            'risk_metrics' => [
                'risk_percent' => $score->risk_percent,
                'reward_percent' => $score->reward_percent,
                'rr_ratio' => $score->rr_ratio,
            ],
            'score_breakdown' => [
                'trend_score' => $score->trend_score,
                'moving_average_score' => $score->moving_average_score,
                'structure_score' => $score->structure_score,
                'momentum_score' => $score->momentum_score,
                'volume_score' => $score->volume_score,
                'risk_score' => $score->risk_score,
                'market_context_score' => $score->market_context_score,
                'final_score' => $score->final_score,
                'classification' => $score->classification,
            ],
            'alerts' => $score->alert_flags ?? [],
            'rationale' => $score->rationale,
        ];
    }
}
