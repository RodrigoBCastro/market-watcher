<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\TradeDecisionEngineInterface;
use App\Models\AssetAnalysisScore;
use App\Models\MonitoredAsset;
use App\Services\Analysis\MarketContextService;
use App\Services\MarketData\SyncLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RecalculateScoresJob implements ShouldQueue
{
    use Queueable;

    private const SCORE_LOOKBACK_CANDLES = 300;

    public int $tries = 2;

    public int $timeout = 240;

    public function __construct(public readonly ?string $ticker = null)
    {
    }

    public function handle(
        TradeDecisionEngineInterface $tradeDecisionEngine,
        MarketContextService $marketContextService,
        SyncLogger $syncLogger,
    ): void {
        $run = $syncLogger->start('recalculate_scores');

        $processed = 0;
        $failed = 0;

        $assets = MonitoredAsset::query()
            ->where('is_active', true)
            ->where('eligible_for_calls', true)
            ->when($this->ticker, static function ($query, string $ticker): void {
                $query->where('ticker', strtoupper($ticker));
            })
            ->select(['id', 'ticker'])
            ->orderBy('id')
            ->cursor();

        foreach ($assets as $asset) {
            try {
                $quotes = $asset->quotes()
                    ->orderByDesc('trade_date')
                    ->limit(self::SCORE_LOOKBACK_CANDLES)
                    ->get(['trade_date', 'open', 'high', 'low', 'close', 'volume'])
                    ->reverse()
                    ->values()
                    ->map(static fn ($quote): array => [
                    'trade_date' => $quote->trade_date->toDateString(),
                    'open' => (float) $quote->open,
                    'high' => (float) $quote->high,
                    'low' => (float) $quote->low,
                    'close' => (float) $quote->close,
                    'volume' => (int) $quote->volume,
                    ])->all();

                $indicators = $asset->indicators()
                    ->orderByDesc('trade_date')
                    ->limit(self::SCORE_LOOKBACK_CANDLES)
                    ->get()
                    ->reverse()
                    ->values()
                    ->map(static fn ($row): array => $row->toArray())
                    ->all();

                if ($quotes === [] || $indicators === []) {
                    $syncLogger->log($run, 'warning', "Sem dados suficientes para score de {$asset->ticker}");
                    continue;
                }

                $latestIndicator = $indicators[count($indicators) - 1];
                $history = array_slice($indicators, -5);
                $latestTradeDate = $quotes[count($quotes) - 1]['trade_date'];

                $marketContext = $marketContextService->resolve(new \DateTimeImmutable($latestTradeDate));

                $decision = $tradeDecisionEngine->evaluate($asset->ticker, $quotes, [
                    'current' => $latestIndicator,
                    'history' => $history,
                ], $marketContext);

                $breakdown = $decision->scoreBreakdown;

                AssetAnalysisScore::query()->updateOrCreate([
                    'monitored_asset_id' => $asset->id,
                    'trade_date' => $decision->tradeDate->toDateString(),
                ], [
                    'trend_score' => (float) ($breakdown['trend_score'] ?? 0.0),
                    'moving_average_score' => (float) ($breakdown['moving_average_score'] ?? 0.0),
                    'structure_score' => (float) ($breakdown['structure_score'] ?? 0.0),
                    'momentum_score' => (float) ($breakdown['momentum_score'] ?? 0.0),
                    'volume_score' => (float) ($breakdown['volume_score'] ?? 0.0),
                    'risk_score' => (float) ($breakdown['risk_score'] ?? 0.0),
                    'market_context_score' => (float) ($breakdown['market_context_score'] ?? 0.0),
                    'final_score' => (float) ($breakdown['final_score'] ?? 0.0),
                    'classification' => (string) ($decision->classification),
                    'setup_code' => $decision->setupCode,
                    'setup_label' => $decision->setupLabel,
                    'recommendation' => $decision->recommendation,
                    'suggested_entry' => $decision->entry,
                    'suggested_stop' => $decision->stop,
                    'suggested_target' => $decision->target,
                    'risk_percent' => $decision->riskPercent,
                    'reward_percent' => $decision->rewardPercent,
                    'rr_ratio' => $decision->rrRatio,
                    'alert_flags' => $decision->alerts,
                    'rationale' => $decision->rationale,
                    'raw_payload' => [
                        'score_breakdown' => $decision->scoreBreakdown,
                    ],
                ]);

                $processed++;

                $syncLogger->log($run, 'info', "Score atualizado para {$asset->ticker}", [
                    'final_score' => $breakdown['final_score'] ?? null,
                    'recommendation' => $decision->recommendation,
                ]);
            } catch (Throwable $exception) {
                $failed++;
                $syncLogger->log($run, 'error', "Falha ao recalcular score para {$asset->ticker}", [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $status = $failed > 0 ? ($processed > 0 ? 'partial' : 'failed') : 'success';

        $syncLogger->finish($run, $status, $processed, $failed);
    }
}
