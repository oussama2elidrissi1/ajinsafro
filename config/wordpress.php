<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invalidation HTTP endpoint (WordPress REST)
    |--------------------------------------------------------------------------
    |
    | After Laravel writes directly to wp_posts / postmeta / custom tables,
    | WordPress object caches (Redis/Memcached) may still serve stale posts.
    | Set WP_CATALOG_INVALIDATE_URL to the full REST URL registered by
    | ajinsafro-traveler-home (see includes/class-catalog-cache-invalidate.php).
    |
    */
    'invalidate_url' => env('WP_CATALOG_INVALIDATE_URL'),

    'invalidate_secret' => env('WP_CATALOG_INVALIDATE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Médias WordPress (même dossier que le front)
    |--------------------------------------------------------------------------
    |
    | Si APP_URL est l’admin (ex. booking.…) et le front est sur un autre domaine,
    | définir WP_UPLOADS_URL (URL publique de wp-content/uploads) ou WP_PUBLIC_SITE_URL
    | (racine du site WordPress). Sinon Laravel lit l’option siteurl en base wp.
    |
    */
    'uploads_path' => env('WP_UPLOADS_PATH'),

    'uploads_url' => env('WP_UPLOADS_URL'),

    'public_site_url' => env('WP_PUBLIC_SITE_URL'),

];
