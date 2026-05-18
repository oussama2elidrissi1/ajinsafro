<?php

use App\Http\Controllers\Auth\PartnerRegistrationController;
use App\Http\Controllers\Booking\CheckoutController;
use App\Http\Controllers\Booking\StartBookingController;
use App\Http\Controllers\Front\GroupDealsController as FrontGroupDealsController;
use App\Http\Controllers\Front\HomeController as FrontHomeController;
use App\Http\Controllers\Front\SearchController as FrontSearchController;
use App\Http\Controllers\Front\VoyageController as FrontVoyageController;
use App\Http\Controllers\Front\VoyageReservationController;
use App\Http\Controllers\Internal\SyncInboundController;
use App\Http\Controllers\RatehawkHotelController;
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
Route::get('/group-deals', [FrontGroupDealsController::class, 'index'])->name('front.group-deals.index');
Route::get('/group-deals/{slug}', [FrontGroupDealsController::class, 'show'])->name('front.group-deals.show');
Route::post('/group-deals/{slug}/participate', [FrontGroupDealsController::class, 'participate'])->name('front.group-deals.participate');
Route::get('/voyages/{slug}', [FrontVoyageController::class, 'show'])->name('front.voyages.show');
Route::post('/voyages/{slug}/reserve', [VoyageReservationController::class, 'store'])->name('front.voyages.reserve');
Route::get('/voyages/{slug}/reservation/success/{reference}', [VoyageReservationController::class, 'success'])->name('front.voyages.reserve.success');

Route::get('/ratehawk/hotels', [RatehawkHotelController::class, 'index'])->name('ratehawk.hotels.index');
Route::get('/ratehawk/hotels/autocomplete', [RatehawkHotelController::class, 'autocomplete'])->name('ratehawk.hotels.autocomplete');

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
