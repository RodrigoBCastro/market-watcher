<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_briefs', function (Blueprint $table): void {
            $table->id();
            $table->date('brief_date')->unique();
            $table->text('market_summary');
            $table->string('market_bias', 40);
            $table->text('ibov_analysis');
            $table->text('risk_notes')->nullable();
            $table->text('conclusion');
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_briefs');
    }
};
