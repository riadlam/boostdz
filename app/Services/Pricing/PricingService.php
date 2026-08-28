<?php

namespace App\Services\Pricing;

use App\Models\Service;
use InvalidArgumentException;

class PricingService
{
    public function eurIdrRate(): float
    {
        $rate = (float) config('pricing.eur_idr', 20700);

        if ($rate <= 0) {
            throw new InvalidArgumentException('PRICING_EUR_IDR must be greater than zero.');
        }

        return $rate;
    }

    public function eurDzdRate(): float
    {
        $rate = (float) config('pricing.eur_dzd', 280);

        if ($rate <= 0) {
            throw new InvalidArgumentException('PRICING_EUR_DZD must be greater than zero.');
        }

        return $rate;
    }

    public function markupPercent(): float
    {
        return (float) config('pricing.markup_percent', 50);
    }

    public function sellRateDzdPerThousand(string|float $rateIdr): string
    {
        $baseDzd = $this->baseDzdPerThousand($rateIdr);

        return (string) $this->roundDzd($this->applyMarkup($baseDzd));
    }

    public function baseDzdPerThousand(string|float $rateIdr): string
    {
        $eurPer1k = (float) $rateIdr / $this->eurIdrRate();

        return $this->convertEurToDzd(number_format($eurPer1k, 8, '.', ''));
    }

    public function quote(Service $service, int $quantity): PricingQuote
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $rateIdrPer1k = number_format((float) $service->rate_idr, 4, '.', '');
        $costIdr = number_format(($quantity / 1000) * (float) $rateIdrPer1k, 4, '.', '');
        $costEur = $this->convertIdrToEur($costIdr);
        $baseDzd = $this->convertEurToDzd($costEur);
        $chargeDzdRaw = (float) $this->applyMarkup($baseDzd);
        $baseDzdInt = $this->roundDzd($baseDzd);
        $chargeDzd = $this->roundDzd($chargeDzdRaw);
        $profitDzd = max(0, $chargeDzd - $baseDzdInt);
        $sellRatePer1k = $this->roundDzd($this->sellRateDzdPerThousand($rateIdrPer1k));

        return new PricingQuote(
            quantity: $quantity,
            rate_idr_per_1k: $rateIdrPer1k,
            cost_idr: $costIdr,
            cost_eur: $costEur,
            base_dzd: (string) $baseDzdInt,
            charge_dzd: (string) $chargeDzd,
            profit_dzd: (string) $profitDzd,
            sell_rate_dzd_per_1k: (string) $sellRatePer1k,
            markup_percent: $this->markupPercent(),
            eur_idr_rate: $this->eurIdrRate(),
            eur_dzd_rate: $this->eurDzdRate(),
        );
    }

    public function assertExpectedCharge(?string $expectedChargeDzd, PricingQuote $quote): void
    {
        if ($expectedChargeDzd === null || $expectedChargeDzd === '') {
            return;
        }

        $tolerance = (float) config('pricing.charge_tolerance_dzd', 0);
        $expected = $this->roundDzd($expectedChargeDzd);
        $actual = $this->roundDzd($quote->charge_dzd);

        if (abs($expected - $actual) > $tolerance) {
            throw new InvalidArgumentException(
                'Price changed. Expected '.number_format($expected, 0, '.', ' ').' DA but current price is '
                .number_format($actual, 0, '.', ' ').' DA. Refresh and try again.',
            );
        }
    }

    public function roundDzd(string|float $amount): int
    {
        return (int) round((float) $amount);
    }

    protected function convertIdrToEur(string|float $amountIdr): string
    {
        $eur = (float) $amountIdr / $this->eurIdrRate();

        return number_format($eur, 6, '.', '');
    }

    protected function convertEurToDzd(string|float $amountEur): string
    {
        $dzd = (float) $amountEur * $this->eurDzdRate();

        return number_format($dzd, 4, '.', '');
    }

    protected function applyMarkup(string|float $baseDzd): string
    {
        $markup = $this->markupPercent();
        $sell = (float) $baseDzd * (1 + ($markup / 100));

        return number_format($sell, 4, '.', '');
    }
}
