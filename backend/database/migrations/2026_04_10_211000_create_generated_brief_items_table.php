<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_brief_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('generated_brief_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitored_asset_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('rank_position');
            $table->decimal('final_score', 6, 2);
            $table->string('classification', 40);
            $table->string('setup_label')->nullable();
            $table->string('recommendation', 20);
            $table->decimal('entry', 14, 4)->nullable();
            $table->decimal('stop', 14, 4)->nullable();
            $table->decimal('target', 14, 4)->nullable();
            $table->decimal('risk_percent', 10, 4)->nullable();
            $table->decimal('reward_percent', 10, 4)->nullable();
            $table->decimal('rr_ratio', 10, 4)->nullable();
            $table->text('rationale')->nullable();
            $table->json('alert_flags')->nullable();
            $table->timestamps();

            $table->index(['generated_brief_id', 'rank_position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_brief_items');
    }
};
