<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProviderService extends Model
{
    protected $fillable = [
        'provider_id',
        'external_id',
        'name',
        'category',
        'type',
        'rate_idr',
        'min',
        'max',
        'refill',
        'dripfeed',
        'is_active',
        'raw_payload',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
            'rate_idr' => 'decimal:4',
            'min' => 'integer',
            'max' => 'integer',
            'refill' => 'boolean',
            'dripfeed' => 'boolean',
            'is_active' => 'boolean',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function service(): HasOne
    {
        return $this->hasOne(Service::class);
    }
}
