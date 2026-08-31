<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_categories', function (Blueprint $table) {
            $table->foreignId('basic_service_id')
                ->nullable()
                ->after('featured_alert_sent_at')
                ->constrained('services')
                ->nullOnDelete();

            $table->foreignId('gold_service_id')
                ->nullable()
                ->after('basic_service_id')
                ->constrained('services')
                ->nullOnDelete();

            $table->foreignId('premium_service_id')
                ->nullable()
                ->after('gold_service_id')
                ->constrained('services')
                ->nullOnDelete();

            $table->index('basic_service_id');
            $table->index('gold_service_id');
            $table->index('premium_service_id');
        });

        if (Schema::hasColumn('catalog_categories', 'featured_service_id')) {
            DB::table('catalog_categories')
                ->whereNotNull('featured_service_id')
                ->update(['basic_service_id' => DB::raw('featured_service_id')]);
        }
    }

    public function down(): void
    {
        Schema::table('catalog_categories', function (Blueprint $table) {
            $table->dropForeign(['basic_service_id']);
            $table->dropForeign(['gold_service_id']);
            $table->dropForeign(['premium_service_id']);
            $table->dropIndex(['basic_service_id']);
            $table->dropIndex(['gold_service_id']);
            $table->dropIndex(['premium_service_id']);
            $table->dropColumn(['basic_service_id', 'gold_service_id', 'premium_service_id']);
        });
    }
};
