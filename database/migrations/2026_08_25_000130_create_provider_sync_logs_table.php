<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('status', 32);
            $table->unsignedInteger('records_synced')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_sync_logs');
    }
};
