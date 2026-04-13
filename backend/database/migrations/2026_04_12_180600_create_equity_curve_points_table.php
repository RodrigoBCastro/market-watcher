<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equity_curve_points', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('reference_date');
            $table->decimal('equity_value', 16, 2);
            $table->decimal('cash_value', 16, 2);
            $table->decimal('invested_value', 16, 2);
            $table->decimal('open_risk_percent', 8, 4);
            $table->decimal('cumulative_return_percent', 8, 4);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'reference_date']);
            $table->index(['reference_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equity_curve_points');
    }
};
