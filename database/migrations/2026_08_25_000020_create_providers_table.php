<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('api_url');
            $table->text('api_key');
            $table->text('webhook_secret')->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->decimal('cached_balance', 20, 4)->nullable();
            $table->timestamp('balance_synced_at')->nullable();
            $table->boolean('is_sandbox')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('rate_limit_per_minute')->default(100);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
