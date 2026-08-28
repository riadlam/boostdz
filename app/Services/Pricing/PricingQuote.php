<?php

namespace App\Services\Pricing;

final class PricingQuote
{
    public function __construct(
        public readonly int $quantity,
        public readonly string $rate_idr_per_1k,
        public readonly string $cost_idr,
        public readonly string $cost_eur,
        public readonly string $base_dzd,
        public readonly string $charge_dzd,
        public readonly string $profit_dzd,
        public readonly string $sell_rate_dzd_per_1k,
        public readonly float $markup_percent,
        public readonly float $eur_idr_rate,
        public readonly float $eur_dzd_rate,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'quantity' => $this->quantity,
            'rate_idr_per_1k' => $this->rate_idr_per_1k,
            'cost_idr' => $this->cost_idr,
            'cost_eur' => $this->cost_eur,
            'base_dzd' => $this->base_dzd,
            'charge_dzd' => $this->charge_dzd,
            'profit_dzd' => $this->profit_dzd,
            'sell_rate_dzd_per_1k' => $this->sell_rate_dzd_per_1k,
            'markup_percent' => $this->markup_percent,
            'eur_idr_rate' => $this->eur_idr_rate,
            'eur_dzd_rate' => $this->eur_dzd_rate,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pricingSnapshot(): array
    {
        return [
            'eur_idr' => $this->eur_idr_rate,
            'eur_dzd' => $this->eur_dzd_rate,
            'markup_percent' => $this->markup_percent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function orderPricingAttributes(): array
    {
        return [
            'rate_idr_per_1k' => $this->rate_idr_per_1k,
            'cost_idr' => $this->cost_idr,
            'cost_eur' => $this->cost_eur,
            'base_dzd' => $this->base_dzd,
            'charge_dzd' => $this->charge_dzd,
            'profit_dzd' => $this->profit_dzd,
            'markup_percent' => number_format($this->markup_percent, 2, '.', ''),
            'pricing_snapshot' => $this->pricingSnapshot(),
        ];
    }
}
