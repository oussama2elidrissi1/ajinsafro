<?php

use App\Http\Controllers\Admin\AccommodationsController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AccommodationPackageController;
use App\Http\Controllers\Admin\ActivityOfferController;
use App\Http\Controllers\Admin\AirlineController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BusinessReferenceController;
use App\Http\Controllers\Admin\CircuitsController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CustomersController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartureController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\HeroImageController;
use App\Http\Controllers\Admin\HomePageSettingsController;
use App\Http\Controllers\Admin\HotelBackofficeController;
use App\Http\Controllers\Admin\LocalMediaController;
use App\Http\Controllers\Admin\MessagerieController;
use App\Http\Controllers\Admin\OperationsController;
use App\Http\Controllers\Admin\PartnerAccountController;
use App\Http\Controllers\Admin\PartnerCommissionRuleController;
use App\Http\Controllers\Admin\PartnersController;
use App\Http\Controllers\Admin\ProductsController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProgramApiController;
use App\Http\Controllers\Admin\ReportingController;
use App\Http\Controllers\Admin\ReservationsController;
use App\Http\Controllers\Admin\RoleAccessController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TaxonomyTermController;
use App\Http\Controllers\Admin\TourHotelController;
use App\Http\Controllers\Admin\TourTransferController;
use App\Http\Controllers\Admin\TravelDayItemController;
use App\Http\Controllers\Admin\TravelProgramDayController;
use App\Http\Controllers\Admin\UserAccessController;
use App\Http\Controllers\Admin\VisaController;
use App\Http\Controllers\Admin\VoyageController;
use App\Http\Controllers\Admin\WordPress\HotelController;
use App\Http\Controllers\Admin\WordPress\ActivityController as WordPressActivityController;
use App\Http\Controllers\Admin\WordPress\TransferController as WordPressTransferController;
use App\Http\Controllers\Admin\WpMediaController;
use App\Http\Controllers\Admin\WpTourController;
use App\Http\Controllers\Auth\LockScreenController;
use App\Http\Controllers\Auth\PartnerRegistrationController;
use App\Http\Controllers\Booking\CheckoutController;
use App\Http\Controllers\Booking\StartBookingController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\Front\HomeController as FrontHomeController;
use App\Http\Controllers\Front\SearchController as FrontSearchController;
use App\Http\Controllers\Front\VoyageController as FrontVoyageController;
use App\Http\Controllers\Internal\SyncInboundController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Client\ClientProfileController;
use App\Http\Controllers\Client\ClientReservationsController;
use App\Http\Controllers\Partner\CatalogueController as PartnerCatalogueController;
use App\Http\Controllers\Partner\ClientsController as PartnerClientsController;
use App\Http\Controllers\Partner\CommissionsController as PartnerCommissionsController;
use App\Http\Controllers\Partner\DashboardController as PartnerDashboardController;
use App\Http\Controllers\Partner\DocumentsController as PartnerDocumentsController;
use App\Http\Controllers\Partner\ReservationsController as PartnerReservationsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Auth::routes();

Route::get('/', [FrontHomeController::class, 'index'])->name('front.home');
Route::get('/maintenance', fn () => view('front.maintenance'))->name('front.maintenance');
Route::get('/search', [FrontSearchController::class, 'index'])->name('front.search');
Route::get('/voyages', [FrontVoyageController::class, 'index'])->name('front.voyages.index');
Route::get('/voyages/{slug}', [FrontVoyageController::class, 'show'])->name('front.voyages.show');
Route::post('/voyages/{slug}/reserve', [\App\Http\Controllers\Front\VoyageReservationController::class, 'store'])
    ->name('front.voyages.reserve');
Route::get('/voyages/{slug}/reservation/success/{reference}', [\App\Http\Controllers\Front\VoyageReservationController::class, 'success'])
    ->name('front.voyages.reserve.success');

Route::get('/booking/start', StartBookingController::class)->name('booking.start');
Route::get('/booking/checkout/{token}', [CheckoutController::class, 'show'])->name('booking.checkout');
Route::post('/booking/checkout/{token}', [CheckoutController::class, 'process'])->name('booking.checkout.process');

Route::post('/internal/sync/wp-to-laravel', [SyncInboundController::class, 'wpToLaravel'])
    ->middleware('sync.token');

Route::get('devenir-partenaire', [PartnerRegistrationController::class, 'showRegistrationForm'])->name('partner.registration.form');
Route::post('devenir-partenaire', [PartnerRegistrationController::class, 'store'])->name('partner.registration.store');
Route::get('devenir-partenaire/success', fn () => view('auth.partner-registration-success'))->name('partner.registration.success');

Route::middleware('auth')->group(function () {
    Route::get('lock-screen', [LockScreenController::class, 'show'])->name('lock-screen');
    Route::post('lock-screen', [LockScreenController::class, 'unlock'])->name('lock-screen.unlock');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('lock-screen/activate', [LockScreenController::class, 'lock'])->name('lock-screen.activate');
});

Route::middleware(['auth', 'partner'])->prefix('partner')->name('partner.')->group(function () {
    Route::get('en-attente', fn () => view('partner.pending'))->name('pending');
    Route::middleware('partner.validated')->group(function () {
        Route::get('dashboard', [PartnerDashboardController::class, 'index'])->name('dashboard');
        Route::get('reservations', [PartnerReservationsController::class, 'index'])->name('reservations.index');
        Route::get('reservations/create', [PartnerReservationsController::class, 'create'])->name('reservations.create');
        Route::post('reservations', [PartnerReservationsController::class, 'store'])->name('reservations.store');
        Route::get('reservations/{reservation}', [PartnerReservationsController::class, 'show'])->name('reservations.show');
        Route::get('reservations/{reservation}/edit', [PartnerReservationsController::class, 'edit'])->name('reservations.edit');
        Route::put('reservations/{reservation}', [PartnerReservationsController::class, 'update'])->name('reservations.update');
        Route::delete('reservations/{reservation}', [PartnerReservationsController::class, 'destroy'])->name('reservations.destroy');
        Route::get('clients', [PartnerClientsController::class, 'index'])->name('clients.index');
        Route::get('clients/create', [PartnerClientsController::class, 'create'])->name('clients.create');
        Route::post('clients', [PartnerClientsController::class, 'store'])->name('clients.store');
        Route::get('clients/{client}', [PartnerClientsController::class, 'show'])->name('clients.show');
        Route::get('clients/{client}/edit', [PartnerClientsController::class, 'edit'])->name('clients.edit');
        Route::put('clients/{client}', [PartnerClientsController::class, 'update'])->name('clients.update');
        Route::delete('clients/{client}', [PartnerClientsController::class, 'destroy'])->name('clients.destroy');
        Route::get('catalogue', [PartnerCatalogueController::class, 'index'])->name('catalogue.index');
        Route::get('commissions', [PartnerCommissionsController::class, 'index'])->name('commissions.index');
        Route::get('documents', [PartnerDocumentsController::class, 'index'])->name('documents.index');
    });
});

Route::middleware(['auth', 'client'])->prefix('client')->name('client.')->group(function () {
    Route::get('dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
    Route::get('reservations', [ClientReservationsController::class, 'index'])->name('reservations.index');
    Route::get('reservations/{reservation}', [ClientReservationsController::class, 'show'])->name('reservations.show')->whereNumber('reservation');
    Route::get('profile', [ClientProfileController::class, 'edit'])->name('profile.edit');
    Route::post('profile', [ClientProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'admin', 'ensure.not.locked', 'route.permission'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/vue-globale', [DashboardController::class, 'page'])->name('dashboard.vue-globale')->defaults('submenu', 'vue-globale');
    Route::get('dashboard/statistiques', [DashboardController::class, 'page'])->name('dashboard.statistiques')->defaults('submenu', 'statistiques');
    Route::get('dashboard/alertes', [DashboardController::class, 'page'])->name('dashboard.alertes')->defaults('submenu', 'alertes');
    Route::get('dashboard/v2', [DashboardController::class, 'v2'])->name('dashboard.v2');
    Route::get('dashboard/v3', [DashboardController::class, 'v3'])->name('dashboard.v3');
    Route::get('reservations', [ReservationsController::class, 'index'])->name('reservations.index');
    Route::get('reservations/toutes', [ReservationsController::class, 'page'])->name('reservations.toutes')->defaults('submenu', 'toutes');
    Route::get('reservations/en-attente', [ReservationsController::class, 'page'])->name('reservations.en-attente')->defaults('submenu', 'en-attente');
    Route::get('reservations/confirmees', [ReservationsController::class, 'page'])->name('reservations.confirmees')->defaults('submenu', 'confirmees');
    Route::get('reservations/annulees', [ReservationsController::class, 'page'])->name('reservations.annulees')->defaults('submenu', 'annulees');
    Route::get('reservations/calendrier', [ReservationsController::class, 'calendar'])->name('reservations.calendrier');
    Route::get('reservations/calendrier/events', [ReservationsController::class, 'calendarEvents'])->name('reservations.calendrier.events');
    Route::get('reservations/calendrier/event-details', [ReservationsController::class, 'calendarEventDetails'])->name('reservations.calendrier.event-details');
    Route::get('reservations/paiements', [ReservationsController::class, 'page'])->name('reservations.paiements')->defaults('submenu', 'paiements');

    Route::get('reservations/messages', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages');
    Route::get('reservations/messages/create', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.create');
    Route::post('reservations/messages', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.store');
    Route::get('reservations/messages/{message}', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.show')->whereNumber('message');
    Route::post('reservations/messages/{message}/star', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.star')->whereNumber('message');
    Route::post('reservations/messages/{message}/trash', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.trash')->whereNumber('message');
    Route::post('reservations/messages/{message}/label', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.label')->whereNumber('message');
    Route::post('reservations/messages/{message}/important', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.important')->whereNumber('message');

    Route::get('reservations/create', [ReservationsController::class, 'create'])->name('reservations.create');
    Route::get('reservations/hotels-rooms', [ReservationsController::class, 'hotelsRooms'])->name('reservations.hotels-rooms');
    Route::get('reservations/receipt', [ReservationsController::class, 'showReceipt'])->name('reservations.receipt');
    Route::post('reservations', [ReservationsController::class, 'store'])->name('reservations.store');
    Route::get('reservations/hub-debug', [ReservationsController::class, 'hubDebug'])->name('reservations.hub-debug');
    Route::get('reservations/hub-refresh', [ReservationsController::class, 'hubRefresh'])->name('reservations.hub-refresh');
    Route::post('reservations/{reservation}/payments', [ReservationsController::class, 'storePayment'])->name('reservations.payments.store');
    Route::post('reservations/{reservation}/documents', [ReservationsController::class, 'storeDocument'])->name('reservations.documents.store');
    Route::post('reservations/{reservation}/notes', [ReservationsController::class, 'storeNote'])->name('reservations.notes.store');
    Route::post('reservations/{reservation}/cancel', [ReservationsController::class, 'cancel'])->name('reservations.cancel');
    Route::get('reservations/{reservation}/invoice', [ReservationsController::class, 'invoice'])->name('reservations.invoice');
    Route::get('reservations/{reservation}/dossier/pdf', [ReservationsController::class, 'dossierPdf'])->name('reservations.dossier.pdf');
    Route::get('reservations/{reservation}/payments/{payment}/receipt/pdf', [ReservationsController::class, 'paymentReceiptPdf'])->name('reservations.payments.receipt.pdf');
    Route::get('reservations/{reservation}/panel', [ReservationsController::class, 'panel'])->name('reservations.panel');
    Route::get('reservations/{reservation}/edit', [ReservationsController::class, 'edit'])->name('reservations.edit');
    Route::put('reservations/{reservation}', [ReservationsController::class, 'update'])->name('reservations.update');
    Route::delete('reservations/{reservation}', [ReservationsController::class, 'destroy'])->name('reservations.destroy');
    Route::post('reservations/{reservation}/validate', [ReservationsController::class, 'validateReservation'])->name('reservations.validate');
    Route::post('reservations/{reservation}/pair-shared-room', [ReservationsController::class, 'pairSharedRoom'])->name('reservations.pair-shared-room');
    Route::get('reservations/{reservation}', [ReservationsController::class, 'show'])->name('reservations.show');

    Route::get('customers', [CustomersController::class, 'index'])->name('customers.index');
    Route::get('customers/clients/trashed', [ClientController::class, 'trashed'])->name('customers.clients.trashed');
    Route::post('customers/clients/bulk', [ClientController::class, 'bulkAction'])->name('customers.clients.bulk');
    Route::post('customers/clients/{id}/restore', [ClientController::class, 'restore'])->name('customers.clients.restore')->whereNumber('id');
    Route::delete('customers/clients/{id}/force', [ClientController::class, 'forceDelete'])->name('customers.clients.force')->whereNumber('id');
    Route::get('customers/clients', [ClientController::class, 'index'])->name('customers.clients.index');
    Route::get('customers/clients/create', [ClientController::class, 'create'])->name('customers.clients.create');
    Route::post('customers/clients', [ClientController::class, 'store'])->name('customers.clients.store');
    Route::get('customers/clients/{client}', [ClientController::class, 'show'])->name('customers.clients.show');
    Route::get('customers/clients/{client}/edit', [ClientController::class, 'edit'])->name('customers.clients.edit');
    Route::match(['put', 'patch'], 'customers/clients/{client}', [ClientController::class, 'update'])->name('customers.clients.update');
    Route::delete('customers/clients/{client}', [ClientController::class, 'destroy'])->name('customers.clients.destroy');
    Route::get('customers/prospects', [CustomersController::class, 'page'])->name('customers.prospects')->defaults('submenu', 'prospects');
    Route::get('customers/voyageurs', [CustomersController::class, 'page'])->name('customers.voyageurs')->defaults('submenu', 'voyageurs');
    Route::get('customers/historique', [CustomersController::class, 'page'])->name('customers.historique')->defaults('submenu', 'historique');
    Route::get('customers/avis-clients', [CustomersController::class, 'page'])->name('customers.avis-clients')->defaults('submenu', 'avis-clients');
    Route::get('customers/fidelite', [CustomersController::class, 'page'])->name('customers.fidelite')->defaults('submenu', 'fidelite');

    Route::get('products', [ProductsController::class, 'index'])->name('products.index');
    Route::get('products/services', [ProductsController::class, 'page'])->name('products.services')->defaults('submenu', 'services');
    Route::get('products/options', [ProductsController::class, 'page'])->name('products.options')->defaults('submenu', 'options');
    Route::get('products/tarifs', [ProductsController::class, 'page'])->name('products.tarifs')->defaults('submenu', 'tarifs');
    Route::get('products/conditions', [ProductsController::class, 'page'])->name('products.conditions')->defaults('submenu', 'conditions');

    Route::get('circuits', [CircuitsController::class, 'index'])->name('circuits.index');
    Route::get('circuits/circuits', [CircuitsController::class, 'page'])->name('circuits.circuits')->defaults('submenu', 'circuits');
    Route::get('circuits/itineraires', [CircuitsController::class, 'page'])->name('circuits.itineraires')->defaults('submenu', 'itineraires');
    Route::get('circuits/departs-dates', [CircuitsController::class, 'page'])->name('circuits.departs-dates')->defaults('submenu', 'departs-dates');
    Route::get('circuits/options', [CircuitsController::class, 'page'])->name('circuits.options')->defaults('submenu', 'options');
    Route::get('circuits/politiques-conditions', [CircuitsController::class, 'page'])->name('circuits.politiques-conditions')->defaults('submenu', 'politiques-conditions');

    Route::get('circuits/activities', [ActivityController::class, 'index'])->name('circuits.activities.index');
    Route::get('circuits/activities/create', [ActivityController::class, 'create'])->name('circuits.activities.create');
    Route::post('circuits/activities', [ActivityController::class, 'store'])->name('circuits.activities.store');
    Route::get('circuits/activities/{activity}/edit', [ActivityController::class, 'edit'])->name('circuits.activities.edit');
    Route::match(['put', 'patch'], 'circuits/activities/{activity}', [ActivityController::class, 'update'])->name('circuits.activities.update');
    Route::delete('circuits/activities/{activity}', [ActivityController::class, 'destroy'])->name('circuits.activities.destroy');
    Route::get('circuits/activities/ajax/list', [ActivityController::class, 'ajaxList'])->name('circuits.activities.ajax.list');
    Route::get('circuits/activities/ajax/{activity}', [ActivityController::class, 'ajaxShow'])->name('circuits.activities.ajax.show');
    Route::post('circuits/activities/ajax/store', [ActivityController::class, 'ajaxStore'])->name('circuits.activities.ajax.store');
    Route::post('circuits/activities/ajax/{activity}/update', [ActivityController::class, 'ajaxUpdate'])->name('circuits.activities.ajax.update');
    Route::delete('circuits/activities/ajax/{activity}', [ActivityController::class, 'ajaxDestroy'])->name('circuits.activities.ajax.destroy');

    Route::get('circuits/airlines', [AirlineController::class, 'index'])->name('circuits.airlines.index');
    Route::get('circuits/airlines/create', [AirlineController::class, 'create'])->name('circuits.airlines.create');
    Route::post('circuits/airlines', [AirlineController::class, 'store'])->name('circuits.airlines.store');
    Route::get('circuits/airlines/{airline}/edit', [AirlineController::class, 'edit'])->name('circuits.airlines.edit');
    Route::match(['put', 'patch'], 'circuits/airlines/{airline}', [AirlineController::class, 'update'])->name('circuits.airlines.update');
    Route::delete('circuits/airlines/{airline}', [AirlineController::class, 'destroy'])->name('circuits.airlines.destroy');
    Route::get('circuits/airlines/ajax/list', [AirlineController::class, 'ajaxList'])->name('circuits.airlines.ajax.list');
    Route::post('circuits/airlines/ajax/store', [AirlineController::class, 'ajaxStore'])->name('circuits.airlines.ajax.store');
    Route::post('circuits/airlines/ajax/{airline}/update', [AirlineController::class, 'ajaxUpdate'])->name('circuits.airlines.ajax.update');
    Route::delete('circuits/airlines/ajax/{airline}', [AirlineController::class, 'ajaxDestroy'])->name('circuits.airlines.ajax.destroy');

    Route::get('circuits/tour-hotels', [TourHotelController::class, 'index'])->name('circuits.tour-hotels.index');
    Route::get('circuits/wp-hotels/{hotelId}/data', [TourHotelController::class, 'wpHotelData'])->name('circuits.wp-hotels.data')->whereNumber('hotelId');
    Route::get('circuits/tour-hotels/{tourId}/data', [TourHotelController::class, 'data'])->name('circuits.tour-hotels.data')->whereNumber('tourId');
    Route::get('circuits/tour-hotels/{tourId}', [TourHotelController::class, 'show'])->name('circuits.tour-hotels.show')->whereNumber('tourId');
    Route::get('circuits/tour-hotels/{tourId}/edit', [TourHotelController::class, 'edit'])->name('circuits.tour-hotels.edit')->whereNumber('tourId');
    Route::match(['put', 'patch'], 'circuits/tour-hotels/{tourId}', [TourHotelController::class, 'update'])->name('circuits.tour-hotels.update')->whereNumber('tourId');

    Route::get('circuits/tour-transfers', [TourTransferController::class, 'index'])->name('circuits.tour-transfers.index');
    Route::post('circuits/tour-transfers', [TourTransferController::class, 'store'])->name('circuits.tour-transfers.store');
    Route::get('circuits/tour-transfers/{tourId}/edit', [TourTransferController::class, 'edit'])->name('circuits.tour-transfers.edit')->whereNumber('tourId');
    Route::match(['put', 'patch'], 'circuits/tour-transfers/{tourId}', [TourTransferController::class, 'update'])->name('circuits.tour-transfers.update')->whereNumber('tourId');

    Route::get('circuits/voyages', [VoyageController::class, 'index'])->name('circuits.voyages.index');
    Route::get('circuits/voyages/create', [VoyageController::class, 'create'])->name('circuits.voyages.create');
    Route::post('circuits/voyages', [VoyageController::class, 'store'])->name('circuits.voyages.store');
    Route::get('circuits/voyages/{id}', [VoyageController::class, 'show'])->name('circuits.voyages.show')->whereNumber('id');
    Route::get('circuits/voyages/{id}/edit', [VoyageController::class, 'edit'])->name('circuits.voyages.edit')->whereNumber('id');
    Route::match(['put', 'patch'], 'circuits/voyages/{id}', [VoyageController::class, 'update'])->name('circuits.voyages.update')->whereNumber('id');
    Route::delete('circuits/voyages/{id}', [VoyageController::class, 'destroy'])->name('circuits.voyages.destroy')->whereNumber('id');
    Route::post('circuits/voyages/ensure-location', [VoyageController::class, 'ensureLocation'])->name('circuits.voyages.ensure-location');
    Route::post('circuits/voyages/{id}/hero-image', [HeroImageController::class, 'upload'])->name('circuits.voyages.hero-image.upload')->whereNumber('id');
    Route::post('circuits/voyages/{id}/hero-image/select', [HeroImageController::class, 'select'])->name('circuits.voyages.hero-image.select')->whereNumber('id');
    Route::post('circuits/voyages/{id}/hero-image/remove', [HeroImageController::class, 'remove'])->name('circuits.voyages.hero-image.remove')->whereNumber('id');
    Route::post('local-media/upload', [LocalMediaController::class, 'upload'])->name('local-media.upload');
    Route::get('wp-media/list', [WpMediaController::class, 'list'])->name('wp-media.list');
    Route::post('wp-media/upload', [WpMediaController::class, 'upload'])->name('wp-media.upload');
    Route::post('wp-media/select', [WpMediaController::class, 'select'])->name('wp-media.select');
    Route::post('wp-media/remove', [WpMediaController::class, 'remove'])->name('wp-media.remove');
    Route::get('wp-media/get/{id}', [WpMediaController::class, 'get'])->name('wp-media.get')->whereNumber('id');
    Route::get('wp-media/search', [WpMediaController::class, 'search'])->name('wp-media.search');
    Route::get('circuits/voyages/{id}/program', [ProgramApiController::class, 'show'])->name('circuits.voyages.program.show')->whereNumber('id');
    Route::post('circuits/voyages/{id}/program', [ProgramApiController::class, 'save'])->name('circuits.voyages.program.save')->whereNumber('id');
    Route::post('circuits/voyages/{id}/program/day', [VoyageController::class, 'addProgramDay'])->name('circuits.voyages.program.addDay')->whereNumber('id');
    Route::post('circuits/voyages/{id}/program/day/{dayId}', [VoyageController::class, 'deleteProgramDay'])->name('circuits.voyages.program.deleteDay')->whereNumber(['id', 'dayId']);
    Route::delete('circuits/voyages/{voyage}/images/{voyageImage}', [VoyageController::class, 'destroyImage'])->name('circuits.voyages.images.destroy');
    Route::post('circuits/voyages/{voyage}/programme', [TravelProgramDayController::class, 'store'])->name('circuits.voyages.programme.store');
    Route::match(['put', 'patch'], 'circuits/voyages/{voyage}/programme/{programDay}', [TravelProgramDayController::class, 'update'])->name('circuits.voyages.programme.update');
    Route::delete('circuits/voyages/{voyage}/programme/{programDay}', [TravelProgramDayController::class, 'destroy'])->name('circuits.voyages.programme.destroy');
    Route::post('circuits/voyages/{voyage}/departures', [DepartureController::class, 'store'])->name('circuits.voyages.departures.store');
    Route::match(['put', 'patch'], 'circuits/voyages/{voyage}/departures/{departure}', [DepartureController::class, 'update'])->name('circuits.voyages.departures.update');
    Route::delete('circuits/voyages/{voyage}/departures/{departure}', [DepartureController::class, 'destroy'])->name('circuits.voyages.departures.destroy');

    // Travel Day Items (Package Builder)
    Route::post('circuits/voyages/{voyage}/items', [TravelDayItemController::class, 'store'])->name('circuits.voyages.items.store');
    Route::get('circuits/voyages/{voyage}/items/{item}/edit', [TravelDayItemController::class, 'edit'])->name('circuits.voyages.items.edit');
    Route::match(['put', 'patch'], 'circuits/voyages/{voyage}/items/{item}', [TravelDayItemController::class, 'update'])->name('circuits.voyages.items.update');
    Route::delete('circuits/voyages/{voyage}/items/{item}', [TravelDayItemController::class, 'destroy'])->name('circuits.voyages.items.destroy');
    Route::post('circuits/voyages/{voyage}/items/reorder', [TravelDayItemController::class, 'reorder'])->name('circuits.voyages.items.reorder');

    Route::get('circuits/taxonomy-terms', [TaxonomyTermController::class, 'index'])->name('circuits.taxonomy-terms.index');
    Route::post('circuits/taxonomy-terms', [TaxonomyTermController::class, 'store'])->name('circuits.taxonomy-terms.store');
    Route::match(['put', 'patch'], 'circuits/taxonomy-terms/{termId}', [TaxonomyTermController::class, 'update'])->name('circuits.taxonomy-terms.update')->whereNumber('termId');
    Route::delete('circuits/taxonomy-terms/{termId}', [TaxonomyTermController::class, 'destroy'])->name('circuits.taxonomy-terms.destroy')->whereNumber('termId');

    Route::get('accommodations', [AccommodationsController::class, 'index'])->name('accommodations.index');
    Route::get('accommodations/hotels', [AccommodationsController::class, 'page'])->name('accommodations.hotels')->defaults('submenu', 'hotels');
    Route::get('accommodations/chambres', [AccommodationsController::class, 'page'])->name('accommodations.chambres')->defaults('submenu', 'chambres');
    Route::get('accommodations/tarifs-saisonniers', [AccommodationsController::class, 'page'])->name('accommodations.tarifs-saisonniers')->defaults('submenu', 'tarifs-saisonniers');
    Route::get('accommodations/disponibilites', [AccommodationsController::class, 'page'])->name('accommodations.disponibilites')->defaults('submenu', 'disponibilites');

    Route::get('accommodation-packages', [AccommodationPackageController::class, 'index'])->name('accommodation-packages.index');
    Route::get('accommodation-packages/create', [AccommodationPackageController::class, 'create'])->name('accommodation-packages.create');
    Route::post('accommodation-packages', [AccommodationPackageController::class, 'store'])->name('accommodation-packages.store');
    Route::get('accommodation-packages/{accommodationPackage}/edit', [AccommodationPackageController::class, 'edit'])->name('accommodation-packages.edit');
    Route::match(['put', 'patch'], 'accommodation-packages/{accommodationPackage}', [AccommodationPackageController::class, 'update'])->name('accommodation-packages.update');
    Route::delete('accommodation-packages/{accommodationPackage}', [AccommodationPackageController::class, 'destroy'])->name('accommodation-packages.destroy');

    Route::get('activity-offers', [ActivityOfferController::class, 'index'])->name('activity-offers.index');
    Route::get('activity-offers/create', [ActivityOfferController::class, 'create'])->name('activity-offers.create');
    Route::post('activity-offers', [ActivityOfferController::class, 'store'])->name('activity-offers.store');
    Route::get('activity-offers/{activityOffer}/edit', [ActivityOfferController::class, 'edit'])->name('activity-offers.edit');
    Route::match(['put', 'patch'], 'activity-offers/{activityOffer}', [ActivityOfferController::class, 'update'])->name('activity-offers.update');
    Route::delete('activity-offers/{activityOffer}', [ActivityOfferController::class, 'destroy'])->name('activity-offers.destroy');

    Route::resource('hotels', HotelBackofficeController::class)->names('hotels');

    Route::get('operations', [OperationsController::class, 'index'])->name('operations.index');
    Route::get('operations/planning', [OperationsController::class, 'page'])->name('operations.planning')->defaults('submenu', 'planning');
    Route::get('operations/guides-chauffeurs', [OperationsController::class, 'page'])->name('operations.guides-chauffeurs')->defaults('submenu', 'guides-chauffeurs');
    Route::get('operations/vehicules', [OperationsController::class, 'page'])->name('operations.vehicules')->defaults('submenu', 'vehicules');
    Route::get('operations/logistique', [OperationsController::class, 'page'])->name('operations.logistique')->defaults('submenu', 'logistique');

    Route::get('visa', [VisaController::class, 'index'])->name('visa.index');
    Route::get('visa/demandes-visa', [VisaController::class, 'page'])->name('visa.demandes-visa')->defaults('submenu', 'demandes-visa');
    Route::get('visa/statuts', [VisaController::class, 'page'])->name('visa.statuts')->defaults('submenu', 'statuts');
    Route::get('visa/documents', [VisaController::class, 'page'])->name('visa.documents')->defaults('submenu', 'documents');

    Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('finance/factures', [FinanceController::class, 'page'])->name('finance.factures')->defaults('submenu', 'factures');
    Route::get('finance/paiements', [FinanceController::class, 'page'])->name('finance.paiements')->defaults('submenu', 'paiements');
    Route::get('finance/depenses', [FinanceController::class, 'page'])->name('finance.depenses')->defaults('submenu', 'depenses');
    Route::get('finance/commissions', [FinanceController::class, 'page'])->name('finance.commissions')->defaults('submenu', 'commissions');
    Route::get('finance/rapports-financiers', [FinanceController::class, 'page'])->name('finance.rapports-financiers')->defaults('submenu', 'rapports-financiers');

    Route::get('partners', [PartnersController::class, 'index'])->name('partners.index');
    Route::get('partners/partenaires', [PartnersController::class, 'page'])->name('partners.partenaires')->defaults('submenu', 'partenaires');
    Route::get('partners/fournisseurs', [PartnersController::class, 'page'])->name('partners.fournisseurs')->defaults('submenu', 'fournisseurs');
    Route::get('partners/contrats', [PartnersController::class, 'page'])->name('partners.contrats')->defaults('submenu', 'contrats');

    Route::get('partner-accounts', [PartnerAccountController::class, 'index'])->name('partner-accounts.index');
    Route::get('partner-accounts/{partner}', [PartnerAccountController::class, 'show'])->name('partner-accounts.show');
    Route::post('partner-accounts/{partner}/validate', [PartnerAccountController::class, 'validatePartner'])->name('partner-accounts.validate');
    Route::post('partner-accounts/{partner}/reject', [PartnerAccountController::class, 'rejectPartner'])->name('partner-accounts.reject');
    Route::post('partner-accounts/{partner}/voyage-access', [PartnerAccountController::class, 'updateVoyageAccess'])->name('partner-accounts.voyage-access');
    Route::get('partner-commission-rules', [PartnerCommissionRuleController::class, 'index'])->name('partner-commission-rules.index');
    Route::get('partner-commission-rules/create', [PartnerCommissionRuleController::class, 'create'])->name('partner-commission-rules.create');
    Route::post('partner-commission-rules', [PartnerCommissionRuleController::class, 'store'])->name('partner-commission-rules.store');
    Route::get('partner-commission-rules/{partner_commission_rule}/edit', [PartnerCommissionRuleController::class, 'edit'])->name('partner-commission-rules.edit');
    Route::put('partner-commission-rules/{partner_commission_rule}', [PartnerCommissionRuleController::class, 'update'])->name('partner-commission-rules.update');
    Route::delete('partner-commission-rules/{partner_commission_rule}', [PartnerCommissionRuleController::class, 'destroy'])->name('partner-commission-rules.destroy');

    Route::get('reporting', [ReportingController::class, 'index'])->name('reporting.index');
    Route::get('reporting/rapports', [ReportingController::class, 'page'])->name('reporting.rapports')->defaults('submenu', 'rapports');
    Route::get('reporting/tableaux-bord', [ReportingController::class, 'page'])->name('reporting.tableaux-bord')->defaults('submenu', 'tableaux-bord');
    Route::get('reporting/exports', [ReportingController::class, 'page'])->name('reporting.exports')->defaults('submenu', 'exports');

    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('settings/utilisateurs', [UserAccessController::class, 'index'])->name('settings.utilisateurs');
    Route::get('settings/utilisateurs/create', [UserAccessController::class, 'create'])->name('settings.utilisateurs.create');
    Route::post('settings/utilisateurs', [UserAccessController::class, 'store'])->name('settings.utilisateurs.store');
    Route::get('settings/utilisateurs/{user}/edit', [UserAccessController::class, 'edit'])->name('settings.utilisateurs.edit');
    Route::match(['put', 'patch'], 'settings/utilisateurs/{user}', [UserAccessController::class, 'update'])->name('settings.utilisateurs.update');
    Route::post('settings/utilisateurs/{user}/toggle-active', [UserAccessController::class, 'toggleActive'])->name('settings.utilisateurs.toggle-active');
    Route::delete('settings/utilisateurs/{user}', [UserAccessController::class, 'destroy'])->name('settings.utilisateurs.destroy');

    Route::get('settings/roles-permissions', [RoleAccessController::class, 'index'])->name('settings.roles-permissions');
    Route::get('settings/roles-permissions/create', [RoleAccessController::class, 'create'])->name('settings.roles-permissions.create');
    Route::post('settings/roles-permissions', [RoleAccessController::class, 'store'])->name('settings.roles-permissions.store');
    Route::get('settings/roles-permissions/{role}/edit', [RoleAccessController::class, 'edit'])->name('settings.roles-permissions.edit');
    Route::match(['put', 'patch'], 'settings/roles-permissions/{role}', [RoleAccessController::class, 'update'])->name('settings.roles-permissions.update');
    Route::delete('settings/roles-permissions/{role}', [RoleAccessController::class, 'destroy'])->name('settings.roles-permissions.destroy');

    Route::get('settings/parametres-generaux', [SettingsController::class, 'page'])->name('settings.parametres-generaux')->defaults('submenu', 'parametres-generaux');
    Route::post('settings/parametres-generaux', [SettingsController::class, 'updateParametresGeneraux'])->name('settings.parametres-generaux.update');
    Route::get('settings/securite', [SettingsController::class, 'page'])->name('settings.securite')->defaults('submenu', 'securite');
    Route::get('settings/home-page', [HomePageSettingsController::class, 'edit'])->name('settings.home-page.edit');
    Route::post('settings/home-page', [HomePageSettingsController::class, 'update'])->name('settings.home-page.update');
    Route::get('settings/referentiels-metier', [BusinessReferenceController::class, 'index'])->name('settings.referentiels-metier');
    Route::post('settings/referentiels-metier/import-legacy', [BusinessReferenceController::class, 'importLegacy'])->name('settings.referentiels-metier.import-legacy');
    Route::get('settings/referentiels-metier/{groupKey}', [BusinessReferenceController::class, 'showGroup'])->name('settings.referentiels-metier.group');
    Route::post('settings/referentiels-metier/{groupKey}', [BusinessReferenceController::class, 'store'])->name('settings.referentiels-metier.store');
    Route::match(['put', 'patch'], 'settings/referentiels-metier/{groupKey}/{item}', [BusinessReferenceController::class, 'update'])->name('settings.referentiels-metier.update');
    Route::delete('settings/referentiels-metier/{groupKey}/{item}', [BusinessReferenceController::class, 'destroy'])->name('settings.referentiels-metier.destroy');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::match(['put', 'patch'], 'profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

    Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
    Route::get('branches/create', [BranchController::class, 'create'])->name('branches.create');
    Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
    Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
    Route::match(['put', 'patch'], 'branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
    Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');

    Route::get('messagerie', [MessagerieController::class, 'index'])->name('messagerie.index');
    Route::get('messagerie/channels', [MessagerieController::class, 'channels'])->name('messagerie.channels');
    Route::get('messagerie/channels/{channel}/messages', [MessagerieController::class, 'messages'])->name('messagerie.messages');
    Route::post('messagerie/channels/{channel}/messages', [MessagerieController::class, 'send'])->name('messagerie.send');
    Route::post('messagerie/channels', [MessagerieController::class, 'createChannel'])->name('messagerie.channels.create');

    // WordPress (TravelerWP) – tables cFdgeZ_*
    Route::prefix('wordpress')->name('wordpress.')->group(function () {
        Route::get('hotels', [HotelController::class, 'index'])->name('hotels.index');
        Route::get('hotels/create', [HotelController::class, 'create'])->name('hotels.create');
        Route::post('hotels', [HotelController::class, 'store'])->name('hotels.store');
        Route::get('hotels/{hotel}/edit', [HotelController::class, 'edit'])->name('hotels.edit')->whereNumber('hotel');
        Route::match(['put', 'patch'], 'hotels/{hotel}', [HotelController::class, 'update'])->name('hotels.update')->whereNumber('hotel');
        Route::delete('hotels/{hotel}', [HotelController::class, 'destroy'])->name('hotels.destroy')->whereNumber('hotel');

        Route::get('activities', [WordPressActivityController::class, 'index'])->name('activities.index');
        Route::get('activities/create', [WordPressActivityController::class, 'create'])->name('activities.create');
        Route::post('activities', [WordPressActivityController::class, 'store'])->name('activities.store');
        Route::get('activities/{activity}/edit', [WordPressActivityController::class, 'edit'])->name('activities.edit')->whereNumber('activity');
        Route::match(['put', 'patch'], 'activities/{activity}', [WordPressActivityController::class, 'update'])->name('activities.update')->whereNumber('activity');
        Route::delete('activities/{activity}', [WordPressActivityController::class, 'destroy'])->name('activities.destroy')->whereNumber('activity');

        Route::get('transfers', [WordPressTransferController::class, 'index'])->name('transfers.index');
        Route::get('transfers/create', [WordPressTransferController::class, 'create'])->name('transfers.create');
        Route::post('transfers', [WordPressTransferController::class, 'store'])->name('transfers.store');
        Route::get('transfers/{transfer}/edit', [WordPressTransferController::class, 'edit'])->name('transfers.edit')->whereNumber('transfer');
        Route::match(['put', 'patch'], 'transfers/{transfer}', [WordPressTransferController::class, 'update'])->name('transfers.update')->whereNumber('transfer');
        Route::delete('transfers/{transfer}', [WordPressTransferController::class, 'destroy'])->name('transfers.destroy')->whereNumber('transfer');

        // Tours (st_tours) - Direct CRUD dans DB WordPress
        Route::get('tours', [WpTourController::class, 'index'])->name('tours.index');
        Route::get('tours/create', [WpTourController::class, 'create'])->name('tours.create');
        Route::post('tours', [WpTourController::class, 'store'])->name('tours.store');
        Route::get('tours/{tour}/edit', [WpTourController::class, 'edit'])->name('tours.edit')->whereNumber('tour');
        Route::match(['put', 'patch'], 'tours/{tour}', [WpTourController::class, 'update'])->name('tours.update')->whereNumber('tour');
        Route::delete('tours/{tour}', [WpTourController::class, 'destroy'])->name('tours.destroy')->whereNumber('tour');
    });
});

Route::middleware('auth')->prefix('demo')->name('demo.')->group(function () {
    Route::get('/', [DemoController::class, 'index'])->name('index');
    Route::get('{any}', [DemoController::class, 'page'])->name('page');
});
