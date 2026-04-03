<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Réservation — statuts qui consomment le stock (chambres réservées)
    |--------------------------------------------------------------------------
    */
    'stock_consuming_statuses' => [
        'confirmed',
        'partially_paid',
        'paid',
    ],

    /*
    |--------------------------------------------------------------------------
    | Option / mise en attente — si true, "option" réserve temporairement le stock
    |--------------------------------------------------------------------------
    */
    'option_holds_stock' => env('BOOKING_OPTION_HOLDS_STOCK', false),

    'stock_hold_statuses' => [
        'option',
    ],

    /*
    |--------------------------------------------------------------------------
    | Aucun impact stock
    |--------------------------------------------------------------------------
    */
    'stock_neutral_statuses' => [
        'draft',
        'pending',
    ],

    /*
    |--------------------------------------------------------------------------
    | Libération du stock
    |--------------------------------------------------------------------------
    */
    'stock_release_statuses' => [
        'cancelled',
        'expired',
    ],

    /*
    |--------------------------------------------------------------------------
    | Remboursement — par défaut libère le stock (même logique qu’annulation)
    |--------------------------------------------------------------------------
    */
    'refund_releases_stock' => env('BOOKING_REFUND_RELEASES_STOCK', true),

    /*
    |--------------------------------------------------------------------------
    | Seuils automatiques (chambres restantes)
    |--------------------------------------------------------------------------
    */
    'room_limited_threshold' => 3,

    'departure_limited_threshold_places' => 5,
];
