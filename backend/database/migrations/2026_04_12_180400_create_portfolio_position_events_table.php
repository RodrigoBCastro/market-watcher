<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_position_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_position_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 30);
            $table->dateTime('event_date');
            $table->decimal('price', 14, 4)->nullable();
            $table->decimal('quantity', 14, 4)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['portfolio_position_id', 'event_date']);
            $table->index(['event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_position_events');
    }
};
