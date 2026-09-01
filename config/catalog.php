<?php

return [
    'service_catalog_viewer_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => trim($email),
        explode(',', (string) env('CATALOG_VIEWER_EMAILS', 'asminvfs12@gmail.com')),
    ))),
    'web_disabled_platforms' => array_values(array_filter(array_map(
        static fn (string $slug): string => trim($slug),
        explode(',', (string) env('CATALOG_WEB_DISABLED_PLATFORMS', '')),
    ))),
    'notify_sync_events' => filter_var(env('CATALOG_NOTIFY_SYNC_EVENTS', true), FILTER_VALIDATE_BOOLEAN),
];
