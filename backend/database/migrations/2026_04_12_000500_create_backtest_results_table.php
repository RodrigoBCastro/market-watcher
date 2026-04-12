<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backtest_results', function (Blueprint $table): void {
            $table->id();
            $table->string('strategy_name', 120);
            $table->unsignedInteger('total_trades')->default(0);
            $table->decimal('winrate', 7, 3)->default(0);
            $table->decimal('total_return', 10, 4)->default(0);
            $table->decimal('max_drawdown', 10, 4)->default(0);
            $table->decimal('profit_factor', 10, 4)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['strategy_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backtest_results');
    }
};
