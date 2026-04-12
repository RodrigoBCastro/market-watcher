<?php

namespace App\Providers;

use App\Contracts\BacktestEngineInterface;
use App\Contracts\BrokerIntegrationInterface;
use App\Contracts\DailyBriefGeneratorInterface;
use App\Contracts\IndicatorCalculatorInterface;
use App\Contracts\MarketDataProviderInterface;
use App\Contracts\ProbabilisticEngineInterface;
use App\Contracts\ScoreEngineInterface;
use App\Contracts\ScoreOptimizerInterface;
use App\Contracts\SetupDetectorInterface;
use App\Contracts\TradeCallServiceInterface;
use App\Contracts\TradeDecisionEngineInterface;
use App\Services\Backtest\BacktestEngine;
use App\Services\Analysis\SetupDetectionService;
use App\Services\Analysis\TradeDecisionEngine;
use App\Services\Briefing\DailyBriefGenerator;
use App\Services\Calls\TradeCallService;
use App\Services\Execution\NullBrokerIntegration;
use App\Services\Indicators\IndicatorPipeline;
use App\Services\MarketData\BrapiProvider;
use App\Services\Metrics\SetupMetricsService;
use App\Services\Optimization\ScoreOptimizerService;
use App\Services\Scoring\CompositeScoreEngine;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MarketDataProviderInterface::class, BrapiProvider::class);
        $this->app->bind(IndicatorCalculatorInterface::class, IndicatorPipeline::class);
        $this->app->bind(SetupDetectorInterface::class, SetupDetectionService::class);
        $this->app->bind(ScoreEngineInterface::class, CompositeScoreEngine::class);
        $this->app->bind(TradeDecisionEngineInterface::class, TradeDecisionEngine::class);
        $this->app->bind(DailyBriefGeneratorInterface::class, DailyBriefGenerator::class);
        $this->app->bind(TradeCallServiceInterface::class, TradeCallService::class);
        $this->app->bind(ProbabilisticEngineInterface::class, SetupMetricsService::class);
        $this->app->bind(BacktestEngineInterface::class, BacktestEngine::class);
        $this->app->bind(ScoreOptimizerInterface::class, ScoreOptimizerService::class);
        $this->app->bind(BrokerIntegrationInterface::class, NullBrokerIntegration::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
