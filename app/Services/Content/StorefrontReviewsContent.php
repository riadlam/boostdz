<?php

namespace App\Services\Content;

use App\Models\StorefrontReviewsSettings;
use App\Models\Testimonial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StorefrontReviewsContent
{
    public const CACHE_KEY = 'storefront.reviews';

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{
     *     section_enabled: bool,
     *     stats: array{show: bool, likes_delivered: string, satisfaction_rate: string},
     *     leave_review: array{show: bool, url: string|null},
     *     testimonials: list<array{id: int, name: string, quote: string, role: string, avatar: string|null}>
     * }
     */
    public function payload(?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $cached = $this->cachedSnapshot();

        return [
            'section_enabled' => $cached['section_enabled'],
            'stats' => $cached['stats'],
            'leave_review' => $cached['leave_review'],
            'testimonials' => $this->localizeTestimonials($cached['testimonials'], $locale),
        ];
    }

    public function publishedCount(): int
    {
        return Testimonial::query()->where('is_published', true)->count();
    }

    public function draftCount(): int
    {
        return Testimonial::query()->where('is_published', false)->count();
    }

    /**
     * @return array{
     *     section_enabled: bool,
     *     stats: array{show: bool, likes_delivered: string, satisfaction_rate: string},
     *     leave_review: array{show: bool, url: string|null},
     *     testimonials: list<array{id: int, name: string, quote: array<string, string>, role: array<string, string>, avatar: string|null}>
     * }
     */
    protected function cachedSnapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function (): array {
            $settings = StorefrontReviewsSettings::current();

            $testimonials = Testimonial::query()
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (Testimonial $testimonial): array => [
                    'id' => $testimonial->id,
                    'name' => $testimonial->name,
                    'quote' => $testimonial->quote ?? [],
                    'role' => $testimonial->role ?? [],
                    'avatar' => $testimonial->avatarUrl(),
                ])
                ->values()
                ->all();

            return [
                'section_enabled' => $settings->section_enabled,
                'stats' => [
                    'show' => $settings->show_stats,
                    'likes_delivered' => $settings->likes_delivered_display,
                    'satisfaction_rate' => $settings->satisfaction_rate_display,
                ],
                'leave_review' => [
                    'show' => $settings->show_leave_review_cta,
                    'url' => $settings->leave_review_url,
                ],
                'testimonials' => $testimonials,
            ];
        });
    }

    /**
     * @param  list<array{id: int, name: string, quote: array<string, string>, role: array<string, string>, avatar: string|null}>  $testimonials
     * @return list<array{id: int, name: string, quote: string, role: string, avatar: string|null}>
     */
    protected function localizeTestimonials(array $testimonials, string $locale): array
    {
        return Collection::make($testimonials)
            ->map(fn (array $testimonial): array => [
                'id' => $testimonial['id'],
                'name' => $testimonial['name'],
                'quote' => $this->pickLocale($testimonial['quote'], $locale),
                'role' => $this->pickLocale($testimonial['role'], $locale),
                'avatar' => $testimonial['avatar'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $values
     */
    protected function pickLocale(array $values, string $locale): string
    {
        return (string) ($values[$locale]
            ?? $values['en']
            ?? $values[array_key_first($values) ?? ''] ?? '');
    }
}
