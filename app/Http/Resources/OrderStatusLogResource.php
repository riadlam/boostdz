<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\OrderStatusLog */
class OrderStatusLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'start_count' => $this->start_count,
            'remains' => $this->remains,
            'source' => $this->source,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
