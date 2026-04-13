<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_closed_positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_position_id')->constrained()->cascadeOnDelete();
            $table->date('exit_date');
            $table->decimal('exit_price', 14, 4);
            $table->decimal('quantity', 14, 4);
            $table->decimal('gross_pnl', 16, 2);
            $table->decimal('gross_pnl_percent', 8, 4);
            $table->string('result', 15);
            $table->unsignedInteger('duration_days');
            $table->string('exit_reason', 24);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['exit_date']);
            $table->index(['result']);
            $table->index(['exit_reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_closed_positions');
    }
};
