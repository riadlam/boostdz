<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('reaction_type', 16)->nullable()->after('audience_gender');
            $table->index(['catalog_category_id', 'reaction_type', 'is_active'], 'services_category_reaction_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('services_category_reaction_active_idx');
            $table->dropColumn('reaction_type');
        });
    }
};
