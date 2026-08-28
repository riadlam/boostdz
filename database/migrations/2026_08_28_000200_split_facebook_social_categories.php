<?php

use App\Models\CatalogCategory;
use App\Models\CatalogCategoryRule;
use App\Models\CatalogPlatform;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $facebook = CatalogPlatform::query()->where('slug', 'facebook')->first();

        if (! $facebook) {
            return;
        }

        CatalogCategory::query()
            ->where('platform_id', $facebook->id)
            ->where('slug', 'followers')
            ->update(['name' => 'Followers', 'sort_order' => 10]);

        foreach ([
            ['slug' => 'friends', 'name' => 'Friends', 'sort_order' => 11],
            ['slug' => 'members', 'name' => 'Members', 'sort_order' => 12],
        ] as $category) {
            CatalogCategory::query()->updateOrCreate(
                [
                    'platform_id' => $facebook->id,
                    'slug' => $category['slug'],
                ],
                [
                    'name' => $category['name'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        $rules = [
            ['facebook', 'name_regex', '/\bgroup\s*members?\b/iu', 'members', null, 850],
            ['facebook', 'name_contains', 'group members', 'members', null, 840],
            ['facebook', 'name_regex', '/\bfriends?\b/iu', 'friends', null, 830],
            ['facebook', 'name_contains', 'profile friends', 'friends', null, 820],
        ];

        foreach ($rules as [$platformSlug, $matchType, $pattern, $categorySlug, $qualityTier, $priority]) {
            $exists = CatalogCategoryRule::query()
                ->where('platform_slug', $platformSlug)
                ->where('match_type', $matchType)
                ->where('pattern', $pattern)
                ->where('category_slug', $categorySlug)
                ->exists();

            if ($exists) {
                continue;
            }

            CatalogCategoryRule::query()->create([
                'platform_slug' => $platformSlug,
                'match_type' => $matchType,
                'pattern' => $pattern,
                'category_slug' => $categorySlug,
                'quality_tier' => $qualityTier,
                'priority' => $priority,
                'is_active' => true,
            ]);
        }
    }

    public function down(): void
    {
        $facebook = CatalogPlatform::query()->where('slug', 'facebook')->first();

        if (! $facebook) {
            return;
        }

        CatalogCategory::query()
            ->where('platform_id', $facebook->id)
            ->where('slug', 'followers')
            ->update(['name' => 'Followers / Friends / Members']);

        CatalogCategory::query()
            ->where('platform_id', $facebook->id)
            ->whereIn('slug', ['friends', 'members'])
            ->delete();

        CatalogCategoryRule::query()
            ->where('platform_slug', 'facebook')
            ->whereIn('category_slug', ['friends', 'members'])
            ->delete();
    }
};
