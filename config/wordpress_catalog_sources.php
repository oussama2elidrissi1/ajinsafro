<?php

/**
 * Cartographie des sources WordPress / Laravel pour le catalogue public Traveler.
 * Utilisée par WordPressCatalogInspectService et la commande wp:catalog-inspect.
 *
 * Connexion DB : config('database.connections.wp') — tables {prefix}posts, etc.
 * Préfixe Laravel : env('WP_DB_PREFIX', …).
 */
return [

    'activities' => [
        'laravel_catalog_table' => 'catalog_activities',
        'laravel_admin_route_param' => 'wp_post_id (ID dans URL /admin/wordpress/activities/{id}/edit)',
        'wp_post_type' => 'st_activity',
        'wp_posts_fields' => [
            'title' => 'post_title',
            'description' => 'post_content',
            'excerpt' => 'post_excerpt',
            'slug' => 'post_name',
            'status' => 'post_status',
        ],
        'traveler_table' => 'st_activity',
        'traveler_fk' => 'post_id',
        'traveler_fields' => [
            'location' => 'address (+ meta aj_activity_place_text si libellé)',
            'price' => 'adult_price, min_price, child_price',
            'duration' => 'duration',
            'other' => 'type_activity, max_people, rate_review, is_featured',
        ],
        'meta_keys_common' => [
            'thumbnail' => '_thumbnail_id',
            'gallery' => '_gallery',
            'category' => 'aj_activity_category',
            'place_text' => 'aj_activity_place_text',
            'mirror_traveler' => 'price, min_price, address, duration (WordPressTravelerMetaMirror)',
        ],
        'front_plugin_listing' => 'wp-plugin/ajinsafro-traveler-home/templates/activites.php — the_title() → post_title',
        'front_single' => 'Thème Traveler (hors dépôt) : single-st_activity.php ou équivalent — the_title() → post_title',
    ],

    'transfers' => [
        'laravel_catalog_table' => 'catalog_transfers',
        'laravel_admin_route_param' => 'wp_post_id (ID dans URL /admin/wordpress/transfers/{id}/edit)',
        'wp_post_type' => 'st_cars',
        'wp_posts_fields' => [
            'title' => 'post_title',
            'description' => 'post_content',
            'excerpt' => 'post_excerpt',
            'slug' => 'post_name',
            'status' => 'post_status',
        ],
        'traveler_table' => 'st_cars',
        'traveler_fk' => 'post_id',
        'traveler_fields' => [
            'location' => 'cars_address',
            'price' => 'cars_price, min_price, max_price',
            'vehicle' => 'via meta aj_transfer_vehicle_type, aj_transfer_type',
            'capacity' => 'number_car (+ meta aj_transfer_capacity)',
        ],
        'meta_keys_common' => [
            'thumbnail' => '_thumbnail_id',
            'from' => 'aj_transfer_from',
            'to' => 'aj_transfer_to',
            'mirror_traveler' => 'price, address, cars_price (WordPressTravelerMetaMirror)',
        ],
        'front_plugin_listing' => 'wp-plugin/ajinsafro-traveler-home/templates/transfert.php — the_title() → post_title',
        'front_single' => 'Thème Traveler — single-st_cars.php ou équivalent',
    ],

    'hotels' => [
        'laravel_catalog_table' => null,
        'laravel_admin' => 'App\\Http\\Controllers\\Admin\\WordPress\\HotelController — modèle App\\Models\\WpPost',
        'wp_post_type' => 'st_hotel',
        'wp_posts_fields' => [
            'title' => 'post_title',
            'description' => 'post_content',
            'excerpt' => 'post_excerpt',
            'slug' => 'post_name',
            'status' => 'post_status',
        ],
        'traveler_table' => 'st_hotel',
        'traveler_fk' => 'post_id',
        'traveler_fields' => [
            'location' => 'address',
            'price' => 'min_price',
            'stars' => 'hotel_star',
        ],
        'meta_keys_common' => [
            'thumbnail' => '_thumbnail_id',
            'gallery' => '_gallery / gallery',
            'mirror_traveler' => 'price, address, hotel_star (WordPressTravelerMetaMirror)',
        ],
        'front_plugin' => 'parts/accommodations.php — meta price/address ; hebergement.php catalogue lit st_hotel',
    ],

    'voyages' => [
        'laravel_catalog_table' => 'voyages',
        'laravel_admin' => 'circuits voyages + sync WpTourSyncService',
        'wp_post_type' => 'st_tours',
        'wp_posts_fields' => [
            'title' => 'post_title',
            'description' => 'post_content',
            'excerpt' => 'post_excerpt',
            'slug' => 'post_name',
            'status' => 'post_status',
        ],
        'traveler_table' => 'st_tours',
        'traveler_fk' => 'post_id',
        'traveler_fields' => [
            'location' => 'address (+ metas location)',
            'price' => 'adult_price, min_price, etc.',
            'duration' => 'duration_day (souvent)',
        ],
        'meta_keys_common' => [
            'program' => 'varies (Traveler)',
            'laravel_link' => '_aj_laravel_voyage_id',
        ],
        'front' => 'WpTourSyncService + thème Traveler single tour',
    ],

];
