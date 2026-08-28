<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PaymentSubmission */
class PaymentSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_method' => $this->payment_method,
            'link' => $this->link,
            'quantity' => $this->quantity,
            'amount_dzd' => $this->amount_dzd,
            'payer_reference' => $this->payer_reference,
            'status' => $this->status?->value,
            'order_id' => $this->order_id,
            'proof_url' => $this->proofPublicUrl(),
            'admin_note' => $this->admin_note,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'service' => ServiceResource::make($this->whenLoaded('service')),
            'order' => OrderResource::make($this->whenLoaded('order')),
        ];
    }
}
