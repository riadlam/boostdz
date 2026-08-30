<?php

namespace App\Http\Resources;

use App\Support\ServiceCatalogVisibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,
            'wallet' => WalletResource::make($this->whenLoaded('wallet')),
            'can_view_service_catalog' => ServiceCatalogVisibility::canView($this->resource),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
