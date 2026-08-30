<?php

namespace App\Models;

use App\Services\Catalog\FeaturedServiceHealth;
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
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'featured_service_id' => 'integer',
            'featured_alert_sent_at' => 'datetime',
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

    public function featuredServiceStatus(): string
    {
        return app(FeaturedServiceHealth::class)->featuredServiceStatus($this);
    }
}
