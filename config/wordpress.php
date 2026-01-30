<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WordPress tables prefix
    |--------------------------------------------------------------------------
    */
    'prefix' => env('WP_TABLE_PREFIX', 'cFdgeZ_'),

    /*
    |--------------------------------------------------------------------------
    | WordPress uploads path (absolute path on disk)
    | Default: public/wp-content/uploads (e.g. public_html/booking/public/wp-content/uploads)
    | When Laravel is under /booking, public_path() = .../booking/public
    |--------------------------------------------------------------------------
    */
    'uploads_path' => env('WP_UPLOADS_PATH', public_path('wp-content/uploads')),

    /*
    |--------------------------------------------------------------------------
    | WordPress uploads URL base (no trailing slash)
    | When null: url('/wp-content/uploads') is used so /booking is respected.
    | Set WP_UPLOADS_URL if uploads are served from another domain/path.
    |--------------------------------------------------------------------------
    */
    'uploads_url' => env('WP_UPLOADS_URL', null),

];
