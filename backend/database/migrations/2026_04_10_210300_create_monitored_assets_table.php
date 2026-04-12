<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticker', 12)->unique();
            $table->string('name');
            $table->string('sector')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('monitoring_enabled')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['monitoring_enabled', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_assets');
    }
};
