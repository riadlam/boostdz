<?php

namespace App\Models;

use App\Services\Content\StorefrontReviewsContent;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'name',
        'quote',
        'role',
        'avatar_path',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'quote' => 'array',
            'role' => 'array',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $clearCache = static function (): void {
            app(StorefrontReviewsContent::class)->clearCache();
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    public function localizedQuote(?string $locale = null): string
    {
        return $this->localizedText('quote', $locale);
    }

    public function localizedRole(?string $locale = null): string
    {
        return $this->localizedText('role', $locale);
    }

    public function avatarUrl(): ?string
    {
        if (blank($this->avatar_path)) {
            return null;
        }

        if (str_starts_with($this->avatar_path, 'http://') || str_starts_with($this->avatar_path, 'https://') || str_starts_with($this->avatar_path, '/')) {
            return $this->avatar_path;
        }

        return asset('storage/'.$this->avatar_path);
    }

    protected function localizedText(string $field, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $values = $this->{$field};

        if (! is_array($values)) {
            return '';
        }

        return (string) ($values[$locale]
            ?? $values['en']
            ?? $values[array_key_first($values)]
            ?? '');
    }
}
