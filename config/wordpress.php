<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WordPress Auto-Sync Configuration
    |--------------------------------------------------------------------------
    */

    'auto_sync_enabled' => env('WP_AUTO_SYNC_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | WordPress Webhook Security
    |--------------------------------------------------------------------------
    */

    'webhook_secret' => env('WP_WEBHOOK_SECRET', ''),
    'manual_sync_token' => env('WP_MANUAL_SYNC_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | WordPress Database Connection
    |--------------------------------------------------------------------------
    |
    | Connection name defined in database.php (usually 'wp')
    */

    'database_connection' => env('WP_DB_CONNECTION', 'wp'),

    /*
    |--------------------------------------------------------------------------
    | WordPress Table Prefix
    |--------------------------------------------------------------------------
    */

    'table_prefix' => env('WP_TABLE_PREFIX', 'cFdgeZ_'),

    /*
    |--------------------------------------------------------------------------
    | WordPress Site URL
    |--------------------------------------------------------------------------
    */

    'site_url' => env('WP_SITE_URL', 'https://ajinsafro.com'),

    /*
    |--------------------------------------------------------------------------
    | Sync Conflict Resolution
    |--------------------------------------------------------------------------
    | 
    | How to handle conflicts when both WP and Laravel have been modified
    | Options: 'wp_wins', 'laravel_wins', 'newest_wins'
    | Default: 'wp_wins' (as per your spec)
    */

    'conflict_resolution' => env('WP_SYNC_CONFLICT_RESOLUTION', 'wp_wins'),

    /*
    |--------------------------------------------------------------------------
    | Sync Options
    |--------------------------------------------------------------------------
    */

    'sync_featured_images' => env('WP_SYNC_IMAGES', true),
    'sync_gallery' => env('WP_SYNC_GALLERY', true),
    'sync_taxonomies' => env('WP_SYNC_TAXONOMIES', true),
    'sync_program' => env('WP_SYNC_PROGRAM', true),

    /*
    |--------------------------------------------------------------------------
    | Post Type
    |--------------------------------------------------------------------------
    */

    'tour_post_type' => 'st_tours',

    /*
    |--------------------------------------------------------------------------
    | Taxonomies to Sync
    |--------------------------------------------------------------------------
    */

    'taxonomies' => [
        'st_tour_type',
        'durations',
        'language',
        'languages', // Sync both language and languages identically
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta Keys to Ignore
    |--------------------------------------------------------------------------
    |
    | These meta keys will not be synchronized
    */

    'ignored_meta_keys' => [
        'rank_math_internal_links_processed',
        'rank_math_seo_score',
        'rank_math_focus_keyword',
        '_edit_lock',
        '_edit_last',
        '_transient_*',
    ],
];
