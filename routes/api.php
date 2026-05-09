<?php

use App\Http\Controllers\Api\PublicPackageController;
use App\Http\Controllers\Api\PublicHajjOmraBookingRequestController;
use App\Http\Controllers\Api\PublicHajjOmraPackageController;
use App\Http\Controllers\Api\PublicToursListController;
use App\Http\Controllers\Api\AccommodationPackageController;
use App\Http\Controllers\Api\ActivityOfferController;
use App\Http\Controllers\Api\TourFlightsController;
use App\Http\Controllers\Api\WpSyncWebhookController;
use App\Http\Controllers\Sync\PingController;
use App\Http\Controllers\Sync\WpToLaravelController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/accommodation-packages', [AccommodationPackageController::class, 'index'])
    ->name('api.accommodation-packages.index');

Route::get('/activity-offers', [ActivityOfferController::class, 'index'])
    ->name('api.activity-offers.index');

Route::get('/activity-offers/filters', [ActivityOfferController::class, 'filters'])
    ->name('api.activity-offers.filters');

Route::post('/group-deals/{slug}/participate', [\App\Http\Controllers\Front\GroupDealsController::class, 'participate'])
    ->name('api.group-deals.participate');

/*
|--------------------------------------------------------------------------
| Public Package Builder API
|--------------------------------------------------------------------------
*/
Route::prefix('public')->name('api.public.')->group(function () {
    Route::get('hajj-omra/packages', [PublicHajjOmraPackageController::class, 'index'])
        ->name('hajj-omra.packages.index');
    Route::get('hajj-omra/packages/{slug}', [PublicHajjOmraPackageController::class, 'show'])
        ->name('hajj-omra.packages.show');
    Route::post('hajj-omra/packages/{slug}/booking-requests', [PublicHajjOmraBookingRequestController::class, 'store'])
        ->name('hajj-omra.packages.booking-requests.store');

    // List all active tours
    Route::get('tours', [PublicToursListController::class, 'index'])
        ->name('tours.index');
    
    // Get package state for a tour
    Route::get('tours/{voyageId}/package-state', [PublicPackageController::class, 'getPackageState'])
        ->whereNumber('voyageId')
        ->name('tours.package-state');

    // Get flights for a tour (by WP post ID) – outbound (day 1) + inbound (last day)
    Route::get('tours/{wpPostId}/flights', TourFlightsController::class)
        ->whereNumber('wpPostId')
        ->name('tours.flights');
    
    // Perform action on package session
    Route::post('package/session/{sessionId}/action', [PublicPackageController::class, 'performAction'])
        ->name('package.action');
    
    // Create checkout token
    Route::post('checkout/create', [PublicPackageController::class, 'createCheckout'])
        ->name('checkout.create');
});

/*
|--------------------------------------------------------------------------
| Bi-directional Sync API (WordPress → Laravel)
|--------------------------------------------------------------------------
*/
Route::prefix('sync')->name('api.sync.')->group(function () {
    // Test endpoint
    Route::post('ping', [PingController::class, 'ping'])
        ->name('ping');
    
    // Upsert tour from WordPress
    Route::post('wp-to-laravel', [WpToLaravelController::class, 'upsertTour'])
        ->name('wp-to-laravel.upsert');
    
    // Delete tour from WordPress
    Route::post('wp-to-laravel/delete', [WpToLaravelController::class, 'deleteTour'])
        ->name('wp-to-laravel.delete');
});

/*
|--------------------------------------------------------------------------
| WordPress Bidirectional Sync Webhooks (New System)
|--------------------------------------------------------------------------
*/
Route::prefix('wp-sync')->name('api.wp-sync.')->group(function () {
    // WP notifies Laravel of tour update (HMAC secured)
    Route::post('/tour-updated', [WpSyncWebhookController::class, 'tourUpdated'])
        ->name('tour-updated');
    
    // Manual sync trigger (token secured)
    Route::get('/pull/{wpPostId}', [WpSyncWebhookController::class, 'manualPull'])
        ->name('pull');
});
