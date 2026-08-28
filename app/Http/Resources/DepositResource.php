<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Deposit */
class DepositResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount_dzd' => $this->amount_dzd,
            'method' => $this->method,
            'status' => $this->status?->value,
            'proof_url' => $this->proof_path ? asset('storage/'.$this->proof_path) : null,
            'wired_amount_dzd' => $this->wired_amount_dzd,
            'provider_reference' => $this->provider_reference,
            'admin_note' => $this->admin_note,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
