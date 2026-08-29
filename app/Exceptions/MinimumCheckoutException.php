<?php

namespace App\Exceptions;

use Exception;

class MinimumCheckoutException extends Exception
{
    public function __construct(
        public readonly int $minimumDzd,
        public readonly int $chargeDzd,
        ?string $message = null,
    ) {
        parent::__construct($message ?? __('api.checkout.minimum_checkout', [
            'amount' => number_format($minimumDzd, 0, '.', ' '),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => 'minimum_checkout_not_met',
            'minimum_amount_dzd' => $this->minimumDzd,
            'charge_dzd' => $this->chargeDzd,
        ];
    }
}
