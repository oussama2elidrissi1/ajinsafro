<?php

use App\Http\Controllers\Api\PublicPackageController;
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
    Route::get('tours/{voyage_id}/package-state', [PublicPackageController::class, 'getPackageState'])
        ->name('tours.package-state');
    
    // Perform action on package session
    Route::post('package/session/{session_id}/action', [PublicPackageController::class, 'performAction'])
        ->name('package.action');
    
    // Create checkout token
    Route::post('checkout/create', [PublicPackageController::class, 'createCheckout'])
        ->name('checkout.create');
});
