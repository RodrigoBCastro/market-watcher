<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setup_metrics', function (Blueprint $table): void {
            $table->id();
            $table->string('setup_code', 40)->unique();
            $table->unsignedInteger('total_trades')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->decimal('winrate', 7, 3)->default(0);
            $table->decimal('avg_gain', 10, 4)->default(0);
            $table->decimal('avg_loss', 10, 4)->default(0);
            $table->decimal('expectancy', 10, 4)->default(0);
            $table->decimal('edge', 10, 4)->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['is_enabled', 'expectancy']);
            $table->index('winrate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setup_metrics');
    }
};
