<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_indexes', function (Blueprint $table): void {
            $table->id();
            $table->string('symbol', 20);
            $table->date('trade_date');
            $table->decimal('open', 14, 4);
            $table->decimal('high', 14, 4);
            $table->decimal('low', 14, 4);
            $table->decimal('close', 14, 4);
            $table->unsignedBigInteger('volume')->nullable();
            $table->string('source', 32);
            $table->timestamps();

            $table->unique(['symbol', 'trade_date']);
            $table->index(['trade_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_indexes');
    }
};
