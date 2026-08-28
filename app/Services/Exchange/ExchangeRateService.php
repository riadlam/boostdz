<?php

namespace App\Services\Exchange;

use App\Services\Pricing\PricingService;

class ExchangeRateService
{
    public function __construct(
        private readonly PricingService $pricing,
    ) {}

    /**
     * Base DZD per 1,000 IDR via EUR bridge (before markup).
     */
    public function convertIdrPerThousandToDzd(string|float $rateIdr, mixed $rate = null): string
    {
        unset($rate);

        return $this->pricing->baseDzdPerThousand($rateIdr);
    }

    public function applyMarkup(string $baseDzd, float $markupPercent): string
    {
        $sell = (float) $baseDzd * (1 + ($markupPercent / 100));

        return number_format($sell, 4, '.', '');
    }
}
