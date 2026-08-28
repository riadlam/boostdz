<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->unsignedBigInteger('provider_order_id')->nullable();
            $table->string('signature')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->json('payload');
            $table->string('payload_hash', 64);
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'event', 'provider_order_id', 'payload_hash'], 'webhook_events_dedup_unique');
            $table->index('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
