<?php

namespace App\Models;

use App\Services\Content\StorefrontReviewsContent;
use Illuminate\Database\Eloquent\Model;

class StorefrontReviewsSettings extends Model
{
    protected $fillable = [
        'section_enabled',
        'show_stats',
        'likes_delivered_display',
        'satisfaction_rate_display',
        'show_leave_review_cta',
        'leave_review_url',
    ];

    protected function casts(): array
    {
        return [
            'section_enabled' => 'boolean',
            'show_stats' => 'boolean',
            'show_leave_review_cta' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $clearCache = static function (): void {
            app(StorefrontReviewsContent::class)->clearCache();
        };

        static::saved($clearCache);
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'section_enabled' => true,
            'show_stats' => true,
            'likes_delivered_display' => '10M+',
            'satisfaction_rate_display' => '98%',
            'show_leave_review_cta' => true,
            'leave_review_url' => null,
        ]);
    }
}
