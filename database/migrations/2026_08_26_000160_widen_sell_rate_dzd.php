<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE services MODIFY sell_rate_dzd DECIMAL(28,4) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE services MODIFY sell_rate_dzd DECIMAL(14,4) NOT NULL');
    }
};
