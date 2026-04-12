<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_indicators', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monitored_asset_id')->constrained()->cascadeOnDelete();
            $table->date('trade_date');

            foreach ([5, 9, 10, 20, 21, 30, 40, 50, 72, 80, 100, 120, 150, 200] as $period) {
                $table->decimal("sma_{$period}", 14, 6)->nullable();
            }

            foreach ([5, 8, 9, 12, 17, 20, 21, 26, 34, 50, 72, 100, 144, 200] as $period) {
                $table->decimal("ema_{$period}", 14, 6)->nullable();
            }

            $table->decimal('rsi_7', 8, 4)->nullable();
            $table->decimal('rsi_14', 8, 4)->nullable();
            $table->decimal('macd_line', 14, 6)->nullable();
            $table->decimal('macd_signal', 14, 6)->nullable();
            $table->decimal('macd_histogram', 14, 6)->nullable();
            $table->decimal('atr_14', 14, 6)->nullable();
            $table->decimal('bollinger_mid', 14, 6)->nullable();
            $table->decimal('bollinger_upper', 14, 6)->nullable();
            $table->decimal('bollinger_lower', 14, 6)->nullable();
            $table->decimal('adx_14', 8, 4)->nullable();
            $table->decimal('stochastic_k', 8, 4)->nullable();
            $table->decimal('stochastic_d', 8, 4)->nullable();
            $table->decimal('roc', 10, 4)->nullable();
            $table->decimal('avg_volume_20', 16, 2)->nullable();
            $table->decimal('change_5', 10, 4)->nullable();
            $table->decimal('change_10', 10, 4)->nullable();
            $table->decimal('change_20', 10, 4)->nullable();
            $table->decimal('high_20', 14, 4)->nullable();
            $table->decimal('low_20', 14, 4)->nullable();
            $table->decimal('high_50', 14, 4)->nullable();
            $table->decimal('low_50', 14, 4)->nullable();
            $table->decimal('high_200', 14, 4)->nullable();
            $table->decimal('low_200', 14, 4)->nullable();
            $table->decimal('distance_ema_21', 10, 4)->nullable();
            $table->decimal('distance_sma_50', 10, 4)->nullable();
            $table->decimal('distance_sma_200', 10, 4)->nullable();
            $table->decimal('recent_volatility', 10, 4)->nullable();
            $table->decimal('avg_range', 10, 4)->nullable();
            $table->timestamps();

            $table->unique(['monitored_asset_id', 'trade_date']);
            $table->index(['trade_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technical_indicators');
    }
};
