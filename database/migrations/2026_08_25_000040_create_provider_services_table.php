<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('external_id');
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('type')->default('default');
            $table->decimal('rate_idr', 20, 4);
            $table->unsignedInteger('min');
            $table->unsignedBigInteger('max');
            $table->boolean('refill')->default(false);
            $table->boolean('dripfeed')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('raw_payload')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'external_id']);
            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_services');
    }
};
