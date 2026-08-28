<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WebhookEvent */
class WebhookEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'event' => $this->event,
            'provider_order_id' => $this->provider_order_id,
            'signature_valid' => $this->signature_valid,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'processing_error' => $this->processing_error,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
