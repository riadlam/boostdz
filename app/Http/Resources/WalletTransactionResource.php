<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WalletTransaction */
class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'amount_dzd' => $this->amount_dzd,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'description' => $this->description,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
