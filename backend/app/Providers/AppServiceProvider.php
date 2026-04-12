<?php

namespace App\Providers;

use App\Contracts\DailyBriefGeneratorInterface;
use App\Contracts\IndicatorCalculatorInterface;
use App\Contracts\MarketDataProviderInterface;
use App\Contracts\ScoreEngineInterface;
use App\Contracts\SetupDetectorInterface;
use App\Contracts\TradeDecisionEngineInterface;
use App\Services\Analysis\SetupDetectionService;
use App\Services\Analysis\TradeDecisionEngine;
use App\Services\Briefing\DailyBriefGenerator;
use App\Services\Indicators\IndicatorPipeline;
use App\Services\MarketData\BrapiProvider;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
