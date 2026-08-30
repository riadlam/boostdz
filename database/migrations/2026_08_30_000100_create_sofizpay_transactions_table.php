<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sofizpay_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 16);
            $table->string('invoice_id', 64)->unique();
            $table->decimal('amount_dzd', 14, 2);
            $table->string('status', 32)->default('pending');
            $table->string('sofizpay_transaction_id')->nullable();
            $table->string('cib_transaction_id')->nullable();
            $table->text('payment_url')->nullable();
            $table->foreignId('deposit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->json('checkout_meta')->nullable();
            $table->json('raw_init_response')->nullable();
            $table->json('raw_callback_payload')->nullable();
            $table->json('raw_verify_response')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index('cib_transaction_id');
            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sofizpay_transactions');
    }
};
