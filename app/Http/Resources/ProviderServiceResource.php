<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ProviderService */
class ProviderServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'external_id' => $this->external_id,
            'name' => $this->name,
            'category' => $this->category,
            'type' => $this->type,
            'rate_idr' => $this->rate_idr,
            'min' => $this->min,
            'max' => $this->max,
            'refill' => $this->refill,
            'dripfeed' => $this->dripfeed,
            'is_active' => $this->is_active,
            'synced_at' => $this->synced_at?->toIso8601String(),
            'service' => ServiceResource::make($this->whenLoaded('service')),
        ];
    }
}
