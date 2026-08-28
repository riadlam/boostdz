<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogPlatform extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'icon_key',
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

    public function categories(): HasMany
    {
        return $this->hasMany(CatalogCategory::class, 'platform_id');
    }
}
