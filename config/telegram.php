<?php

return [
    'enabled' => (bool) env('TELEGRAM_ENABLED', true),
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    // Local/dev: skip Telegram Accept/Decline and place the order immediately after receipt upload.
    // Set false on production when the webhook URL is live.
    'auto_accept' => (bool) env('TELEGRAM_AUTO_ACCEPT', true),
];
