<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_run_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sync_run_id')->constrained()->cascadeOnDelete();
            $table->string('level', 20);
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['sync_run_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_run_logs');
    }
};
