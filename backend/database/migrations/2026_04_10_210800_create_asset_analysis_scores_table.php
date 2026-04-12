<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_analysis_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monitored_asset_id')->constrained()->cascadeOnDelete();
            $table->date('trade_date');
            $table->decimal('trend_score', 6, 2)->default(0);
            $table->decimal('moving_average_score', 6, 2)->default(0);
            $table->decimal('structure_score', 6, 2)->default(0);
            $table->decimal('momentum_score', 6, 2)->default(0);
            $table->decimal('volume_score', 6, 2)->default(0);
            $table->decimal('risk_score', 6, 2)->default(0);
            $table->decimal('market_context_score', 6, 2)->default(0);
            $table->decimal('final_score', 6, 2)->default(0);
            $table->string('classification', 40);
            $table->string('setup_code', 40)->nullable();
            $table->string('setup_label')->nullable();
            $table->string('recommendation', 20);
            $table->decimal('suggested_entry', 14, 4)->nullable();
            $table->decimal('suggested_stop', 14, 4)->nullable();
            $table->decimal('suggested_target', 14, 4)->nullable();
            $table->decimal('risk_percent', 10, 4)->nullable();
            $table->decimal('reward_percent', 10, 4)->nullable();
            $table->decimal('rr_ratio', 10, 4)->nullable();
            $table->json('alert_flags')->nullable();
            $table->text('rationale')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['monitored_asset_id', 'trade_date']);
            $table->index(['final_score', 'trade_date']);
            $table->index(['recommendation', 'trade_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_analysis_scores');
    }
};
