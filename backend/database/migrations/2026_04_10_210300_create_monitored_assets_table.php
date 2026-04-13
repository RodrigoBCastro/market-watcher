<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_master_id')->nullable()->constrained('asset_master')->nullOnDelete()->unique();
            $table->string('ticker', 12)->unique();
            $table->string('name');
            $table->string('sector')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('monitoring_enabled')->default(true);
            $table->boolean('collect_data')->default(true);
            $table->boolean('eligible_for_analysis')->default(false);
            $table->boolean('eligible_for_calls')->default(false);
            $table->boolean('eligible_for_execution')->default(false);
            $table->string('universe_type', 32)->default('data_universe');

            $table->decimal('avg_daily_volume_20', 20, 2)->nullable();
            $table->decimal('avg_daily_financial_volume_20', 20, 2)->nullable();
            $table->decimal('avg_spread_percent', 10, 4)->nullable();
            $table->decimal('avg_trades_count_20', 20, 2)->nullable();
            $table->decimal('volatility_20', 10, 4)->nullable();

            $table->boolean('in_ibov')->default(false);
            $table->boolean('in_index_small_caps')->nullable();

            $table->decimal('liquidity_score', 10, 4)->nullable();
            $table->decimal('operability_score', 10, 4)->nullable();
            $table->timestamp('last_universe_review_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['monitoring_enabled', 'is_active']);
            $table->index(['collect_data', 'is_active']);
            $table->index(['eligible_for_analysis', 'is_active']);
            $table->index(['eligible_for_calls', 'is_active']);
            $table->index(['universe_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_assets');
    }
};
