<?php

use App\Models\CatalogCategory;
use App\Models\CatalogCategoryRule;
use App\Models\CatalogPlatform;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $telegram = CatalogPlatform::query()->where('slug', 'telegram')->first();

        if (! $telegram) {
            return;
        }

        foreach ([
            ['slug' => 'reaction_heart', 'name' => 'Reaction · Heart', 'sort_order' => 30],
            ['slug' => 'reaction_thumbs_up', 'name' => 'Reaction · Thumbs Up', 'sort_order' => 31],
            ['slug' => 'reaction_thumbs_down', 'name' => 'Reaction · Thumbs Down', 'sort_order' => 32],
            ['slug' => 'reaction_fire', 'name' => 'Reaction · Fire', 'sort_order' => 33],
            ['slug' => 'reaction_party', 'name' => 'Reaction · Party', 'sort_order' => 34],
            ['slug' => 'reaction_starstruck', 'name' => 'Reaction · Star-struck', 'sort_order' => 35],
            ['slug' => 'reaction_scream', 'name' => 'Reaction · Scream', 'sort_order' => 36],
            ['slug' => 'reaction_grin', 'name' => 'Reaction · Grin', 'sort_order' => 37],
            ['slug' => 'reaction_cry', 'name' => 'Reaction · Cry', 'sort_order' => 38],
            ['slug' => 'reaction_poo', 'name' => 'Reaction · Poo', 'sort_order' => 39],
            ['slug' => 'reaction_vomit', 'name' => 'Reaction · Vomit', 'sort_order' => 40],
            ['slug' => 'reactions', 'name' => 'Other Reactions', 'sort_order' => 41],
        ] as $category) {
            CatalogCategory::query()->updateOrCreate(
                [
                    'platform_id' => $telegram->id,
                    'slug' => $category['slug'],
                ],
                [
                    'name' => $category['name'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        CatalogCategory::query()
            ->where('platform_id', $telegram->id)
            ->where('slug', 'reaction_love')
            ->update(['is_active' => false]);

        CatalogCategoryRule::query()
            ->where('platform_slug', 'telegram')
            ->where('category_slug', 'reaction_love')
            ->update(['category_slug' => 'reactions', 'is_active' => true]);

        $rules = [
            ['telegram', 'name_regex', '/reaction.*(👎|dislike)|(?:👎|dislike).*reaction/iu', 'reaction_thumbs_down', null, 920],
            ['telegram', 'name_regex', '/reaction.*(❤|heart)|(?:❤|heart).*reaction/iu', 'reaction_heart', null, 910],
            ['telegram', 'name_regex', '/reaction.*(👍|thumbs?\s*up)|(?:👍|thumbs?\s*up).*reaction/iu', 'reaction_thumbs_up', null, 900],
            ['telegram', 'name_regex', '/reaction.*(🔥|\bfire\b)|(?:🔥|\bfire\b).*reaction/iu', 'reaction_fire', null, 890],
            ['telegram', 'name_regex', '/reaction.*(🎉|party)|(?:🎉|party).*reaction/iu', 'reaction_party', null, 880],
            ['telegram', 'name_regex', '/reaction.*🤩|🤩.*reaction/iu', 'reaction_starstruck', null, 870],
            ['telegram', 'name_regex', '/reaction.*(😱|scream)|(?:😱|scream).*reaction/iu', 'reaction_scream', null, 860],
            ['telegram', 'name_regex', '/reaction.*(😁|beaming)|(?:😁|beaming).*reaction/iu', 'reaction_grin', null, 850],
            ['telegram', 'name_regex', '/reaction.*(😢|crying)|(?:😢|crying).*reaction/iu', 'reaction_cry', null, 840],
            ['telegram', 'name_regex', '/reaction.*(💩|poo)|(?:💩|poo).*reaction/iu', 'reaction_poo', null, 830],
            ['telegram', 'name_regex', '/reaction.*(🤮|vomit)|(?:🤮|vomit).*reaction/iu', 'reaction_vomit', null, 820],
            ['telegram', 'category_contains', 'reaction', 'reactions', null, 700],
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
        $telegram = CatalogPlatform::query()->where('slug', 'telegram')->first();

        if (! $telegram) {
            return;
        }

        CatalogCategory::query()
            ->where('platform_id', $telegram->id)
            ->whereIn('slug', [
                'reaction_heart',
                'reaction_thumbs_up',
                'reaction_thumbs_down',
                'reaction_fire',
                'reaction_party',
                'reaction_starstruck',
                'reaction_scream',
                'reaction_grin',
                'reaction_cry',
                'reaction_poo',
                'reaction_vomit',
                'reactions',
            ])
            ->delete();

        CatalogCategory::query()
            ->where('platform_id', $telegram->id)
            ->where('slug', 'reaction_love')
            ->update(['is_active' => true, 'name' => 'Reactions']);

        CatalogCategoryRule::query()
            ->where('platform_slug', 'telegram')
            ->whereIn('category_slug', [
                'reaction_heart',
                'reaction_thumbs_up',
                'reaction_thumbs_down',
                'reaction_fire',
                'reaction_party',
                'reaction_starstruck',
                'reaction_scream',
                'reaction_grin',
                'reaction_cry',
                'reaction_poo',
                'reaction_vomit',
                'reactions',
            ])
            ->delete();
    }
};
