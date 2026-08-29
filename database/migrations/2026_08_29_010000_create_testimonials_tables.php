<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('quote');
            $table->json('role');
            $table->string('avatar_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('storefront_reviews_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('section_enabled')->default(true);
            $table->boolean('show_stats')->default(true);
            $table->string('likes_delivered_display')->default('10M+');
            $table->string('satisfaction_rate_display')->default('98%');
            $table->boolean('show_leave_review_cta')->default(true);
            $table->string('leave_review_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_reviews_settings');
        Schema::dropIfExists('testimonials');
    }
};
