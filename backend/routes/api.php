<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AssetAnalysisController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AssetMasterController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BacktestController;
use App\Http\Controllers\Api\BriefController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OptimizerController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\PerformanceController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\PortfolioRiskController;
use App\Http\Controllers\Api\PositionSizingController;
use App\Http\Controllers\Api\QuantController;
use App\Http\Controllers\Api\RiskSettingsController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\TradeCallController;
use App\Http\Controllers\Api\UniverseController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('api.token')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('api.token')->group(function (): void {
    Route::get('/assets/{ticker}/quotes', [AssetAnalysisController::class, 'quotes'])
        ->where('ticker', '[A-Za-z0-9\^=\.\-]+');
    Route::get('/assets/{ticker}/indicators', [AssetAnalysisController::class, 'indicators'])
        ->where('ticker', '[A-Za-z0-9\^=\.\-]+');
    Route::get('/assets/{ticker}/analysis', [AssetAnalysisController::class, 'analysis'])
        ->where('ticker', '[A-Za-z0-9\^=\.\-]+');
    Route::get('/assets/{ticker}/universe-status', [UniverseController::class, 'assetStatus'])
        ->where('ticker', '[A-Za-z0-9\^=\.\-]+');

    Route::get('/asset-master', [AssetMasterController::class, 'index']);
    Route::get('/asset-master/indexes', [AssetMasterController::class, 'indexes']);
    Route::get('/asset-master/{symbol}', [AssetMasterController::class, 'show'])
        ->where('symbol', '[A-Za-z0-9\^=\.\-]+');
    Route::post('/asset-master/sync', [AssetMasterController::class, 'sync']);
    Route::post('/asset-master/bootstrap-data-universe', [AssetMasterController::class, 'bootstrapDataUniverse']);

    Route::get('/assets', [AssetController::class, 'index']);
    Route::post('/assets', [AssetController::class, 'store']);
    Route::patch('/assets/{id}/universe-membership', [UniverseController::class, 'updateAssetMembership'])->whereNumber('id');
    Route::patch('/assets/{id}', [AssetController::class, 'update'])->whereNumber('id');
    Route::delete('/assets/{id}', [AssetController::class, 'destroy'])->whereNumber('id');

    Route::get('/universes/summary', [UniverseController::class, 'summary']);
    Route::get('/universes/data', [UniverseController::class, 'data']);
    Route::get('/universes/eligible', [UniverseController::class, 'eligible']);
    Route::get('/universes/trading', [UniverseController::class, 'trading']);
    Route::post('/universes/recalculate-eligible', [UniverseController::class, 'recalculateEligible']);
    Route::post('/universes/recalculate-trading', [UniverseController::class, 'recalculateTrading']);

    Route::post('/sync/assets/{ticker}', [SyncController::class, 'syncAsset'])
        ->where('ticker', '[A-Za-z0-9\^=\.\-]+');
    Route::post('/sync/assets', [SyncController::class, 'syncAssets']);
    Route::post('/sync/market', [SyncController::class, 'syncMarket']);
    Route::post('/sync/full', [SyncController::class, 'syncFull']);

    Route::get('/opportunities/top', [OpportunityController::class, 'top']);
    Route::get('/opportunities/avoid', [OpportunityController::class, 'avoid']);

    Route::post('/briefs/generate', [BriefController::class, 'generate']);
    Route::get('/briefs', [BriefController::class, 'index']);
    Route::get('/briefs/{date}', [BriefController::class, 'show'])
        ->where('date', '\\d{4}-\\d{2}-\\d{2}');

    Route::get('/dashboard', DashboardController::class);

    Route::get('/calls', [TradeCallController::class, 'index']);
    Route::get('/calls/queue', [TradeCallController::class, 'queue']);
    Route::get('/calls/{id}', [TradeCallController::class, 'show'])->whereNumber('id');
    Route::get('/calls/outcomes', [TradeCallController::class, 'outcomes']);
    Route::post('/calls/generate', [TradeCallController::class, 'generate']);
    Route::post('/calls/evaluate-open', [TradeCallController::class, 'evaluateOpen']);
    Route::post('/calls/{id}/approve', [TradeCallController::class, 'approve'])->whereNumber('id');
    Route::post('/calls/{id}/reject', [TradeCallController::class, 'reject'])->whereNumber('id');
    Route::post('/calls/{id}/publish', [TradeCallController::class, 'publish'])->whereNumber('id');

    Route::get('/risk-settings', [RiskSettingsController::class, 'show']);
    Route::put('/risk-settings', [RiskSettingsController::class, 'update']);

    Route::post('/position-sizing/calculate', [PositionSizingController::class, 'calculate']);

    Route::get('/portfolio', [PortfolioController::class, 'index']);
    Route::get('/portfolio/open', [PortfolioController::class, 'open']);
    Route::get('/portfolio/closed', [PortfolioController::class, 'closed']);
    Route::post('/portfolio/positions', [PortfolioController::class, 'store']);
    Route::patch('/portfolio/positions/{id}', [PortfolioController::class, 'update'])->whereNumber('id');
    Route::post('/portfolio/positions/{id}/close', [PortfolioController::class, 'close'])->whereNumber('id');
    Route::post('/portfolio/positions/{id}/partial-close', [PortfolioController::class, 'partialClose'])->whereNumber('id');

    Route::post('/portfolio/simulate', [PortfolioController::class, 'simulate']);

    Route::get('/portfolio/risk', [PortfolioRiskController::class, 'risk']);
    Route::get('/portfolio/exposure', [PortfolioRiskController::class, 'exposure']);
    Route::get('/portfolio/correlations', [PortfolioRiskController::class, 'correlations']);

    Route::get('/performance/summary', [PerformanceController::class, 'summary']);
    Route::get('/performance/equity-curve', [PerformanceController::class, 'equityCurve']);
    Route::get('/performance/by-setup', [PerformanceController::class, 'bySetup']);
    Route::get('/performance/by-asset', [PerformanceController::class, 'byAsset']);
    Route::get('/performance/by-sector', [PerformanceController::class, 'bySector']);
    Route::get('/performance/by-regime', [PerformanceController::class, 'byRegime']);

    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts/{id}/read', [AlertController::class, 'read'])->whereNumber('id');

    Route::get('/quant/dashboard', [QuantController::class, 'dashboard']);
    Route::get('/quant/setup-metrics', [QuantController::class, 'setupMetrics']);

    Route::get('/backtests', [BacktestController::class, 'index']);
    Route::post('/backtests/run', [BacktestController::class, 'run']);

    Route::get('/optimizer/current', [OptimizerController::class, 'current']);
    Route::post('/optimizer/run', [OptimizerController::class, 'run']);
    Route::post('/optimizer/apply', [OptimizerController::class, 'apply']);
});
