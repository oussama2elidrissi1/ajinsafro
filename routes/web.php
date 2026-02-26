<?php

use App\Http\Controllers\Admin\AccommodationsController;
use App\Http\Controllers\Admin\CircuitsController;
use App\Http\Controllers\Admin\CustomersController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\OperationsController;
use App\Http\Controllers\Admin\PartnersController;
use App\Http\Controllers\Admin\ProductsController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportingController;
use App\Http\Controllers\Admin\ReservationsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\RoleAccessController;
use App\Http\Controllers\Admin\UserAccessController;
use App\Http\Controllers\Admin\DepartureController;
use App\Http\Controllers\Admin\TravelDayItemController;
use App\Http\Controllers\Admin\TravelProgramDayController;
use App\Http\Controllers\Admin\VisaController;
use App\Http\Controllers\Admin\VoyageController;
use App\Http\Controllers\Admin\HomePageSettingsController;
use App\Http\Controllers\Admin\HeroImageController;
use App\Http\Controllers\Admin\WpMediaController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AirlineController;
use App\Http\Controllers\Admin\TourHotelController;
use App\Http\Controllers\Admin\TourTransferController;
use App\Http\Controllers\Admin\ProgramApiController;
use App\Http\Controllers\Admin\WordPress\HotelController;
use App\Http\Controllers\Admin\WpTourController;
use App\Http\Controllers\Auth\LockScreenController;
use App\Http\Controllers\Booking\CheckoutController;
use App\Http\Controllers\Booking\StartBookingController;
use App\Http\Controllers\Front\HomeController as FrontHomeController;
use App\Http\Controllers\Front\SearchController as FrontSearchController;
use App\Http\Controllers\Front\VoyageController as FrontVoyageController;
use App\Http\Controllers\Internal\SyncInboundController;
use App\Http\Controllers\DemoController;
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
Route::get('/search', [FrontSearchController::class, 'index'])->name('front.search');
Route::get('/voyages', [FrontVoyageController::class, 'index'])->name('front.voyages.index');
Route::get('/voyages/{slug}', [FrontVoyageController::class, 'show'])->name('front.voyages.show');

Route::get('/booking/start', StartBookingController::class)->name('booking.start');
Route::get('/booking/checkout/{token}', [CheckoutController::class, 'show'])->name('booking.checkout');
Route::post('/booking/checkout/{token}', [CheckoutController::class, 'process'])->name('booking.checkout.process');

Route::post('/internal/sync/wp-to-laravel', [SyncInboundController::class, 'wpToLaravel'])
    ->middleware('sync.token');

Route::middleware('auth')->group(function () {
    Route::get('lock-screen', [LockScreenController::class, 'show'])->name('lock-screen');
    Route::post('lock-screen', [LockScreenController::class, 'unlock'])->name('lock-screen.unlock');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('lock-screen/activate', [LockScreenController::class, 'lock'])->name('lock-screen.activate');
});

Route::middleware(['auth', 'admin', 'ensure.not.locked', 'route.permission'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/vue-globale', [DashboardController::class, 'page'])->name('dashboard.vue-globale')->defaults('submenu', 'vue-globale');
    Route::get('dashboard/statistiques', [DashboardController::class, 'page'])->name('dashboard.statistiques')->defaults('submenu', 'statistiques');
    Route::get('dashboard/alertes', [DashboardController::class, 'page'])->name('dashboard.alertes')->defaults('submenu', 'alertes');

    Route::get('reservations', [ReservationsController::class, 'index'])->name('reservations.index');
    Route::get('reservations/toutes', [ReservationsController::class, 'page'])->name('reservations.toutes')->defaults('submenu', 'toutes');
    Route::get('reservations/en-attente', [ReservationsController::class, 'page'])->name('reservations.en-attente')->defaults('submenu', 'en-attente');
    Route::get('reservations/confirmees', [ReservationsController::class, 'page'])->name('reservations.confirmees')->defaults('submenu', 'confirmees');
    Route::get('reservations/annulees', [ReservationsController::class, 'page'])->name('reservations.annulees')->defaults('submenu', 'annulees');
    Route::get('reservations/calendrier', [ReservationsController::class, 'page'])->name('reservations.calendrier')->defaults('submenu', 'calendrier');
    Route::get('reservations/paiements', [ReservationsController::class, 'page'])->name('reservations.paiements')->defaults('submenu', 'paiements');

    Route::get('customers', [CustomersController::class, 'index'])->name('customers.index');
    Route::get('customers/clients', [CustomersController::class, 'page'])->name('customers.clients')->defaults('submenu', 'clients');
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

    Route::get('circuits/airlines', [AirlineController::class, 'index'])->name('circuits.airlines.index');
    Route::get('circuits/airlines/create', [AirlineController::class, 'create'])->name('circuits.airlines.create');
    Route::post('circuits/airlines', [AirlineController::class, 'store'])->name('circuits.airlines.store');
    Route::get('circuits/airlines/{airline}/edit', [AirlineController::class, 'edit'])->name('circuits.airlines.edit');
    Route::match(['put', 'patch'], 'circuits/airlines/{airline}', [AirlineController::class, 'update'])->name('circuits.airlines.update');
    Route::delete('circuits/airlines/{airline}', [AirlineController::class, 'destroy'])->name('circuits.airlines.destroy');

    Route::get('circuits/tour-hotels', [TourHotelController::class, 'index'])->name('circuits.tour-hotels.index');
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

    Route::get('accommodations', [AccommodationsController::class, 'index'])->name('accommodations.index');
    Route::get('accommodations/hotels', [AccommodationsController::class, 'page'])->name('accommodations.hotels')->defaults('submenu', 'hotels');
    Route::get('accommodations/chambres', [AccommodationsController::class, 'page'])->name('accommodations.chambres')->defaults('submenu', 'chambres');
    Route::get('accommodations/tarifs-saisonniers', [AccommodationsController::class, 'page'])->name('accommodations.tarifs-saisonniers')->defaults('submenu', 'tarifs-saisonniers');
    Route::get('accommodations/disponibilites', [AccommodationsController::class, 'page'])->name('accommodations.disponibilites')->defaults('submenu', 'disponibilites');

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

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::match(['put', 'patch'], 'profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

    // WordPress (TravelerWP) – tables cFdgeZ_*
    Route::prefix('wordpress')->name('wordpress.')->group(function () {
        Route::get('hotels', [HotelController::class, 'index'])->name('hotels.index');
        Route::get('hotels/create', [HotelController::class, 'create'])->name('hotels.create');
        Route::post('hotels', [HotelController::class, 'store'])->name('hotels.store');
        Route::get('hotels/{hotel}/edit', [HotelController::class, 'edit'])->name('hotels.edit')->whereNumber('hotel');
        Route::match(['put', 'patch'], 'hotels/{hotel}', [HotelController::class, 'update'])->name('hotels.update')->whereNumber('hotel');
        Route::delete('hotels/{hotel}', [HotelController::class, 'destroy'])->name('hotels.destroy')->whereNumber('hotel');
        
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
