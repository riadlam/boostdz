<?php

return [
    'api_url' => env('BUZZERPANEL_API_URL', 'https://buzzerpanel.id/api/json.php'),
    'api_key' => env('BUZZERPANEL_API_KEY'),
    'secret_key' => env('BUZZERPANEL_SECRET_KEY'),
    'default_markup_percent' => (float) env('BUZZERPANEL_DEFAULT_MARKUP', 20),
    // services | services_1 (speed) | services2 | services3 (+ cat_id)
    'services_action' => env('BUZZERPANEL_SERVICES_ACTION', 'services3'),
    'default_refill_days' => (int) env('BUZZERPANEL_DEFAULT_REFILL_DAYS', 30),
    // Minimum seconds between automatic status polls for the same order.
    'status_poll_min_seconds' => (int) env('BUZZERPANEL_STATUS_POLL_MIN_SECONDS', 20),
    'provider_slug' => env('BUZZERPANEL_PROVIDER_SLUG', 'buzzerpanel'),
    'provider_name' => env('BUZZERPANEL_PROVIDER_NAME', 'BuzzerPanel'),
];
