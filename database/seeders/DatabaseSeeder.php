<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProviderSeeder::class,
            CatalogTaxonomySeeder::class,
        ]);

        $user = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'role' => 'admin',
                'is_active' => true,
                'timezone' => 'Africa/Algiers',
            ],
        );

        $user->wallet()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'currency' => 'DZD',
                'balance' => 0,
                'locked_balance' => 0,
            ],
        );
    }
}
