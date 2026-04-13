<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitored_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trade_call_id')->nullable()->constrained()->nullOnDelete();
            $table->date('entry_date');
            $table->decimal('entry_price', 14, 4);
            $table->decimal('quantity', 14, 4);
            $table->decimal('invested_amount', 16, 2);
            $table->decimal('current_price', 14, 4)->nullable();
            $table->decimal('stop_price', 14, 4)->nullable();
            $table->decimal('target_price', 14, 4)->nullable();
            $table->string('status', 20)->default('open');
            $table->decimal('confidence_score', 6, 2)->nullable();
            $table->string('confidence_label')->nullable();
            $table->string('market_regime', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['monitored_asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_positions');
    }
};
