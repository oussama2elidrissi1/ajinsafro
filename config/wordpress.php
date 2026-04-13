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

    /*
    |--------------------------------------------------------------------------
    | WP Media Bridge (upload via WordPress natif)
    |--------------------------------------------------------------------------
    |
    | Pour éviter les attachments "fantômes" quand Laravel n'écrit pas dans le même
    | volume que WordPress, on peut déléguer l'upload à WP via un endpoint REST privé
    | (ajinsafro-traveler-home): wp_upload_bits + wp_insert_attachment + metadata.
    |
    | Exemple :
    |   WP_MEDIA_UPLOAD_URL=https://ajinsafro.net/wp-json/ajth/v1/media-upload
    |   WP_MEDIA_VALIDATE_URL=https://ajinsafro.net/wp-json/ajth/v1/media-validate
    |
    | Auth: même secret que invalidate_secret (X-Ajth-Secret).
    |
    */
    'media_upload_url' => env('WP_MEDIA_UPLOAD_URL'),

    'media_validate_url' => env('WP_MEDIA_VALIDATE_URL'),

];
