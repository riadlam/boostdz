<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'status',
        'start_count',
        'remains',
        'charge_idr',
        'currency',
        'source',
        'raw_payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'start_count' => 'integer',
            'remains' => 'integer',
            'charge_idr' => 'decimal:4',
            'raw_payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
