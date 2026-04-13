<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_sector_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monitored_asset_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('sector');
            $table->string('subsector')->nullable();
            $table->string('segment')->nullable();
            $table->timestamps();

            $table->index(['sector']);
        });

        Schema::create('market_universe_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monitored_asset_id')->constrained()->cascadeOnDelete();
            $table->string('universe_type', 32);
            $table->boolean('is_active')->default(false);
            $table->text('inclusion_reason')->nullable();
            $table->text('exclusion_reason')->nullable();
            $table->timestamp('last_changed_at')->nullable();
            $table->timestamps();

            $table->unique(['monitored_asset_id', 'universe_type'], 'market_universe_memberships_unique_asset_type');
            $table->index(['universe_type', 'is_active'], 'market_universe_memberships_type_active_index');
        });

        Schema::create('market_universe_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('market_universe_membership_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitored_asset_id')->constrained()->cascadeOnDelete();
            $table->string('universe_type', 32);
            $table->string('event_type', 32);
            $table->boolean('from_active')->nullable();
            $table->boolean('to_active');
            $table->text('automatic_reason')->nullable();
            $table->text('manual_reason')->nullable();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['universe_type', 'event_type'], 'market_universe_events_type_event_index');
            $table->index(['monitored_asset_id', 'created_at'], 'market_universe_events_asset_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_universe_events');
        Schema::dropIfExists('market_universe_memberships');
        Schema::dropIfExists('asset_sector_mappings');
    }
};
