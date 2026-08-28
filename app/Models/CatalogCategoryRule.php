<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogCategoryRule extends Model
{
    protected $fillable = [
        'platform_slug',
        'match_type',
        'pattern',
        'category_slug',
        'quality_tier',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
