<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_master', function (Blueprint $table): void {
            $table->id();
            $table->string('symbol', 20)->unique();
            $table->string('name');
            $table->string('asset_type', 24)->default('unknown');
            $table->string('sector', 120)->nullable();
            $table->string('logo_url')->nullable();
            $table->decimal('last_close', 14, 4)->nullable();
            $table->decimal('last_change_percent', 10, 4)->nullable();
            $table->unsignedBigInteger('last_volume')->nullable();
            $table->decimal('market_cap', 20, 2)->nullable();
            $table->string('source', 32)->default('brapi');
            $table->json('source_payload')->nullable();
            $table->boolean('is_listed')->default(true);
            $table->boolean('is_blacklisted_for_monitoring')->default(false);
            $table->unsignedInteger('missing_sync_count')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('delisted_at')->nullable();
            $table->string('delisting_reason')->nullable();
            $table->timestamp('blacklisted_at')->nullable();
            $table->string('blacklist_reason')->nullable();
            $table->timestamps();

            $table->index(['is_listed', 'is_blacklisted_for_monitoring']);
            $table->index(['asset_type', 'is_listed', 'is_blacklisted_for_monitoring']);
            $table->index(['sector']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_master');
    }
};
