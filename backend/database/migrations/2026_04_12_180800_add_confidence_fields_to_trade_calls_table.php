<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trade_calls', function (Blueprint $table): void {
            $table->decimal('confidence_score', 6, 2)->nullable()->after('advanced_classification');
            $table->string('confidence_label')->nullable()->after('confidence_score');
            $table->string('market_regime', 32)->nullable()->after('confidence_label');
            $table->decimal('expectancy_snapshot', 8, 4)->nullable()->after('market_regime');
            $table->decimal('market_context_score_snapshot', 8, 4)->nullable()->after('expectancy_snapshot');

            $table->index(['confidence_score']);
            $table->index(['market_regime']);
        });
    }

    public function down(): void
    {
        Schema::table('trade_calls', function (Blueprint $table): void {
            $table->dropIndex(['confidence_score']);
            $table->dropIndex(['market_regime']);
            $table->dropColumn([
                'confidence_score',
                'confidence_label',
                'market_regime',
                'expectancy_snapshot',
                'market_context_score_snapshot',
            ]);
        });
    }
};
