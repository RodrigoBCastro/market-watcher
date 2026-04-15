<?php

namespace App\Providers;

use App\Contracts\AssetAnalysisScoreRepositoryInterface;
use App\Contracts\AssetHistorySyncStateRepositoryInterface;
use App\Contracts\AssetMasterRepositoryInterface;
use App\Contracts\AssetMasterRegistryServiceInterface;
use App\Contracts\AssetQuoteRepositoryInterface;
use App\Contracts\AssetUniverseBootstrapServiceInterface;
use App\Contracts\BacktestEngineInterface;
use App\Contracts\BacktestResultRepositoryInterface;
use App\Contracts\BrokerIntegrationInterface;
use App\Contracts\ConfidenceScoreServiceInterface;
use App\Contracts\CorrelationAnalysisServiceInterface;
use App\Contracts\DailyBriefGeneratorInterface;
use App\Contracts\EquityCurvePointRepositoryInterface;
use App\Contracts\GeneratedBriefRepositoryInterface;
use App\Contracts\IndicatorCalculatorInterface;
use App\Contracts\MacroSnapshotRepositoryInterface;
use App\Contracts\MarketDataProviderInterface;
use App\Contracts\MarketIndexMasterRepositoryInterface;
use App\Contracts\MarketIndexRepositoryInterface;
use App\Contracts\MarketRegimeServiceInterface;
use App\Contracts\MarketUniverseMembershipRepositoryInterface;
use App\Contracts\MarketUniverseServiceInterface;
use App\Contracts\MonitoredAssetRepositoryInterface;
use App\Contracts\PerformanceAnalyticsServiceInterface;
use App\Contracts\PortfolioClosedPositionRepositoryInterface;
use App\Contracts\PortfolioPositionRepositoryInterface;
use App\Contracts\PortfolioRiskServiceInterface;
use App\Contracts\PortfolioServiceInterface;
use App\Contracts\PortfolioSimulationServiceInterface;
use App\Contracts\PositionSizingServiceInterface;
use App\Contracts\ProbabilisticEngineInterface;
use App\Contracts\QuoteImporterInterface;
use App\Contracts\RiskSettingRepositoryInterface;
use App\Contracts\RiskSettingsServiceInterface;
use App\Contracts\ScoreEngineInterface;
use App\Contracts\ScoreOptimizerInterface;
use App\Contracts\SetupDetectorInterface;
use App\Contracts\SetupMetricRepositoryInterface;
use App\Contracts\TechnicalIndicatorRepositoryInterface;
use App\Contracts\TradeCallRepositoryInterface;
use App\Contracts\TradeCallServiceInterface;
use App\Contracts\TradeDecisionEngineInterface;
use App\Contracts\TradeOutcomeRepositoryInterface;
use App\Contracts\TradingAlertRepositoryInterface;
use App\Contracts\TradingAlertServiceInterface;
use App\Repositories\EloquentAssetAnalysisScoreRepository;
use App\Repositories\EloquentAssetHistorySyncStateRepository;
use App\Repositories\EloquentAssetMasterRepository;
use App\Repositories\EloquentAssetQuoteRepository;
use App\Repositories\EloquentBacktestResultRepository;
use App\Repositories\EloquentEquityCurvePointRepository;
use App\Repositories\EloquentGeneratedBriefRepository;
use App\Repositories\EloquentMacroSnapshotRepository;
use App\Repositories\EloquentMarketIndexMasterRepository;
use App\Repositories\EloquentMarketIndexRepository;
use App\Repositories\EloquentMarketUniverseMembershipRepository;
use App\Repositories\EloquentMonitoredAssetRepository;
use App\Repositories\EloquentPortfolioClosedPositionRepository;
use App\Repositories\EloquentPortfolioPositionRepository;
use App\Repositories\EloquentRiskSettingRepository;
use App\Repositories\EloquentSetupMetricRepository;
use App\Repositories\EloquentTechnicalIndicatorRepository;
use App\Repositories\EloquentTradeCallRepository;
use App\Repositories\EloquentTradeOutcomeRepository;
use App\Repositories\EloquentTradingAlertRepository;
use App\Services\Analysis\SetupDetectionService;
use App\Services\Analysis\TradeDecisionEngine;
use App\Services\Backtest\BacktestEngine;
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
use App\Services\Trading\AssetUniverseBootstrapService;
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
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ── Repositories ─────────────────────────────────────────────────────
        $this->app->bind(AssetAnalysisScoreRepositoryInterface::class, EloquentAssetAnalysisScoreRepository::class);
        $this->app->bind(AssetHistorySyncStateRepositoryInterface::class, EloquentAssetHistorySyncStateRepository::class);
        $this->app->bind(AssetMasterRepositoryInterface::class, EloquentAssetMasterRepository::class);
        $this->app->bind(AssetQuoteRepositoryInterface::class, EloquentAssetQuoteRepository::class);
        $this->app->bind(BacktestResultRepositoryInterface::class, EloquentBacktestResultRepository::class);
        $this->app->bind(EquityCurvePointRepositoryInterface::class, EloquentEquityCurvePointRepository::class);
        $this->app->bind(GeneratedBriefRepositoryInterface::class, EloquentGeneratedBriefRepository::class);
        $this->app->bind(MacroSnapshotRepositoryInterface::class, EloquentMacroSnapshotRepository::class);
        $this->app->bind(MarketIndexMasterRepositoryInterface::class, EloquentMarketIndexMasterRepository::class);
        $this->app->bind(MarketIndexRepositoryInterface::class, EloquentMarketIndexRepository::class);
        $this->app->bind(MarketUniverseMembershipRepositoryInterface::class, EloquentMarketUniverseMembershipRepository::class);
        $this->app->bind(MonitoredAssetRepositoryInterface::class, EloquentMonitoredAssetRepository::class);
        $this->app->bind(PortfolioClosedPositionRepositoryInterface::class, EloquentPortfolioClosedPositionRepository::class);
        $this->app->bind(PortfolioPositionRepositoryInterface::class, EloquentPortfolioPositionRepository::class);
        $this->app->bind(RiskSettingRepositoryInterface::class, EloquentRiskSettingRepository::class);
        $this->app->bind(SetupMetricRepositoryInterface::class, EloquentSetupMetricRepository::class);
        $this->app->bind(TechnicalIndicatorRepositoryInterface::class, EloquentTechnicalIndicatorRepository::class);
        $this->app->bind(TradeCallRepositoryInterface::class, EloquentTradeCallRepository::class);
        $this->app->bind(TradeOutcomeRepositoryInterface::class, EloquentTradeOutcomeRepository::class);
        $this->app->bind(TradingAlertRepositoryInterface::class, EloquentTradingAlertRepository::class);

        // ── Services ─────────────────────────────────────────────────────────
        $this->app->bind(AssetMasterRegistryServiceInterface::class, AssetMasterRegistryService::class);
        $this->app->bind(AssetUniverseBootstrapServiceInterface::class, AssetUniverseBootstrapService::class);
        $this->app->bind(BacktestEngineInterface::class, BacktestEngine::class);
        $this->app->bind(BrokerIntegrationInterface::class, NullBrokerIntegration::class);
        $this->app->bind(ConfidenceScoreServiceInterface::class, ConfidenceScoreService::class);
        $this->app->bind(CorrelationAnalysisServiceInterface::class, CorrelationAnalysisService::class);
        $this->app->bind(DailyBriefGeneratorInterface::class, DailyBriefGenerator::class);
        $this->app->bind(IndicatorCalculatorInterface::class, IndicatorPipeline::class);
        $this->app->bind(MarketDataProviderInterface::class, BrapiProvider::class);
        $this->app->bind(QuoteImporterInterface::class, QuoteImporter::class);
        $this->app->bind(MarketRegimeServiceInterface::class, MarketRegimeService::class);
        $this->app->bind(MarketUniverseServiceInterface::class, MarketUniverseService::class);
        $this->app->bind(PerformanceAnalyticsServiceInterface::class, PerformanceAnalyticsService::class);
        $this->app->bind(PortfolioRiskServiceInterface::class, PortfolioRiskService::class);
        $this->app->bind(PortfolioServiceInterface::class, PortfolioService::class);
        $this->app->bind(PortfolioSimulationServiceInterface::class, PortfolioSimulationService::class);
        $this->app->bind(PositionSizingServiceInterface::class, PositionSizingService::class);
        $this->app->bind(ProbabilisticEngineInterface::class, SetupMetricsService::class);
        $this->app->bind(RiskSettingsServiceInterface::class, RiskSettingsService::class);
        $this->app->bind(ScoreEngineInterface::class, CompositeScoreEngine::class);
        $this->app->bind(ScoreOptimizerInterface::class, ScoreOptimizerService::class);
        $this->app->bind(SetupDetectorInterface::class, SetupDetectionService::class);
        $this->app->bind(TradeCallServiceInterface::class, TradeCallService::class);
        $this->app->bind(TradeDecisionEngineInterface::class, TradeDecisionEngine::class);
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
