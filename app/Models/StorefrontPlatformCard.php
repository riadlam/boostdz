<?php

namespace App\Models;

use App\Services\Content\StorefrontPlatformCardsContent;
use Illuminate\Database\Eloquent\Model;

class StorefrontPlatformCard extends Model
{
    protected $fillable = [
        'platform_slug',
        'starting_price_dzd',
        'review_count_display',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'starting_price_dzd' => 'integer',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $clearCache = static function (): void {
            app(StorefrontPlatformCardsContent::class)->clearCache();
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }
}
