<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Service */
class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'platform' => $this->platform,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'requires_custom_comments' => $this->requiresCustomComments(),
            'is_custom_comments_package' => $this->isCustomCommentsPackage(),
            'catalog_category_id' => $this->catalog_category_id,
            'quality_tier' => $this->quality_tier,
            'is_hot' => (bool) $this->is_hot,
            'is_cheap' => (bool) $this->is_cheap,
            'start_class' => $this->start_class,
            'refill_days' => $this->refill_days,
            'refill_mode' => $this->refill_mode,
            'country_code' => $this->country_code,
            'audience_gender' => $this->audience_gender,
            'reaction_type' => $this->reaction_type,
            'min' => $this->min,
            'max' => $this->max,
            'sell_rate_dzd' => $this->sell_rate_dzd,
            'markup_percent' => $this->markup_percent,
            'refill' => $this->refill,
            'dripfeed' => $this->dripfeed,
            'is_active' => $this->is_active,
            'meta' => $this->meta,
        ];
    }
}
