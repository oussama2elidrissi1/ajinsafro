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
    | Used to store uploaded images in WP structure: uploads/Y/m/file.jpg
    |--------------------------------------------------------------------------
    */
    'uploads_path' => env('WP_UPLOADS_PATH', public_path('wp-content/uploads')),

    /*
    |--------------------------------------------------------------------------
    | WordPress uploads URL base (no trailing slash)
    | Used to build public URLs for images
    |--------------------------------------------------------------------------
    */
    'uploads_url' => env('WP_UPLOADS_URL', null),

];
