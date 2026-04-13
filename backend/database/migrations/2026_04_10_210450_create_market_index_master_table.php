<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_index_master', function (Blueprint $table): void {
            $table->id();
            $table->string('symbol', 20)->unique();
            $table->string('name');
            $table->string('source', 32)->default('brapi');
            $table->json('source_payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_index_master');
    }
};

