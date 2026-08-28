<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogSyncEvent extends Model
{
    public const TYPE_NAME_CHANGED = 'name_changed';

    public const TYPE_NEW_PROVIDER_SERVICE = 'new_provider_service';

    public const STATUS_PENDING = 'pending';

    public const STATUS_NOTIFIED = 'notified';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'provider_id',
        'provider_service_id',
        'service_id',
        'external_id',
        'event_type',
        'old_value',
        'new_value',
        'status',
        'notified_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
            'notified_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function providerService(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
