<?php

namespace App\Services\Content;

use App\Models\StorefrontPlatformCard;
use Illuminate\Support\Facades\Cache;

class StorefrontPlatformCardsContent
{
    public const CACHE_KEY = 'storefront.platform_cards';

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{
     *     platforms: list<array{
     *         id: string,
     *         starting_price_dzd: int,
     *         review_count_display: string
     *     }>
     * }
     */
    public function payload(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function (): array {
            $platforms = StorefrontPlatformCard::query()
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (StorefrontPlatformCard $card): array => [
                    'id' => $card->platform_slug,
                    'starting_price_dzd' => (int) $card->starting_price_dzd,
                    'review_count_display' => $card->review_count_display,
                ])
                ->values()
                ->all();

            return [
                'platforms' => $platforms,
            ];
        });
    }

    public function publishedCount(): int
    {
        return StorefrontPlatformCard::query()->where('is_published', true)->count();
    }
}
