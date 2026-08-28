<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('audience_gender', 16)->nullable()->after('country_code');
            $table->index(['catalog_category_id', 'audience_gender', 'is_active'], 'services_category_audience_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('services_category_audience_active_idx');
            $table->dropColumn('audience_gender');
        });
    }
};
