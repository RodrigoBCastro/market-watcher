<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->decimal('total_capital', 16, 2);
            $table->decimal('risk_per_trade_percent', 5, 2);
            $table->decimal('max_portfolio_risk_percent', 5, 2);
            $table->unsignedSmallInteger('max_open_positions');
            $table->decimal('max_position_size_percent', 5, 2);
            $table->decimal('max_sector_exposure_percent', 5, 2);
            $table->unsignedSmallInteger('max_correlated_positions');
            $table->boolean('allow_pyramiding')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_settings');
    }
};
