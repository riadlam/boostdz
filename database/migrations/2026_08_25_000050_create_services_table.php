<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_service_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('platform', 32);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('default');
            $table->unsignedInteger('min');
            $table->unsignedBigInteger('max');
            $table->decimal('rate_idr', 20, 4);
            $table->decimal('sell_rate_dzd', 14, 4);
            $table->decimal('markup_percent', 8, 2)->default(0);
            $table->boolean('refill')->default(false);
            $table->boolean('dripfeed')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
