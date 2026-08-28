<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE provider_services MODIFY rate_idr DECIMAL(28,4) NOT NULL');
        DB::statement('ALTER TABLE services MODIFY rate_idr DECIMAL(28,4) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE provider_services MODIFY rate_idr DECIMAL(20,4) NOT NULL');
        DB::statement('ALTER TABLE services MODIFY rate_idr DECIMAL(20,4) NOT NULL');
    }
};
