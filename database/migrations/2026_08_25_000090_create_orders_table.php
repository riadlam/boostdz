<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('provider_order_id')->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->string('link');
            $table->unsignedBigInteger('quantity');
            $table->unsignedInteger('runs')->nullable();
            $table->unsignedInteger('interval')->nullable();
            $table->text('comments')->nullable();
            $table->text('usernames')->nullable();
            $table->string('hashtag')->nullable();
            $table->unsignedInteger('posts')->nullable();
            $table->unsignedInteger('delay')->nullable();
            $table->timestamp('expiry')->nullable();
            $table->unsignedInteger('answer_number')->nullable();
            $table->json('payload_meta')->nullable();
            $table->string('status', 32)->default('pending');
            $table->unsignedBigInteger('start_count')->nullable();
            $table->unsignedBigInteger('remains')->nullable();
            $table->decimal('charge_dzd', 14, 2);
            $table->decimal('cost_idr', 20, 4)->nullable();
            $table->string('currency_provider', 3)->default('IDR');
            $table->string('country', 64)->nullable();
            $table->string('quality', 32)->nullable();
            $table->boolean('is_repeat')->default(false);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_status_check_at')->nullable();
            $table->unsignedInteger('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('raw_last_response')->nullable();
            $table->timestamps();

            $table->index('provider_order_id');
            $table->index('status');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
