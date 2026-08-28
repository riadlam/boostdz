<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32);
            $table->unsignedBigInteger('start_count')->nullable();
            $table->unsignedBigInteger('remains')->nullable();
            $table->decimal('charge_idr', 20, 4)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('source', 32);
            $table->json('raw_payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_logs');
    }
};
