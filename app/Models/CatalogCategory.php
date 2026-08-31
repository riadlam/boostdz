<?php

namespace App\Models;

use App\Services\Catalog\FeaturedServiceHealth;
use App\Support\CatalogTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogCategory extends Model
{
    protected $fillable = [
        'platform_id',
        'slug',
        'name',
        'sort_order',
        'is_active',
        'featured_service_id',
        'featured_alert_sent_at',
        'basic_service_id',
        'gold_service_id',
        'premium_service_id',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'featured_service_id' => 'integer',
            'featured_alert_sent_at' => 'datetime',
            'basic_service_id' => 'integer',
            'gold_service_id' => 'integer',
            'premium_service_id' => 'integer',
        ];
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(CatalogPlatform::class, 'platform_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'catalog_category_id');
    }

    public function featuredService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'featured_service_id');
    }

    public function basicService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'basic_service_id');
    }

    public function goldService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'gold_service_id');
    }

    public function premiumService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'premium_service_id');
    }

    /**
     * @return array<string, int|null>
     */
    public function tierServiceIds(): array
    {
        return [
            CatalogTier::BASIC => $this->basic_service_id ? (int) $this->basic_service_id : null,
            CatalogTier::GOLD => $this->gold_service_id ? (int) $this->gold_service_id : null,
            CatalogTier::PREMIUM => $this->premium_service_id ? (int) $this->premium_service_id : null,
        ];
    }

    public function featuredServiceStatus(): string
    {
        return app(FeaturedServiceHealth::class)->featuredServiceStatus($this);
    }
}
