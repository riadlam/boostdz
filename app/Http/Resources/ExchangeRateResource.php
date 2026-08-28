<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ExchangeRate */
class ExchangeRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_currency' => $this->from_currency,
            'to_currency' => $this->to_currency,
            'rate' => $this->rate,
            'source' => $this->source,
            'fetched_at' => $this->fetched_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
