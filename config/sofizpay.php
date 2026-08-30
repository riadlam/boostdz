<?php

return [
    'enabled' => (bool) env('SOFIZPAY_ENABLED', false),
    'sandbox' => (bool) env('SOFIZPAY_SANDBOX', false),
    'base_url' => rtrim((string) env('SOFIZPAY_BASE_URL', 'https://sofizpay.com'), '/'),
    'merchant_account' => env('SOFIZPAY_MERCHANT_ACCOUNT'),
    'timeout' => (int) env('SOFIZPAY_TIMEOUT', 30),
    'redirect' => strtolower((string) env('SOFIZPAY_REDIRECT', 'no')),
    'keep_return_url' => filter_var(env('SOFIZPAY_KEEP_RETURN_URL', true), FILTER_VALIDATE_BOOLEAN) ? 'True' : 'False',
    'default_language' => env('SOFIZPAY_LANGUAGE', 'ar'),
];
