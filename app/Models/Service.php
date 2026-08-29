<?php

namespace App\Models;

use App\Services\Pricing\PricingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'provider_service_id',
        'catalog_category_id',
        'slug',
        'platform',
        'name',
        'description',
        'type',
        'quality_tier',
        'is_hot',
        'is_cheap',
        'start_class',
        'refill_days',
        'refill_mode',
        'country_code',
        'audience_gender',
        'reaction_type',
        'min',
        'max',
        'rate_idr',
        'sell_rate_dzd',
        'markup_percent',
        'refill',
        'dripfeed',
        'is_active',
        'sort_order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'min' => 'integer',
            'max' => 'integer',
            'rate_idr' => 'decimal:4',
            'sell_rate_dzd' => 'decimal:4',
            'markup_percent' => 'decimal:2',
            'is_hot' => 'boolean',
            'is_cheap' => 'boolean',
            'refill_days' => 'integer',
            'refill' => 'boolean',
            'dripfeed' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'meta' => 'array',
        ];
    }

    public function providerService(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class);
    }

    public function catalogCategory(): BelongsTo
    {
        return $this->belongsTo(CatalogCategory::class, 'catalog_category_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function featuredForCategories(): HasMany
    {
        return $this->hasMany(CatalogCategory::class, 'featured_service_id');
    }

    public function calculateChargeDzd(int $quantity): string
    {
        return app(PricingService::class)->quote($this, $quantity)->charge_dzd;
    }

    public function requiresCustomComments(): bool
    {
        foreach ($this->customCommentTypeCandidates() as $candidate) {
            if ($this->typeRequiresCustomCommentsInput($candidate)) {
                return true;
            }
        }

        return false;
    }

    public function isCustomCommentsPackage(): bool
    {
        foreach ($this->customCommentTypeCandidates() as $candidate) {
            if (str_contains(strtolower($candidate), 'custom comments package')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected function customCommentTypeCandidates(): array
    {
        $candidates = [(string) $this->type];
        $meta = is_array($this->meta) ? $this->meta : [];
        if (! empty($meta['jenis'])) {
            $candidates[] = (string) $meta['jenis'];
        }

        return $candidates;
    }

    protected function typeRequiresCustomCommentsInput(?string $value): bool
    {
        $normalized = strtolower(trim((string) $value));

        if ($normalized === '') {
            return false;
        }

        // Standard SMM API label.
        if (str_contains($normalized, 'custom comment')) {
            return true;
        }

        // BuzzerPanel catalog uses jenis/type "Comment" for custom comments services.
        return $normalized === 'comment';
    }

    /**
     * @return list<string>
     */
    public static function parseCommentLines(?string $comments): array
    {
        if ($comments === null || trim($comments) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $comments) ?: [];

        return array_values(array_filter(array_map(static fn (string $line): string => trim($line), $lines)));
    }

    public function validateComments(?string $comments, int $quantity): void
    {
        if (! $this->requiresCustomComments()) {
            return;
        }

        $lines = self::parseCommentLines($comments);

        if ($lines === []) {
            throw new \InvalidArgumentException(__('api.comments.enter_at_least_one'));
        }

        if ($this->isCustomCommentsPackage()) {
            return;
        }

        if (count($lines) !== $quantity) {
            $count = count($lines);

            throw new \InvalidArgumentException(trans_choice('api.comments.count_mismatch', $count, [
                'count' => $count,
                'quantity' => $quantity,
            ]));
        }
    }
}
