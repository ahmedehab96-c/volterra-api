<?php

return [
    // Recipient of new-inquiry notifications (no email is sent when unset)
    'admin_email' => env('ADMIN_EMAIL'),

    // Public catalog cache lifetime in seconds; admin edits clear it immediately
    'catalog_ttl' => (int) env('CATALOG_CACHE_TTL', 3600),
];
