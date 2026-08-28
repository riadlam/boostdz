<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        Provider::query()->updateOrCreate(
            ['slug' => config('buzzerpanel.provider_slug', 'buzzerpanel')],
            [
                'name' => config('buzzerpanel.provider_name', 'BuzzerPanel'),
                'api_url' => config('buzzerpanel.api_url'),
                'api_key' => config('buzzerpanel.api_key', ''),
                'currency' => 'IDR',
                'is_sandbox' => false,
                'is_active' => true,
                'meta' => [
                    'secret_key' => config('buzzerpanel.secret_key'),
                ],
            ],
        );

        ExchangeRate::query()->firstOrCreate(
            [
                'from_currency' => 'IDR',
                'to_currency' => 'DZD',
                'source' => 'seed',
            ],
            [
                'rate' => env('DEFAULT_IDR_DZD_RATE', '0.0085'),
                'fetched_at' => now(),
            ],
        );
    }
}
