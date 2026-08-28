<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 32)->unique();
            $table->string('name');
            $table->string('icon_key', 32)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('catalog_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained('catalog_platforms')->cascadeOnDelete();
            $table->string('slug', 64);
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['platform_id', 'slug']);
            $table->index(['platform_id', 'is_active', 'sort_order']);
        });

        Schema::create('catalog_category_rules', function (Blueprint $table) {
            $table->id();
            $table->string('platform_slug', 32);
            $table->string('match_type', 32);
            $table->string('pattern', 255);
            $table->string('category_slug', 64);
            $table->string('quality_tier', 16)->nullable();
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['platform_slug', 'is_active', 'priority']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('catalog_category_id')
                ->nullable()
                ->after('provider_service_id')
                ->constrained('catalog_categories')
                ->nullOnDelete();
            $table->string('quality_tier', 16)->nullable()->after('type');
            $table->index(['catalog_category_id', 'is_active']);
            $table->index(['quality_tier', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('catalog_category_id');
            $table->dropIndex(['quality_tier', 'is_active']);
            $table->dropColumn('quality_tier');
        });

        Schema::dropIfExists('catalog_category_rules');
        Schema::dropIfExists('catalog_categories');
        Schema::dropIfExists('catalog_platforms');
    }
};
