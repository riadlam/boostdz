<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'source',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'fetched_at' => 'datetime',
        ];
    }
}
