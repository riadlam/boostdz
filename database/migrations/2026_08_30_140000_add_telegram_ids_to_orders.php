<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('error_message');
            $table->string('telegram_message_id')->nullable()->after('telegram_chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['telegram_chat_id', 'telegram_message_id']);
        });
    }
};
