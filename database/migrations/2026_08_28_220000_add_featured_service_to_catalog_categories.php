<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_categories', function (Blueprint $table) {
            $table->foreignId('featured_service_id')
                ->nullable()
                ->after('is_active')
                ->constrained('services')
                ->nullOnDelete();

            $table->timestamp('featured_alert_sent_at')->nullable()->after('featured_service_id');

            $table->index('featured_service_id');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_categories', function (Blueprint $table) {
            $table->dropForeign(['featured_service_id']);
            $table->dropIndex(['featured_service_id']);
            $table->dropColumn(['featured_service_id', 'featured_alert_sent_at']);
        });
    }
};
