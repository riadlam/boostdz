<?php

namespace App\Services\Checkout;

use App\Exceptions\MinimumCheckoutException;
use App\Services\Pricing\PricingQuote;
use App\Services\Pricing\PricingService;

class CheckoutPolicy
{
    public function __construct(
        private readonly PricingService $pricing,
    ) {}

    public function minimumAmountDzd(): int
    {
        return (int) config('checkout.minimum_amount_dzd', 0);
    }

    public function minimumTopupDzd(): int
    {
        return $this->minimumAmountDzd();
    }

    public function isEnabled(): bool
    {
        return $this->minimumAmountDzd() > 0;
    }

    public function assertMinimumCheckout(PricingQuote $quote): void
    {
        $minimum = $this->minimumAmountDzd();

        if ($minimum <= 0) {
            return;
        }

        $charge = $this->pricing->roundDzd($quote->charge_dzd);

        if ($charge < $minimum) {
            throw new MinimumCheckoutException($minimum, $charge);
        }
    }

    public function assertMinimumTopup(float|string $amountDzd): void
    {
        $minimum = $this->minimumTopupDzd();

        if ($minimum <= 0) {
            return;
        }

        $amount = $this->pricing->roundDzd($amountDzd);

        if ($amount < $minimum) {
            throw new MinimumCheckoutException($minimum, $amount, __('api.checkout.minimum_topup', [
                'amount' => number_format($minimum, 0, '.', ' '),
            ]));
        }
    }

    /**
     * @return array{minimum_amount_dzd: int, minimum_topup_dzd: int}
     */
    public function publicSettings(): array
    {
        $minimum = $this->minimumAmountDzd();

        return [
            'minimum_amount_dzd' => $minimum,
            'minimum_topup_dzd' => $minimum,
        ];
    }
}
