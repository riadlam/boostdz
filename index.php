<?php

/**
 * Shared-hosting fallback when the document root cannot be set to /public.
 * Prefer pointing the domain document root to the public/ folder instead.
 */
require __DIR__.'/public/index.php';
