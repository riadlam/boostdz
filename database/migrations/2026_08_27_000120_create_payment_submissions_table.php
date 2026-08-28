<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method', 32)->default('ccp_baridimob');
            $table->string('link');
            $table->unsignedBigInteger('quantity');
            $table->boolean('is_repeat')->default(false);
            $table->uuid('idempotency_key')->unique();
            $table->json('payload_meta')->nullable();
            $table->decimal('amount_dzd', 14, 2);
            $table->string('payer_reference')->nullable();
            $table->string('proof_path');
            $table->string('status', 32)->default('pending');
            $table->string('telegram_chat_id')->nullable();
            $table->string('telegram_message_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_submissions');
    }
};
