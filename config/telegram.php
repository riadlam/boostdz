<?php

return [
    'enabled' => (bool) env('TELEGRAM_ENABLED', true),
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    // Local/dev: skip Telegram Accept/Decline and place the order immediately after receipt upload.
    // Set false on production when the webhook URL is live.
    'auto_accept' => (bool) env('TELEGRAM_AUTO_ACCEPT', true),

    // Dedicated bot for all payment notifications (CCP, Edahabiya, top-up). Same admin chat as above.
    'payment_bot_token' => env('TELEGRAM_BOT_TOKEN_PAYMENT'),
    'payment_admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
    'payment_webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET_PAYMENT', env('TELEGRAM_WEBHOOK_SECRET')),
];
