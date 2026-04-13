<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_history_sync_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monitored_asset_id')->unique()->constrained('monitored_assets')->cascadeOnDelete();
            $table->string('status', 32)->default('pending_bootstrap');
            $table->date('bootstrap_from_date')->nullable();
            $table->date('earliest_quote_date_found')->nullable();
            $table->date('latest_quote_date_synced')->nullable();
            $table->string('last_mode_used', 24)->nullable();
            $table->timestamp('last_bootstrap_at')->nullable();
            $table->timestamp('last_rolling_at')->nullable();
            $table->timestamp('bootstrap_completed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['last_mode_used']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_history_sync_states');
    }
};
