<?php

namespace Database\Seeders;

use App\Models\CatalogCategory;
use App\Models\CatalogCategoryRule;
use App\Models\CatalogPlatform;
use Illuminate\Database\Seeder;

class CatalogTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            ['slug' => 'instagram', 'name' => 'Instagram', 'icon_key' => 'instagram', 'sort_order' => 10],
            ['slug' => 'tiktok', 'name' => 'TikTok', 'icon_key' => 'tiktok', 'sort_order' => 20],
            ['slug' => 'youtube', 'name' => 'YouTube', 'icon_key' => 'youtube', 'sort_order' => 30],
            ['slug' => 'facebook', 'name' => 'Facebook', 'icon_key' => 'facebook', 'sort_order' => 40],
            ['slug' => 'twitter', 'name' => 'Twitter / X', 'icon_key' => 'twitter', 'sort_order' => 50],
            ['slug' => 'telegram', 'name' => 'Telegram', 'icon_key' => 'telegram', 'sort_order' => 60],
            ['slug' => 'threads', 'name' => 'Threads', 'icon_key' => 'threads', 'sort_order' => 70],
            ['slug' => 'spotify', 'name' => 'Spotify', 'icon_key' => 'spotify', 'sort_order' => 80],
            ['slug' => 'linkedin', 'name' => 'LinkedIn', 'icon_key' => 'linkedin', 'sort_order' => 90],
            ['slug' => 'other', 'name' => 'Other', 'icon_key' => 'other', 'sort_order' => 999],
        ];

        foreach ($platforms as $row) {
            CatalogPlatform::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [...$row, 'is_active' => true],
            );
        }

        $categoriesByPlatform = [
            'instagram' => [
                ['slug' => 'followers', 'name' => 'Followers', 'sort_order' => 10],
                ['slug' => 'likes', 'name' => 'Likes', 'sort_order' => 20],
                ['slug' => 'views', 'name' => 'Views', 'sort_order' => 30],
                ['slug' => 'comments', 'name' => 'Comments', 'sort_order' => 40],
                ['slug' => 'stories', 'name' => 'Stories', 'sort_order' => 50],
                ['slug' => 'reach', 'name' => 'Reach', 'sort_order' => 55],
                ['slug' => 'saves', 'name' => 'Saves', 'sort_order' => 60],
                ['slug' => 'impressions', 'name' => 'Impressions', 'sort_order' => 65],
                ['slug' => 'engagement', 'name' => 'Other engagement', 'sort_order' => 70],
                ['slug' => 'other', 'name' => 'Other', 'sort_order' => 900],
            ],
            'tiktok' => [
                ['slug' => 'followers', 'name' => 'Followers', 'sort_order' => 10],
                ['slug' => 'likes', 'name' => 'Likes', 'sort_order' => 20],
                ['slug' => 'views', 'name' => 'Views', 'sort_order' => 30],
                ['slug' => 'comments', 'name' => 'Comments', 'sort_order' => 40],
                ['slug' => 'shares', 'name' => 'Shares', 'sort_order' => 50],
                ['slug' => 'other', 'name' => 'Other', 'sort_order' => 900],
            ],
            'youtube' => [
                ['slug' => 'followers', 'name' => 'Subscribers', 'sort_order' => 10],
                ['slug' => 'views', 'name' => 'Views', 'sort_order' => 20],
                ['slug' => 'likes', 'name' => 'Likes / Dislikes', 'sort_order' => 30],
                ['slug' => 'comments', 'name' => 'Comments', 'sort_order' => 40],
                ['slug' => 'other', 'name' => 'Other', 'sort_order' => 900],
            ],
            'facebook' => [
                ['slug' => 'followers', 'name' => 'Followers', 'sort_order' => 10],
                ['slug' => 'friends', 'name' => 'Friends', 'sort_order' => 11],
                ['slug' => 'members', 'name' => 'Members', 'sort_order' => 12],
                ['slug' => 'page_likes', 'name' => 'Page Likes', 'sort_order' => 20],
                ['slug' => 'likes', 'name' => 'Post Likes', 'sort_order' => 30],
                ['slug' => 'comments', 'name' => 'Comments', 'sort_order' => 40],
                ['slug' => 'shares', 'name' => 'Shares', 'sort_order' => 50],
                ['slug' => 'views', 'name' => 'Video / Reels Views', 'sort_order' => 60],
                ['slug' => 'reaction_love', 'name' => 'Reaction · Love', 'sort_order' => 70],
                ['slug' => 'reaction_wow', 'name' => 'Reaction · Wow', 'sort_order' => 80],
                ['slug' => 'reaction_haha', 'name' => 'Reaction · Haha', 'sort_order' => 90],
                ['slug' => 'reaction_sad', 'name' => 'Reaction · Sad', 'sort_order' => 100],
                ['slug' => 'reaction_angry', 'name' => 'Reaction · Angry', 'sort_order' => 110],
                ['slug' => 'stories', 'name' => 'Story Reactions', 'sort_order' => 120],
                ['slug' => 'other', 'name' => 'Other', 'sort_order' => 900],
            ],
            'twitter' => [
                ['slug' => 'followers', 'name' => 'Followers', 'sort_order' => 10],
                ['slug' => 'likes', 'name' => 'Likes', 'sort_order' => 20],
                ['slug' => 'views', 'name' => 'Views', 'sort_order' => 30],
                ['slug' => 'comments', 'name' => 'Replies', 'sort_order' => 40],
                ['slug' => 'shares', 'name' => 'Retweets', 'sort_order' => 50],
                ['slug' => 'other', 'name' => 'Other', 'sort_order' => 900],
            ],
            'telegram' => [
                ['slug' => 'members', 'name' => 'Members', 'sort_order' => 10],
                ['slug' => 'views', 'name' => 'Post Views', 'sort_order' => 20],
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
                ['slug' => 'other', 'name' => 'Other', 'sort_order' => 900],
            ],
            'threads' => [
                ['slug' => 'followers', 'name' => 'Followers', 'sort_order' => 10],
                ['slug' => 'likes', 'name' => 'Likes', 'sort_order' => 20],
                ['slug' => 'other', 'name' => 'Other', 'sort_order' => 900],
            ],
            'spotify' => [
                ['slug' => 'followers', 'name' => 'Followers', 'sort_order' => 10],
                ['slug' => 'plays', 'name' => 'Plays', 'sort_order' => 20],
                ['slug' => 'other', 'name' => 'Other', 'sort_order' => 900],
            ],
            'linkedin' => [
                ['slug' => 'followers', 'name' => 'Followers', 'sort_order' => 10],
                ['slug' => 'likes', 'name' => 'Likes', 'sort_order' => 20],
                ['slug' => 'other', 'name' => 'Other', 'sort_order' => 900],
            ],
            'other' => [
                ['slug' => 'traffic', 'name' => 'Website Traffic', 'sort_order' => 10],
                ['slug' => 'other', 'name' => 'Other', 'sort_order' => 900],
            ],
        ];

        foreach ($categoriesByPlatform as $platformSlug => $categories) {
            $platform = CatalogPlatform::query()->where('slug', $platformSlug)->first();
            if (! $platform) {
                continue;
            }

            foreach ($categories as $category) {
                CatalogCategory::query()->updateOrCreate(
                    [
                        'platform_id' => $platform->id,
                        'slug' => $category['slug'],
                    ],
                    [
                        'name' => $category['name'],
                        'sort_order' => $category['sort_order'],
                        'is_active' => true,
                    ],
                );
            }
        }

        CatalogCategoryRule::query()->delete();

        $rules = [
            // Facebook post/story reactions (name-based; reaction_type set at classify time)
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

            // Facebook category buckets
            ['facebook', 'name_regex', '/\bgroup\s*members?\b/iu', 'members', null, 850],
            ['facebook', 'name_contains', 'group members', 'members', null, 840],
            ['facebook', 'name_regex', '/\bfriends?\b/iu', 'friends', null, 830],
            ['facebook', 'name_contains', 'profile friends', 'friends', null, 820],
            ['facebook', 'category_contains', 'page likes', 'page_likes', null, 700],
            ['facebook', 'category_contains', 'emoticons', 'likes', null, 650],
            ['facebook', 'category_contains', 'followers', 'followers', null, 700],
            ['facebook', 'category_contains', 'video views', 'views', null, 700],
            ['facebook', 'category_contains', 'reels', 'views', null, 700],
            ['facebook', 'category_contains', 'story', 'stories', null, 700],
            ['facebook', 'category_contains', 'comment', 'comments', null, 680],
            ['facebook', 'category_contains', 'share', 'shares', null, 680],
            ['facebook', 'category_contains', 'post likes', 'likes', null, 660],

            // Instagram
            ['instagram', 'category_contains', 'followers [guaranteed]', 'followers', 'premium', 800],
            ['instagram', 'category_contains', 'followers [not guaranteed]', 'followers', 'economy', 800],
            ['instagram', 'category_contains', 'followers', 'followers', null, 700],
            ['instagram', 'category_contains', 'likes', 'likes', null, 700],
            ['instagram', 'category_contains', 'views', 'views', null, 700],
            ['instagram', 'category_contains', 'comments', 'comments', null, 700],
            ['instagram', 'category_contains', 'story', 'stories', null, 720],
            ['instagram', 'name_contains', 'story', 'stories', null, 710],
            ['instagram', 'category_contains', 'reach', 'reach', null, 740],
            ['instagram', 'name_contains', 'reach', 'reach', null, 730],
            ['instagram', 'category_contains', 'saves', 'saves', null, 740],
            ['instagram', 'name_contains', 'save', 'saves', null, 730],
            ['instagram', 'category_contains', 'impressions', 'impressions', null, 740],
            ['instagram', 'name_contains', 'impression', 'impressions', null, 730],
            ['instagram', 'category_contains', 'engagement', 'engagement', null, 650],

            // TikTok
            ['tiktok', 'category_contains', 'not guaranteed', 'followers', 'economy', 800],
            ['tiktok', 'category_contains', 'follower', 'followers', null, 700],
            ['tiktok', 'category_contains', 'likes', 'likes', null, 700],
            ['tiktok', 'category_contains', 'views', 'views', null, 700],
            ['tiktok', 'category_contains', 'comment', 'comments', null, 700],
            ['tiktok', 'category_contains', 'share', 'shares', null, 700],

            // YouTube
            ['youtube', 'category_contains', 'subscriber', 'followers', null, 700],
            ['youtube', 'category_contains', 'views', 'views', null, 700],
            ['youtube', 'category_contains', 'likes', 'likes', null, 700],
            ['youtube', 'category_contains', 'comment', 'comments', null, 700],
            ['youtube', 'category_contains', 'seo', 'views', 'economy', 750],

            // Telegram reactions (name-based; require "reaction" context for emoji matches)
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
            ['telegram', 'category_contains', 'views', 'views', null, 700],
            ['telegram', 'category_contains', 'member', 'members', null, 700],
            ['telegram', 'category_contains', 'telegram', 'members', null, 600],

            ['*', 'category_contains', 'website traffic', 'traffic', null, 700],
        ];

        foreach ($rules as [$platformSlug, $matchType, $pattern, $categorySlug, $qualityTier, $priority]) {
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
}
