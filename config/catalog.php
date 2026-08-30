<?php

return [
    'service_catalog_viewer_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => trim($email),
        explode(',', (string) env('CATALOG_VIEWER_EMAILS', 'asminvfs12@gmail.com')),
    ))),
];
