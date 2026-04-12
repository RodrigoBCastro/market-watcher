<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_calls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monitored_asset_id')->constrained()->cascadeOnDelete();
            $table->date('trade_date');
            $table->string('setup_code', 40);
            $table->string('setup_label');
            $table->decimal('entry_price', 14, 4);
            $table->decimal('stop_price', 14, 4);
            $table->decimal('target_price', 14, 4);
            $table->decimal('risk_percent', 10, 4);
            $table->decimal('reward_percent', 10, 4);
            $table->decimal('rr_ratio', 10, 4);
            $table->decimal('score', 6, 2);
            $table->decimal('final_rank_score', 8, 4)->default(0);
            $table->string('advanced_classification', 40)->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('generated_by_engine')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['monitored_asset_id', 'trade_date', 'setup_code'], 'trade_calls_unique_asset_date_setup');
            $table->index(['status', 'trade_date']);
            $table->index(['score', 'trade_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_calls');
    }
};
