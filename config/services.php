<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Hôtels — API externe (liste / catalogue)
    | GET {base}/hotels — Authorization: Bearer {key}
    */
    'hotel_api' => [
        'key' => env('HOTEL_API_KEY'),
        'base_url' => env('HOTEL_API_BASE_URL'),
        'timeout' => (int) env('HOTEL_API_TIMEOUT', 20),
    ],

    /*
    | RateHawk / ETG (Emerging Travel) — Basic Auth KEY_ID:API_KEY
    | POST /api/b2b/v3/search/serp/region/
    */
    'ratehawk' => [
        'key_id' => env('RATEHAWK_KEY_ID'),
        'api_key' => env('RATEHAWK_API_KEY'),
        'base_url' => env('RATEHAWK_API_BASE_URL', 'https://api.worldota.net'),
        'timeout' => (int) env('RATEHAWK_TIMEOUT', 45),
        /** Timeout de connexion TCP (secondes), ≤ timeout global. */
        'connect_timeout' => (int) env('RATEHAWK_CONNECT_TIMEOUT', 15),
        /** Guzzle : vérifier le certificat TLS. En local : RATEHAWK_VERIFY_SSL=false si erreur cURL 60. */
        'verify_ssl' => env('RATEHAWK_VERIFY_SSL', true),
        'default_residency' => env('RATEHAWK_DEFAULT_RESIDENCY', 'ma'),
        'default_currency' => env('RATEHAWK_DEFAULT_CURRENCY', 'MAD'),
        'language' => env('RATEHAWK_LANGUAGE', 'fr'),
        'hotels_limit' => (int) env('RATEHAWK_HOTELS_LIMIT', 30),
    ],

    /*
    | RapidAPI — Booking COM (DataCrawler) — x-rapidapi-key / x-rapidapi-host
    */
    'rapidapi' => [
        'key' => env('RAPIDAPI_KEY'),
        'host' => env('RAPIDAPI_HOST', 'booking-com15.p.rapidapi.com'),
        'base_url' => rtrim(env('RAPIDAPI_BASE_URL', 'https://booking-com15.p.rapidapi.com'), '/'),
        'timeout' => (int) env('RAPIDAPI_TIMEOUT', 45),
        'locale' => env('RAPIDAPI_LOCALE', 'fr'),
        'currency' => env('RAPIDAPI_CURRENCY', 'EUR'),
        'verify_ssl' => env('RAPIDAPI_VERIFY_SSL', true),
        'endpoint_locations' => env('RAPIDAPI_ENDPOINT_LOCATIONS', '/api/v1/hotels/searchDestination'),
        'endpoint_hotels_search' => env('RAPIDAPI_ENDPOINT_HOTELS_SEARCH', '/api/v1/hotels/searchHotels'),
        'endpoint_hotel_details' => env('RAPIDAPI_ENDPOINT_HOTEL_DETAILS', '/api/v1/hotels/getHotelDetails'),
        /** Nom du query param pour l’ID hôtel (booking-com15 getHotelDetails : hotel_id) */
        'hotel_detail_id_param' => env('RAPIDAPI_HOTEL_DETAIL_ID_PARAM', 'hotel_id'),
        /** Ex. description,photos,hotel_facilities — vide = non envoyé */
        'hotel_detail_extras' => env('RAPIDAPI_HOTEL_DETAIL_EXTRAS', ''),
        /** Paramètres GET searchHotels (booking-com15) — surcharges via .env si l’API évolue */
        'hotels_room_qty' => (int) env('RAPIDAPI_HOTELS_ROOM_QTY', 1),
        /** RapidAPI booking-com15 : page_number ≥ 1 (0 invalide) */
        'hotels_page_number' => (int) env('RAPIDAPI_HOTELS_PAGE_NUMBER', 1),
        'hotels_language_code' => env('RAPIDAPI_HOTELS_LANGUAGE_CODE', 'fr'),
    ],

];
