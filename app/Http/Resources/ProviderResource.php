<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Provider */
class ProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'api_url' => $this->api_url,
            'currency' => $this->currency,
            'cached_balance' => $this->cached_balance,
            'balance_synced_at' => $this->balance_synced_at?->toIso8601String(),
            'is_sandbox' => $this->is_sandbox,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
