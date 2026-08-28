<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'api_url',
        'api_key',
        'webhook_secret',
        'currency',
        'cached_balance',
        'balance_synced_at',
        'is_sandbox',
        'is_active',
        'rate_limit_per_minute',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'cached_balance' => 'decimal:4',
            'balance_synced_at' => 'datetime',
            'is_sandbox' => 'boolean',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function providerServices(): HasMany
    {
        return $this->hasMany(ProviderService::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(ProviderSyncLog::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }
}
