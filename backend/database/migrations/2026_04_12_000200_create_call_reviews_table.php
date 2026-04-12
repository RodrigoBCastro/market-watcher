<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trade_call_id')->constrained('trade_calls')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 20);
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['trade_call_id', 'created_at']);
            $table->index('decision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_reviews');
    }
};
