<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_sync_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('external_id')->nullable();
            $table->string('event_type', 32);
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->string('status', 16)->default('pending');
            $table->timestamp('notified_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'event_type', 'status']);
            $table->index(['service_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_sync_events');
    }
};
