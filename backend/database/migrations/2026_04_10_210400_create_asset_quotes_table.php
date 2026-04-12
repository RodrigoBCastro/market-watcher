<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monitored_asset_id')->constrained()->cascadeOnDelete();
            $table->date('trade_date');
            $table->decimal('open', 14, 4);
            $table->decimal('high', 14, 4);
            $table->decimal('low', 14, 4);
            $table->decimal('close', 14, 4);
            $table->decimal('adjusted_close', 14, 4)->nullable();
            $table->unsignedBigInteger('volume');
            $table->string('source', 32);
            $table->timestamps();

            $table->unique(['monitored_asset_id', 'trade_date']);
            $table->index(['trade_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_quotes');
    }
};
