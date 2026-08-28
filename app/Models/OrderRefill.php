<?php

namespace App\Models;

use App\Enums\RefillStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRefill extends Model
{
    protected $fillable = [
        'order_id',
        'provider_id',
        'provider_refill_id',
        'status',
        'raw_payload',
        'requested_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RefillStatus::class,
            'raw_payload' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
