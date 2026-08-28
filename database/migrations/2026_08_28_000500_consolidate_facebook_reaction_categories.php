<?php

use App\Models\CatalogCategory;
use App\Models\CatalogCategoryRule;
use App\Models\CatalogPlatform;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $facebook = CatalogPlatform::query()->where('slug', 'facebook')->first();

        if (! $facebook) {
            return;
        }

        $likesCategory = CatalogCategory::query()
            ->where('platform_id', $facebook->id)
            ->where('slug', 'likes')
            ->first();

        $storiesCategory = CatalogCategory::query()
            ->where('platform_id', $facebook->id)
            ->where('slug', 'stories')
            ->first();

        if (! $likesCategory || ! $storiesCategory) {
            return;
        }

        $legacyReactionSlugs = [
            'reaction_love' => 'love',
            'reaction_wow' => 'wow',
            'reaction_haha' => 'haha',
            'reaction_sad' => 'sad',
            'reaction_angry' => 'angry',
        ];

        foreach ($legacyReactionSlugs as $slug => $reactionType) {
            $legacyCategory = CatalogCategory::query()
                ->where('platform_id', $facebook->id)
                ->where('slug', $slug)
                ->first();

            if (! $legacyCategory) {
                continue;
            }

            $services = Service::query()
                ->where('catalog_category_id', $legacyCategory->id)
                ->get(['id', 'name', 'description']);

            foreach ($services as $service) {
                $hay = mb_strtolower($service->name.' '.$service->description);
                $targetCategoryId = str_contains($hay, 'story') ? $storiesCategory->id : $likesCategory->id;

                Service::query()->whereKey($service->id)->update([
                    'catalog_category_id' => $targetCategoryId,
                    'reaction_type' => $reactionType,
                ]);
            }

            CatalogCategory::query()
                ->whereKey($legacyCategory->id)
                ->update(['is_active' => false]);
        }

        CatalogCategoryRule::query()
            ->where('platform_slug', 'facebook')
            ->whereIn('category_slug', array_keys($legacyReactionSlugs))
            ->update(['category_slug' => 'likes', 'is_active' => false]);

        $rules = [
            ['facebook', 'name_regex', '/story.*(love|❤|😍|𝐥𝐨𝐯𝐞)|(love|❤|😍|𝐥𝐨𝐯𝐞).*story/iu', 'stories', null, 920],
            ['facebook', 'name_regex', '/story.*(wow|😲|𝐰𝐨𝐰)|(wow|😲|𝐰𝐨𝐰).*story/iu', 'stories', null, 920],
            ['facebook', 'name_regex', '/story.*(haha|😀|😂|𝐡𝐚𝐡𝐚)|(haha|😀|😂|𝐡𝐚𝐡𝐚).*story/iu', 'stories', null, 920],
            ['facebook', 'name_regex', '/story.*(sad|😢|𝐬𝐚𝐝)|(sad|😢|𝐬𝐚𝐝).*story/iu', 'stories', null, 920],
            ['facebook', 'name_regex', '/story.*(angry|😡|𝐚𝐧𝐠𝐫𝐲)|(angry|😡|𝐚𝐧𝐠𝐫𝐲).*story/iu', 'stories', null, 920],
            ['facebook', 'name_regex', '/love|❤|😍|𝐥𝐨𝐯𝐞/iu', 'likes', null, 900],
            ['facebook', 'name_regex', '/wow|😲|𝐰𝐨𝐰/iu', 'likes', null, 900],
            ['facebook', 'name_regex', '/haha|😀|😂|𝐡𝐚𝐡𝐚/iu', 'likes', null, 900],
            ['facebook', 'name_regex', '/sad|😢|𝐬𝐚𝐝/iu', 'likes', null, 900],
            ['facebook', 'name_regex', '/angry|😡|𝐚𝐧𝐠𝐫𝐲/iu', 'likes', null, 900],
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
            ->whereIn('slug', ['reaction_love', 'reaction_wow', 'reaction_haha', 'reaction_sad', 'reaction_angry'])
            ->update(['is_active' => true]);

        CatalogCategoryRule::query()
            ->where('platform_slug', 'facebook')
            ->whereIn('category_slug', ['likes', 'stories'])
            ->where('priority', '>=', 900)
            ->delete();
    }
};
