<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_outcomes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trade_call_id')->constrained('trade_calls')->cascadeOnDelete();
            $table->foreignId('monitored_asset_id')->constrained()->cascadeOnDelete();
            $table->string('setup_code', 40);
            $table->decimal('entry_price', 14, 4);
            $table->decimal('stop_price', 14, 4);
            $table->decimal('target_price', 14, 4);
            $table->decimal('exit_price', 14, 4);
            $table->string('result', 10);
            $table->decimal('pnl_percent', 10, 4);
            $table->unsignedInteger('duration_days')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique('trade_call_id');
            $table->index(['setup_code', 'result']);
            $table->index(['monitored_asset_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_outcomes');
    }
};
