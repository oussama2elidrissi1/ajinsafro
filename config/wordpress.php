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

];
