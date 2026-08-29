<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_platform_cards', function (Blueprint $table): void {
            $table->id();
            $table->string('platform_slug')->unique();
            $table->unsignedInteger('starting_price_dzd')->default(199);
            $table->string('review_count_display')->default('235+');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_platform_cards');
    }
};
