<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AssetAnalysisController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BacktestController;
use App\Http\Controllers\Api\BriefController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OptimizerController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\QuantController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\TradeCallController;
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

    Route::get('/assets', [AssetController::class, 'index']);
    Route::post('/assets', [AssetController::class, 'store']);
    Route::patch('/assets/{id}', [AssetController::class, 'update'])->whereNumber('id');
    Route::delete('/assets/{id}', [AssetController::class, 'destroy'])->whereNumber('id');

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

    Route::get('/quant/dashboard', [QuantController::class, 'dashboard']);
    Route::get('/quant/setup-metrics', [QuantController::class, 'setupMetrics']);

    Route::get('/backtests', [BacktestController::class, 'index']);
    Route::post('/backtests/run', [BacktestController::class, 'run']);

    Route::get('/optimizer/current', [OptimizerController::class, 'current']);
    Route::post('/optimizer/run', [OptimizerController::class, 'run']);
    Route::post('/optimizer/apply', [OptimizerController::class, 'apply']);
});
