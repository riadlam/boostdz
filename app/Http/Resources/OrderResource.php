<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_order_id' => $this->provider_order_id,
            'link' => $this->link,
            'quantity' => $this->quantity,
            'status' => $this->status?->value,
            'start_count' => $this->start_count,
            'remains' => $this->remains,
            'delivery' => $this->delivery()->toArray(),
            'charge_dzd' => $this->charge_dzd,
            'cost_idr' => $this->cost_idr,
            'rate_idr_per_1k' => $this->rate_idr_per_1k,
            'cost_eur' => $this->cost_eur,
            'base_dzd' => $this->base_dzd,
            'profit_dzd' => $this->profit_dzd,
            'markup_percent' => $this->markup_percent,
            'pricing_snapshot' => $this->pricing_snapshot,
            'is_repeat' => $this->is_repeat,
            'error_message' => $this->error_message,
            'supports_refill' => $this->supportsRefill(),
            'can_request_refill' => $this->canRequestRefill(),
            'refill_block_reason' => $this->refillBlockReason(),
            'refill_lifetime' => $this->supportsRefill() && $this->hasLifetimeRefill(),
            'refill_warranty_days' => $this->supportsRefill() && ! $this->hasLifetimeRefill()
                ? $this->refillWarrantyDays()
                : null,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'last_status_check_at' => $this->last_status_check_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'service' => ServiceResource::make($this->whenLoaded('service')),
            'refills' => OrderRefillResource::collection($this->whenLoaded('refills')),
        ];
    }
}
