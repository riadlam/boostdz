<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('rate_idr_per_1k', 28, 4)->nullable()->after('cost_idr');
            $table->decimal('cost_eur', 20, 6)->nullable()->after('rate_idr_per_1k');
            $table->decimal('base_dzd', 14, 2)->nullable()->after('cost_eur');
            $table->decimal('profit_dzd', 14, 2)->nullable()->after('base_dzd');
            $table->decimal('markup_percent', 8, 2)->nullable()->after('profit_dzd');
            $table->json('pricing_snapshot')->nullable()->after('markup_percent');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'rate_idr_per_1k',
                'cost_eur',
                'base_dzd',
                'profit_dzd',
                'markup_percent',
                'pricing_snapshot',
            ]);
        });
    }
};
