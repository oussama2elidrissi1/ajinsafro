<?php

use App\Http\Controllers\Api\PublicPackageController;
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

/*
|--------------------------------------------------------------------------
| Public Package Builder API
|--------------------------------------------------------------------------
*/
Route::prefix('public')->name('api.public.')->group(function () {
    // Get package state for a tour
    Route::get('tours/{voyageId}/package-state', [PublicPackageController::class, 'getPackageState'])
        ->whereNumber('voyageId')
        ->name('tours.package-state');
    
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
    // Upsert tour from WordPress
    Route::post('wp-to-laravel', [WpToLaravelController::class, 'upsertTour'])
        ->name('wp-to-laravel.upsert');
    
    // Delete tour from WordPress
    Route::post('wp-to-laravel/delete', [WpToLaravelController::class, 'deleteTour'])
        ->name('wp-to-laravel.delete');
});
