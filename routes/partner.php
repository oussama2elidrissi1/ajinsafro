<?php

use App\Http\Controllers\Partner\CatalogueController as PartnerCatalogueController;
use App\Http\Controllers\Partner\ClientsController as PartnerClientsController;
use App\Http\Controllers\Partner\CommissionsController as PartnerCommissionsController;
use App\Http\Controllers\Partner\DashboardController as PartnerDashboardController;
use App\Http\Controllers\Partner\DocumentsController as PartnerDocumentsController;
use App\Http\Controllers\Partner\ReservationsController as PartnerReservationsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Partner Portal Routes (subdomain)
|--------------------------------------------------------------------------
|
| These routes are loaded under the partenaire.ajinsafro.net subdomain.
| Keep business logic & DB identical; only split domain/routes/views/access.
|
*/

Route::middleware(['auth', 'partner'])->group(function () {
    Route::get('en-attente', fn () => view('partner.pending'))->name('partner.pending');

    Route::middleware('partner.validated')->group(function () {
        Route::get('/', fn () => redirect()->route('partner.dashboard'))->name('partner.home');

        Route::get('dashboard', [PartnerDashboardController::class, 'index'])->name('partner.dashboard');

        Route::get('reservations', [PartnerReservationsController::class, 'index'])->name('partner.reservations.index');
        Route::get('reservations/create', [PartnerReservationsController::class, 'create'])->name('partner.reservations.create');
        Route::post('reservations', [PartnerReservationsController::class, 'store'])->name('partner.reservations.store');
        Route::get('reservations/{reservation}', [PartnerReservationsController::class, 'show'])->name('partner.reservations.show');
        Route::get('reservations/{reservation}/edit', [PartnerReservationsController::class, 'edit'])->name('partner.reservations.edit');
        Route::put('reservations/{reservation}', [PartnerReservationsController::class, 'update'])->name('partner.reservations.update');
        Route::delete('reservations/{reservation}', [PartnerReservationsController::class, 'destroy'])->name('partner.reservations.destroy');

        Route::get('clients', [PartnerClientsController::class, 'index'])->name('partner.clients.index');
        Route::get('clients/create', [PartnerClientsController::class, 'create'])->name('partner.clients.create');
        Route::post('clients', [PartnerClientsController::class, 'store'])->name('partner.clients.store');
        Route::get('clients/{client}', [PartnerClientsController::class, 'show'])->name('partner.clients.show');
        Route::get('clients/{client}/edit', [PartnerClientsController::class, 'edit'])->name('partner.clients.edit');
        Route::put('clients/{client}', [PartnerClientsController::class, 'update'])->name('partner.clients.update');
        Route::delete('clients/{client}', [PartnerClientsController::class, 'destroy'])->name('partner.clients.destroy');

        Route::get('catalogue', [PartnerCatalogueController::class, 'index'])->name('partner.catalogue.index');
        Route::get('commissions', [PartnerCommissionsController::class, 'index'])->name('partner.commissions.index');
        Route::get('documents', [PartnerDocumentsController::class, 'index'])->name('partner.documents.index');

        // Portail partenaire v2: messagerie interne + factures/devis
        Route::get('messages', [\App\Http\Controllers\Partner\MessagesController::class, 'index'])->name('partner.messages.index');
        Route::get('messages/channels', [\App\Http\Controllers\Partner\MessagesController::class, 'channels'])->name('partner.messages.channels');
        Route::post('messages/direct', [\App\Http\Controllers\Partner\MessagesController::class, 'createDirect'])->name('partner.messages.direct');
        Route::get('messages/channels/{channel}/messages', [\App\Http\Controllers\Partner\MessagesController::class, 'messages'])->name('partner.messages.channel.messages');
        Route::post('messages/channels/{channel}/send', [\App\Http\Controllers\Partner\MessagesController::class, 'send'])->name('partner.messages.channel.send');

        Route::get('invoices', [\App\Http\Controllers\Partner\InvoicesController::class, 'index'])->name('partner.invoices.index');
        Route::get('invoices/{reservation}/file', [\App\Http\Controllers\Partner\InvoicesController::class, 'file'])->name('partner.invoices.file');

        Route::get('profile', fn () => view('partner.v2.profile.show', ['partner' => request()->user()->partner]))->name('partner.profile.show');
    });
});

/*
|--------------------------------------------------------------------------
| Partner Auth routes (subdomain)
|--------------------------------------------------------------------------
|
| Dedicated login page on partenaire.* (no internal admin pages).
|
*/

Route::middleware('guest')->group(function () {
    Route::get('login', fn () => view('partner_v2.auth.login'))->name('partner.login');
    Route::post('login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->name('partner.login.submit');
});

Route::post('logout', function (Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->away((string) config('app.public_url', 'https://ajinsafro.net'));
})->middleware('auth')->name('partner.logout');

