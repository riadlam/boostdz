<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderSyncLog extends Model
{
    protected $fillable = [
        'provider_id',
        'type',
        'status',
        'records_synced',
        'duration_ms',
        'message',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'records_synced' => 'integer',
            'duration_ms' => 'integer',
            'meta' => 'array',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
