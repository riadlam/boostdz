<?php

namespace Database\Seeders;

use App\Models\StorefrontPlatformCard;
use Illuminate\Database\Seeder;

class StorefrontPlatformCardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            ['platform_slug' => 'instagram', 'starting_price_dzd' => 199, 'review_count_display' => '230+', 'sort_order' => 1],
            ['platform_slug' => 'tiktok', 'starting_price_dzd' => 199, 'review_count_display' => '235+', 'sort_order' => 2],
            ['platform_slug' => 'x', 'starting_price_dzd' => 199, 'review_count_display' => '235+', 'sort_order' => 3],
            ['platform_slug' => 'youtube', 'starting_price_dzd' => 199, 'review_count_display' => '210+', 'sort_order' => 4],
            ['platform_slug' => 'facebook', 'starting_price_dzd' => 199, 'review_count_display' => '235+', 'sort_order' => 5],
        ];

        foreach ($cards as $card) {
            StorefrontPlatformCard::query()->updateOrCreate(
                ['platform_slug' => $card['platform_slug']],
                $card,
            );
        }
    }
}
