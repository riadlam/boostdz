<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Wallet */
class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'currency' => $this->currency,
            'balance' => $this->balance,
            'locked_balance' => $this->locked_balance,
            'available_balance' => $this->availableBalance(),
        ];
    }
}
