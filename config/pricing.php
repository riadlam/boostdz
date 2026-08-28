<?php

return [
    // 1 EUR = PRICING_EUR_IDR IDR (BuzzerPanel cost currency bridge)
    'eur_idr' => (float) env('PRICING_EUR_IDR', 20700),
    // 1 EUR = PRICING_EUR_DZD Algerian Dinar
    'eur_dzd' => (float) env('PRICING_EUR_DZD', 280),
    // Markup applied on top of base DZD price (50 = +50%)
    'markup_percent' => (float) env('PRICING_MARKUP_PERCENT', 50),
    // Max allowed difference when client sends expected_charge_dzd (DA)
    'charge_tolerance_dzd' => (float) env('PRICING_CHARGE_TOLERANCE', 0),
];
