<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SofizPayTransaction */
class SofizPayTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'invoice_id' => $this->invoice_id,
            'purpose' => $this->purpose->value,
            'amount_dzd' => (string) $this->amount_dzd,
            'status' => $this->status->value,
            'payment_url' => $this->payment_url,
            'order_id' => $this->order_id,
            'deposit_id' => $this->deposit_id,
            'failure_reason' => $this->failure_reason,
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
