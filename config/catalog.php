<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Catalog sync mode
    |--------------------------------------------------------------------------
    |
    | update_only — refresh prices/limits for existing store services only;
    |                 never import new BuzzerPanel packages or re-classify.
    | full          — legacy import: create new services and re-classify on sync.
    |
    */
    'sync_mode' => strtolower((string) env('CATALOG_SYNC_MODE', 'update_only')),

    /*
    |--------------------------------------------------------------------------
    | Telegram alerts for catalog sync changes (name changes, skipped imports)
    |--------------------------------------------------------------------------
    */
    'notify_sync_events' => (bool) env('CATALOG_NOTIFY_TELEGRAM', true),

    /*
    |--------------------------------------------------------------------------
    | Platforms hidden on the website (Telegram / later channels)
    |--------------------------------------------------------------------------
    |
    | Comma-separated platform slugs. Sync still imports them from BuzzerPanel,
    | but keeps catalog services and platform rows inactive on the web store.
    |
    */
    'web_disabled_platforms' => array_values(array_filter(array_map(
        static fn (string $slug): string => strtolower(trim($slug)),
        explode(',', (string) env('CATALOG_WEB_DISABLED_PLATFORMS', 'telegram,spotify,linkedin,other')),
    ))),
];
