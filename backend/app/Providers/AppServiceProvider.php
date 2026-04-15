<?php

namespace App\Providers;

use App\Contracts\BacktestEngineInterface;
use App\Contracts\AssetMasterRegistryServiceInterface;
use App\Contracts\AssetUniverseBootstrapServiceInterface;
use App\Contracts\BrokerIntegrationInterface;
use App\Contracts\ConfidenceScoreServiceInterface;
use App\Contracts\CorrelationAnalysisServiceInterface;
use App\Contracts\DailyBriefGeneratorInterface;
use App\Contracts\IndicatorCalculatorInterface;
use App\Contracts\MarketUniverseServiceInterface;
use App\Contracts\MarketDataProviderInterface;
use App\Contracts\MarketRegimeServiceInterface;
use App\Contracts\QuoteImporterInterface;
use App\Contracts\ProbabilisticEngineInterface;
use App\Contracts\PerformanceAnalyticsServiceInterface;
use App\Contracts\PortfolioRiskServiceInterface;
use App\Contracts\PortfolioServiceInterface;
use App\Contracts\PortfolioSimulationServiceInterface;
use App\Contracts\PositionSizingServiceInterface;
use App\Contracts\RiskSettingsServiceInterface;
use App\Contracts\ScoreEngineInterface;
use App\Contracts\ScoreOptimizerInterface;
use App\Contracts\SetupDetectorInterface;
use App\Contracts\TradingAlertServiceInterface;
use App\Contracts\TradeCallServiceInterface;
use App\Contracts\TradeDecisionEngineInterface;
use App\Services\Backtest\BacktestEngine;
use App\Services\Analysis\SetupDetectionService;
use App\Services\Analysis\TradeDecisionEngine;
use App\Services\Briefing\DailyBriefGenerator;
use App\Services\Calls\TradeCallService;
use App\Services\Execution\NullBrokerIntegration;
use App\Services\Indicators\IndicatorPipeline;
use App\Services\MarketData\AssetMasterRegistryService;
use App\Services\MarketData\BrapiProvider;
use App\Services\MarketData\QuoteImporter;
use App\Services\Metrics\SetupMetricsService;
use App\Services\Optimization\ScoreOptimizerService;
use App\Services\Scoring\CompositeScoreEngine;
use App\Services\Trading\ConfidenceScoreService;
use App\Services\Trading\CorrelationAnalysisService;
use App\Services\Trading\MarketRegimeService;
use App\Services\Trading\MarketUniverseService;
use App\Services\Trading\PerformanceAnalyticsService;
use App\Services\Trading\PortfolioRiskService;
use App\Services\Trading\PortfolioService;
use App\Services\Trading\PortfolioSimulationService;
use App\Services\Trading\PositionSizingService;
use App\Services\Trading\RiskSettingsService;
use App\Services\Trading\TradingAlertService;
use App\Services\Trading\AssetUniverseBootstrapService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MarketDataProviderInterface::class, BrapiProvider::class);
        $this->app->bind(QuoteImporterInterface::class, QuoteImporter::class);
        $this->app->bind(AssetMasterRegistryServiceInterface::class, AssetMasterRegistryService::class);
        $this->app->bind(IndicatorCalculatorInterface::class, IndicatorPipeline::class);
        $this->app->bind(MarketUniverseServiceInterface::class, MarketUniverseService::class);
        $this->app->bind(AssetUniverseBootstrapServiceInterface::class, AssetUniverseBootstrapService::class);
        $this->app->bind(SetupDetectorInterface::class, SetupDetectionService::class);
        $this->app->bind(ScoreEngineInterface::class, CompositeScoreEngine::class);
        $this->app->bind(TradeDecisionEngineInterface::class, TradeDecisionEngine::class);
        $this->app->bind(DailyBriefGeneratorInterface::class, DailyBriefGenerator::class);
        $this->app->bind(TradeCallServiceInterface::class, TradeCallService::class);
        $this->app->bind(ProbabilisticEngineInterface::class, SetupMetricsService::class);
        $this->app->bind(BacktestEngineInterface::class, BacktestEngine::class);
        $this->app->bind(ScoreOptimizerInterface::class, ScoreOptimizerService::class);
        $this->app->bind(BrokerIntegrationInterface::class, NullBrokerIntegration::class);
        $this->app->bind(RiskSettingsServiceInterface::class, RiskSettingsService::class);
        $this->app->bind(PositionSizingServiceInterface::class, PositionSizingService::class);
        $this->app->bind(MarketRegimeServiceInterface::class, MarketRegimeService::class);
        $this->app->bind(ConfidenceScoreServiceInterface::class, ConfidenceScoreService::class);
        $this->app->bind(CorrelationAnalysisServiceInterface::class, CorrelationAnalysisService::class);
        $this->app->bind(PortfolioRiskServiceInterface::class, PortfolioRiskService::class);
        $this->app->bind(PortfolioServiceInterface::class, PortfolioService::class);
        $this->app->bind(PortfolioSimulationServiceInterface::class, PortfolioSimulationService::class);
        $this->app->bind(PerformanceAnalyticsServiceInterface::class, PerformanceAnalyticsService::class);
        $this->app->bind(TradingAlertServiceInterface::class, TradingAlertService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
