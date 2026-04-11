<?php

use App\Http\Controllers\Auth\PartnerRegistrationController;
use App\Http\Controllers\Booking\CheckoutController;
use App\Http\Controllers\Booking\StartBookingController;
use App\Http\Controllers\Front\HomeController as FrontHomeController;
use App\Http\Controllers\Front\SearchController as FrontSearchController;
use App\Http\Controllers\Front\VoyageController as FrontVoyageController;
use App\Http\Controllers\Internal\SyncInboundController;
use App\Http\Controllers\RapidapiHotelController;
use App\Http\Controllers\RatehawkHotelController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Website Routes
|--------------------------------------------------------------------------
|
| Domain: ajinsafro.net (or PUBLIC_DOMAIN when set).
| Keep public pages here only (no internal admin, no partner portal).
|
*/

Route::get('/', [FrontHomeController::class, 'index'])->name('front.home');
Route::get('/search', [FrontSearchController::class, 'index'])->name('front.search');
Route::get('/voyages', [FrontVoyageController::class, 'index'])->name('front.voyages.index');
Route::get('/voyages/{slug}', [FrontVoyageController::class, 'show'])->name('front.voyages.show');

Route::get('/ratehawk/hotels', [RatehawkHotelController::class, 'index'])->name('ratehawk.hotels.index');
Route::get('/ratehawk/hotels/autocomplete', [RatehawkHotelController::class, 'autocomplete'])->name('ratehawk.hotels.autocomplete');

Route::get('/rapidapi/hotels', [RapidapiHotelController::class, 'index'])->name('rapidapi.hotels.index');
Route::get('/rapidapi/hotels/{hotelId}', [RapidapiHotelController::class, 'show'])
    ->where('hotelId', '[0-9]+')
    ->name('rapidapi.hotels.show');

/** Test rapide RapidAPI searchDestination — à retirer en production. */
Route::get('/test-rapidapi', function () {
    $key = config('services.rapidapi.key');
    $host = (string) config('services.rapidapi.host', 'booking-com15.p.rapidapi.com');
    $baseUrl = rtrim((string) config('services.rapidapi.base_url', 'https://booking-com15.p.rapidapi.com'), '/');
    $url = $baseUrl.'/api/v1/hotels/searchDestination';
    $verify = filter_var(config('services.rapidapi.verify_ssl', true), FILTER_VALIDATE_BOOL);

    if (! is_string($key) || trim($key) === '') {
        return response()->json([
            'error' => 'RAPIDAPI_KEY manquant dans .env',
        ], 503);
    }

    $response = Http::withHeaders([
        'x-rapidapi-key' => trim($key),
        'x-rapidapi-host' => $host,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ])
        ->withOptions(['verify' => $verify])
        ->timeout(30)
        ->get($url, ['query' => 'paris']);

    $raw = $response->body();

    return response()->json([
        'http_status' => $response->status(),
        'body_decoded' => $response->json(),
        'body_raw' => $raw,
    ]);
})->name('test.rapidapi');

/** @deprecated Ancienne URL catalogue hôtels — redirige vers RateHawk (ETG). */
Route::get('/hotels-api', function () {
    return redirect()->route('ratehawk.hotels.index', [], 301);
})->name('hotels.api.index');

Route::get('/booking/start', StartBookingController::class)->name('booking.start');
Route::get('/booking/checkout/{token}', [CheckoutController::class, 'show'])->name('booking.checkout');
Route::post('/booking/checkout/{token}', [CheckoutController::class, 'process'])->name('booking.checkout.process');

Route::post('/internal/sync/wp-to-laravel', [SyncInboundController::class, 'wpToLaravel'])
    ->middleware('sync.token');

Route::get('devenir-partenaire', [PartnerRegistrationController::class, 'showRegistrationForm'])->name('partner.registration.form');
Route::post('devenir-partenaire', [PartnerRegistrationController::class, 'store'])->name('partner.registration.store');
Route::get('devenir-partenaire/success', fn () => view('auth.partner-registration-success'))->name('partner.registration.success');

