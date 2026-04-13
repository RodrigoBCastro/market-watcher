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
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_sector_mappings');
    }
};
