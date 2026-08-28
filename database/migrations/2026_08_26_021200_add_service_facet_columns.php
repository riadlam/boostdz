<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->boolean('is_hot')->default(false)->after('quality_tier');
            $table->boolean('is_cheap')->default(false)->after('is_hot');
            $table->string('start_class', 20)->nullable()->after('is_cheap');
            $table->unsignedSmallInteger('refill_days')->nullable()->after('start_class');
            $table->string('refill_mode', 20)->nullable()->after('refill_days');

            $table->index('start_class');
            $table->index('refill_mode');
            $table->index(['is_hot', 'is_cheap']);
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropIndex(['is_hot', 'is_cheap']);
            $table->dropIndex(['start_class']);
            $table->dropIndex(['refill_mode']);
            $table->dropColumn(['is_hot', 'is_cheap', 'start_class', 'refill_days', 'refill_mode']);
        });
    }
};
