<?php

namespace App\Models;

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
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
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
}
