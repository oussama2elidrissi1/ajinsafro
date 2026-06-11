<?php

use App\Http\Controllers\Admin\AccommodationPackageController;
use App\Http\Controllers\Admin\ActivityOfferController;
use App\Http\Controllers\Admin\AccommodationsController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AgencyAccountController;
use App\Http\Controllers\Admin\AgencyController;
use App\Http\Controllers\Admin\AgencyEmployeeController;
use App\Http\Controllers\Admin\AirlineController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BusinessReferenceController;
use App\Http\Controllers\Admin\CircuitsController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CustomersController;
use App\Http\Controllers\Admin\CustomReservationRequestController;
use App\Http\Controllers\Admin\CustomRequestCommentController;
use App\Http\Controllers\Admin\CustomRequestController;
use App\Http\Controllers\Admin\CustomRequestDocumentController;
use App\Http\Controllers\Admin\CustomRequestQuoteController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartureController;
use App\Http\Controllers\Admin\AgentCommissionPortalController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\Finance\AgentCommissionController as FinanceAgentCommissionController;
use App\Http\Controllers\Admin\Finance\ChargeTypeController as FinanceChargeTypeController;
use App\Http\Controllers\Admin\Finance\DepartureFinanceController;
use App\Http\Controllers\Admin\GroupDeals\GroupDealController;
use App\Http\Controllers\Admin\GroupDeals\OfferController as GroupDealOfferController;
use App\Http\Controllers\Admin\HajjOmraBookingRequestController;
use App\Http\Controllers\Admin\HajjOmraPackageController;
use App\Http\Controllers\Admin\EconomicOfferController;
use App\Http\Controllers\Admin\EconomicOfferRequestController;
use App\Http\Controllers\Admin\HeroImageController;
use App\Http\Controllers\Admin\HomePageSettingsController;
use App\Http\Controllers\Admin\OperationsController;
use App\Http\Controllers\Admin\PartnerAccountController;
use App\Http\Controllers\Admin\PartnerCommissionRuleController;
use App\Http\Controllers\Admin\PartnersController;
use App\Http\Controllers\Admin\ProductsController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProgramApiController;
use App\Http\Controllers\Admin\ReservationDossierController;
use App\Http\Controllers\Admin\ReportingController;
use App\Http\Controllers\Admin\ReservationsController;
use App\Http\Controllers\Admin\ReservationWorkspaceController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\RoleAccessController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TaxonomyTermController;
use App\Http\Controllers\Admin\TailorMadeRequestController;
use App\Http\Controllers\Admin\TourHotelController;
use App\Http\Controllers\Admin\TourTransferController;
use App\Http\Controllers\Admin\LaravelVoyageThemeController;
use App\Http\Controllers\Admin\LocalMediaController;
use App\Http\Controllers\Admin\TravelDayItemController;
use App\Http\Controllers\Admin\TravelProgramDayController;
use App\Http\Controllers\Admin\UserAccessController;
use App\Http\Controllers\Admin\VisaController;
use App\Http\Controllers\Admin\VoyageController;
use App\Http\Controllers\Admin\VoyageDepartureManageController;
use App\Http\Controllers\Admin\VoyageReservationDataController;
use App\Http\Controllers\Admin\WordPress\HotelController;
use App\Http\Controllers\Admin\WpMediaController;
use App\Http\Controllers\Admin\WpTourController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Client\ClientProfileController;
use App\Http\Controllers\Client\ClientReservationsController;
use App\Http\Controllers\Agent\CatalogueController as AgentCatalogueController;
use App\Http\Controllers\Agent\CustomReservationController as AgentCustomReservationController;
use App\Http\Controllers\Agent\DashboardController as AgentDashboardController;
use App\Http\Controllers\Agent\ReservationController as AgentReservationController;
use App\Http\Controllers\Auth\LockScreenController;
use App\Http\Controllers\Front\GroupDealsController as FrontGroupDealsController;
use App\Http\Controllers\Front\VoyageController as FrontVoyageController;
use App\Http\Controllers\Admin\MenuHubController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\MessagerieController as AgentMessagerieController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal Back-office Routes
|--------------------------------------------------------------------------
|
| Domain: booking.ajinsafro.net (or ADMIN_DOMAIN when set).
| Contains Auth + all /admin pages only.
|
*/

Auth::routes();

// Front pages also served on booking domain (to avoid 404 on /voyages/{slug}).
// Intentionally no route names here to avoid collisions with routes/public.php.
if (is_string(config('app.admin_domain')) && config('app.admin_domain') !== '') {
    Route::get('/voyages', [FrontVoyageController::class, 'index']);
    Route::get('/voyages/{slug}', [FrontVoyageController::class, 'show']);
    Route::get('/group-deals', [FrontGroupDealsController::class, 'index']);
    Route::get('/group-deals/{slug}', [FrontGroupDealsController::class, 'show']);
    Route::post('/group-deals/{slug}/participate', [FrontGroupDealsController::class, 'participate']);
}

// Public entrypoint from WordPress UI (ajinsafro.net/login form)
Route::post('auth/public-login', [\App\Http\Controllers\Auth\PublicLoginController::class, 'store'])
    ->middleware('guest')
    ->name('auth.public-login');
Route::get('auth/public-login', function () {
    $adminUrl = rtrim((string) config('app.admin_url', config('app.url')), '/');

    return redirect()->away($adminUrl.'/login');
})->name('auth.public-login.get');

// GET /logout: session close + redirect to public website
Route::get('logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->away((string) config('app.public_url', 'https://ajinsafro.net'));
})->name('logout.get');

Route::middleware('auth')->group(function () {
    Route::get('lock-screen', [LockScreenController::class, 'show'])->name('lock-screen');
    Route::post('lock-screen', [LockScreenController::class, 'unlock'])->name('lock-screen.unlock');
});

Route::middleware(['auth', 'client'])->prefix('client')->name('client.')->group(function () {
    Route::get('dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
    Route::get('reservations', [ClientReservationsController::class, 'index'])->name('reservations.index');
    Route::get('reservations/{reservation}', [ClientReservationsController::class, 'show'])->name('reservations.show')->whereNumber('reservation');
    Route::get('profile', [ClientProfileController::class, 'edit'])->name('profile.edit');
    Route::post('profile', [ClientProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('lock-screen/activate', [LockScreenController::class, 'lock'])->name('lock-screen.activate');
});

// Legacy partner URLs under /partner/... are redirected to the dedicated subdomain portal.
Route::prefix('partner')->group(function () {
    Route::get('{any?}', function (?string $any = null) {
        $partnerUrl = rtrim((string) config('app.partner_url', 'https://partenaire.ajinsafro.net'), '/');
        $path = $any ? '/'.ltrim($any, '/') : '/dashboard';

        return redirect()->away($partnerUrl.$path);
    })->where('any', '.*');
});

Route::middleware(['auth', 'admin', 'ensure.not.locked', 'route.permission'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/vue-globale', [DashboardController::class, 'page'])->name('dashboard.vue-globale')->defaults('submenu', 'vue-globale');
        Route::get('dashboard/statistiques', [DashboardController::class, 'page'])->name('dashboard.statistiques')->defaults('submenu', 'statistiques');
        Route::get('dashboard/alertes', [DashboardController::class, 'page'])->name('dashboard.alertes')->defaults('submenu', 'alertes');
        Route::get('dashboard/v2', [DashboardController::class, 'v2'])->name('dashboard.v2');
        Route::get('dashboard/v3', [DashboardController::class, 'v3'])->name('dashboard.v3');
        Route::get('dashboard/v4', [DashboardController::class, 'v4'])->name('dashboard.v4');
        Route::get('dashboard/v5', [DashboardController::class, 'v5'])->name('dashboard.v5');
        Route::get('dashboard/v6', [DashboardController::class, 'v6'])->name('dashboard.v6');

        Route::get('reservations', [ReservationsController::class, 'index'])->name('reservations.index');
        Route::get('reservations/agents', function (\Illuminate\Http\Request $request) {
            $query = $request->query();
            $query['scope'] = 'agents';
            unset($query['channel']);

            return redirect()->route('admin.reservation-dossiers.index', $query);
        })->name('reservations.agents');
        Route::get('reservation-dossiers', [ReservationDossierController::class, 'index'])->name('reservation-dossiers.index');
        Route::get('reservation-dossiers/{reservationDossier}', [ReservationDossierController::class, 'show'])->name('reservation-dossiers.show');
        Route::delete('reservation-dossiers/{reservation}', [ReservationDossierController::class, 'destroy'])->name('reservation-dossiers.destroy');
        Route::get('reservations/clients', function (\Illuminate\Http\Request $request) {
            $query = $request->query();
            $query['channel'] = 'client';
            return redirect()->route('admin.reservations.index', $query);
        })->name('reservations.clients');
        Route::get('reservations/partners', function (\Illuminate\Http\Request $request) {
            $query = $request->query();
            $query['scope'] = 'partners';
            unset($query['channel']);

            return redirect()->route('admin.reservation-dossiers.index', $query);
        })->name('reservations.partners');
        Route::get('reservations/toutes', [ReservationsController::class, 'page'])->name('reservations.toutes')->defaults('submenu', 'toutes');
        Route::get('reservations/en-attente', [ReservationsController::class, 'page'])->name('reservations.en-attente')->defaults('submenu', 'en-attente');
        Route::get('reservations/confirmees', [ReservationsController::class, 'page'])->name('reservations.confirmees')->defaults('submenu', 'confirmees');
        Route::get('reservations/annulees', [ReservationsController::class, 'page'])->name('reservations.annulees')->defaults('submenu', 'annulees');

        Route::get('vente/catalogue', [ReservationWorkspaceController::class, 'index'])->name('vente.catalogue');
        Route::get('reservations/workspace', [ReservationWorkspaceController::class, 'index'])->name('reservations.workspace');
        Route::post('reservations/workspace', [ReservationWorkspaceController::class, 'store'])->name('reservations.workspace.store');
        Route::get('reservations/workspace/prestation/participants', [ReservationWorkspaceController::class, 'prestationParticipants'])->name('reservations.workspace.prestation.participants');
        Route::get('reservations/workspace/prestation/pdf', [ReservationWorkspaceController::class, 'prestationPdf'])->name('reservations.workspace.prestation.pdf');
        Route::get('reservations/workspace/reservation/{reservation}/pdf', [ReservationWorkspaceController::class, 'reservationPdf'])->name('reservations.workspace.reservation.pdf');
        Route::get('reservations/custom-requests', [CustomReservationRequestController::class, 'index'])->name('reservations.custom-requests.index');
        Route::get('reservations/custom-requests/create', [CustomReservationRequestController::class, 'create'])->name('reservations.custom-requests.create');
        Route::post('reservations/custom-requests', [CustomReservationRequestController::class, 'store'])->name('reservations.custom-requests.store');
        Route::get('reservations/custom-requests/{customRequest}', [CustomReservationRequestController::class, 'show'])->name('reservations.custom-requests.show')->whereNumber('customRequest');
        Route::get('reservations/custom-requests/{customRequest}/edit', [CustomReservationRequestController::class, 'edit'])->name('reservations.custom-requests.edit')->whereNumber('customRequest');
        Route::put('reservations/custom-requests/{customRequest}', [CustomReservationRequestController::class, 'update'])->name('reservations.custom-requests.update')->whereNumber('customRequest');
        Route::patch('reservations/custom-requests/{customRequest}/status', [CustomReservationRequestController::class, 'updateStatus'])->name('reservations.custom-requests.status')->whereNumber('customRequest');
        Route::post('reservations/custom-requests/{customRequest}/convert-to-reservation', [CustomReservationRequestController::class, 'convertToReservation'])->name('reservations.custom-requests.convert-to-reservation')->whereNumber('customRequest');

        Route::get('assignments', [AssignmentController::class, 'index'])->name('assignments.index');
        Route::post('assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::patch('assignments/{reservation}', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('assignments/{reservation}', [AssignmentController::class, 'remove'])->name('assignments.remove');
        Route::post('assignments/bulk', [AssignmentController::class, 'bulk'])->name('assignments.bulk');

        Route::get('reservations/calendrier', [ReservationsController::class, 'calendar'])->name('reservations.calendrier');
        Route::get('reservations/calendrier/events', [ReservationsController::class, 'calendarEvents'])->name('reservations.calendrier.events');
        Route::get('reservations/calendrier/event-details', [ReservationsController::class, 'calendarEventDetails'])->name('reservations.calendrier.event-details');
        Route::get('reservations/calendrier/reservation-details', [ReservationsController::class, 'calendarReservationDetails'])->name('reservations.calendrier.reservation-details');
        Route::get('reservations/paiements', [ReservationsController::class, 'page'])->name('reservations.paiements')->defaults('submenu', 'paiements');

        Route::get('reservations/messages', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages');
        Route::get('reservations/messages/create', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.create');
        Route::post('reservations/messages', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.store');
        Route::get('reservations/messages/{message}', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.show')->whereNumber('message');
        Route::post('reservations/messages/{message}/star', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.star')->whereNumber('message');
        Route::post('reservations/messages/{message}/trash', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.trash')->whereNumber('message');
        Route::post('reservations/messages/{message}/label', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.label')->whereNumber('message');
        Route::post('reservations/messages/{message}/important', fn () => redirect()->route('admin.messagerie.index'))->name('reservations.messages.important')->whereNumber('message');

        Route::get('demande-a-la-carte', [TailorMadeRequestController::class, 'index'])->name('tailor-made-requests.index');
        Route::get('demande-a-la-carte/{tailorMadeRequest}', [TailorMadeRequestController::class, 'show'])->name('tailor-made-requests.show')->whereNumber('tailorMadeRequest');
        Route::patch('demande-a-la-carte/{tailorMadeRequest}/status', [TailorMadeRequestController::class, 'updateStatus'])->name('tailor-made-requests.status')->whereNumber('tailorMadeRequest');
        Route::delete('demande-a-la-carte/{tailorMadeRequest}', [TailorMadeRequestController::class, 'destroy'])->name('tailor-made-requests.destroy')->whereNumber('tailorMadeRequest');

        Route::prefix('custom-requests')->name('custom-requests.')->group(function () {
            Route::get('/', [CustomRequestController::class, 'index'])->name('index');
            Route::get('create', [CustomRequestController::class, 'create'])->name('create');
            Route::post('/', [CustomRequestController::class, 'store'])->name('store');
            Route::get('{customRequest}', [CustomRequestController::class, 'show'])->name('show')->whereNumber('customRequest');
            Route::get('{customRequest}/edit', [CustomRequestController::class, 'edit'])->name('edit')->whereNumber('customRequest');
            Route::put('{customRequest}', [CustomRequestController::class, 'update'])->name('update')->whereNumber('customRequest');
            Route::delete('{customRequest}', [CustomRequestController::class, 'destroy'])->name('destroy')->whereNumber('customRequest');
            Route::post('{customRequest}/submit', [CustomRequestController::class, 'submit'])->name('submit')->whereNumber('customRequest');
            Route::post('{customRequest}/assign', [CustomRequestController::class, 'assign'])->name('assign')->whereNumber('customRequest');
            Route::post('{customRequest}/take', [CustomRequestController::class, 'take'])->name('take')->whereNumber('customRequest');
            Route::get('{customRequest}/quote', [CustomRequestQuoteController::class, 'quote'])->name('quote')->whereNumber('customRequest');
            Route::post('{customRequest}/quote', [CustomRequestQuoteController::class, 'store'])->name('quote.store')->whereNumber('customRequest');
            Route::put('{customRequest}/quote/{quote}', [CustomRequestQuoteController::class, 'update'])->name('quote.update')->whereNumber(['customRequest', 'quote']);
            Route::post('{customRequest}/quote/{quote}/prepare', [CustomRequestQuoteController::class, 'prepare'])->name('quote.prepare')->whereNumber(['customRequest', 'quote']);
            Route::post('{customRequest}/quote/{quote}/send', [CustomRequestQuoteController::class, 'send'])->name('quote.send')->whereNumber(['customRequest', 'quote']);
            Route::get('{customRequest}/quote/{quote}/download', [CustomRequestQuoteController::class, 'download'])->name('quote.download')->whereNumber(['customRequest', 'quote']);
            Route::post('{customRequest}/request-modification', [CustomRequestController::class, 'requestModification'])->name('request-modification')->whereNumber('customRequest');
            Route::post('{customRequest}/confirm', [CustomRequestController::class, 'confirm'])->name('confirm')->whereNumber('customRequest');
            Route::post('{customRequest}/cancel', [CustomRequestController::class, 'cancel'])->name('cancel')->whereNumber('customRequest');
            Route::post('{customRequest}/documents', [CustomRequestDocumentController::class, 'store'])->name('documents.store')->whereNumber('customRequest');
            Route::delete('{customRequest}/documents/{document}', [CustomRequestDocumentController::class, 'destroy'])->name('documents.destroy')->whereNumber(['customRequest', 'document']);
            Route::post('{customRequest}/comments', [CustomRequestCommentController::class, 'store'])->name('comments.store')->whereNumber('customRequest');
        });
        Route::get('reservations/create', [ReservationsController::class, 'create'])->name('reservations.create');
        Route::get('reservations/create-v2', [ReservationsController::class, 'createV2'])->name('reservations.create-v2');
        Route::get('reservations/create-classic', function () {
            return redirect()->route('admin.reservations.create');
        })->name('reservations.create-classic');
        Route::get('reservations/hotels-rooms', [ReservationsController::class, 'hotelsRooms'])->name('reservations.hotels-rooms');
        Route::get('reservations/voyage-departures', [ReservationsController::class, 'voyageDepartures'])->name('reservations.voyage-departures');
        Route::get('reservations/extras', [ReservationsController::class, 'extras'])->name('reservations.extras');
        Route::get('reservations/departure-hotels-rooms', [ReservationsController::class, 'departureHotelsRooms'])->name('reservations.departure-hotels-rooms');
        Route::post('reservations/pricing-preview', [ReservationsController::class, 'pricingPreview'])->name('reservations.pricing-preview');
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
        Route::get('reservations/{reservation}/pairing-candidates', [ReservationsController::class, 'pairingCandidates'])->name('reservations.pairing-candidates');
        Route::post('reservations/{reservation}/pair-shared-room', [ReservationsController::class, 'pairSharedRoom'])->name('reservations.pair-shared-room');

        Route::get('reservations/{reservation}', [ReservationsController::class, 'show'])->name('reservations.show');

        Route::get('customers', [CustomersController::class, 'index'])->name('customers.index');
        Route::get('customers/clients/trashed', [ClientController::class, 'trashed'])->name('customers.clients.trashed');
        Route::post('customers/clients/bulk', [ClientController::class, 'bulkAction'])->name('customers.clients.bulk');
        Route::post('customers/clients/{id}/restore', [ClientController::class, 'restore'])->name('customers.clients.restore')->whereNumber('id');
        Route::delete('customers/clients/{id}/force', [ClientController::class, 'forceDelete'])->name('customers.clients.force')->whereNumber('id');
        Route::get('customers/clients', [ClientController::class, 'index'])->name('customers.clients.index');
        Route::get('customers/clients/create', [ClientController::class, 'create'])->name('customers.clients.create');
        Route::get('customers/clients/search', [ClientController::class, 'search'])->name('customers.clients.search');
        Route::post('customers/clients/quick-store', [ClientController::class, 'quickStore'])->name('customers.clients.quick-store');
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
        Route::get('menu-hubs/billetterie', [MenuHubController::class, 'show'])->name('menu-hubs.billetterie')->defaults('page', 'billetterie');
        Route::get('menu-hubs/hebergement', [MenuHubController::class, 'show'])->name('menu-hubs.hebergement')->defaults('page', 'hebergement');
        Route::get('menu-hubs/hajj-omra', [MenuHubController::class, 'show'])->name('menu-hubs.hajj-omra')->defaults('page', 'hajj-omra');
        Route::get('menu-hubs/low-cost', [MenuHubController::class, 'show'])->name('menu-hubs.low-cost')->defaults('page', 'low-cost');
        Route::get('menu-hubs/activites', [MenuHubController::class, 'show'])->name('menu-hubs.activites')->defaults('page', 'activites');
        Route::get('menu-hubs/transfers', [MenuHubController::class, 'show'])->name('menu-hubs.transfers')->defaults('page', 'transfers');
        Route::get('menu-hubs/visa', [MenuHubController::class, 'show'])->name('menu-hubs.visa')->defaults('page', 'visa');
        Route::get('menu-hubs/rh', [MenuHubController::class, 'show'])->name('menu-hubs.rh')->defaults('page', 'rh');

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
        Route::get('circuits/tour-hotels/{tourId}/edit', [TourHotelController::class, 'edit'])->name('circuits.tour-hotels.edit')->whereNumber('tourId');
        Route::match(['put', 'patch'], 'circuits/tour-hotels/{tourId}', [TourHotelController::class, 'update'])->name('circuits.tour-hotels.update')->whereNumber('tourId');

        Route::get('circuits/tour-transfers', [TourTransferController::class, 'index'])->name('circuits.tour-transfers.index');
        Route::post('circuits/tour-transfers', [TourTransferController::class, 'store'])->name('circuits.tour-transfers.store');
        Route::get('circuits/tour-transfers/{tourId}/edit', [TourTransferController::class, 'edit'])->name('circuits.tour-transfers.edit')->whereNumber('tourId');
        Route::match(['put', 'patch'], 'circuits/tour-transfers/{tourId}', [TourTransferController::class, 'update'])->name('circuits.tour-transfers.update')->whereNumber('tourId');

        Route::get('circuits/voyages', [VoyageController::class, 'index'])->name('circuits.voyages.index');
        Route::get('circuits/voyages/create', [VoyageController::class, 'create'])->name('circuits.voyages.create');
        Route::get('circuits/voyages/create-v2', [VoyageController::class, 'createV2'])->name('circuits.voyages.create-v2');
        Route::post('circuits/voyages/v2/steps/{step}/save', [VoyageController::class, 'saveStepV2'])->name('circuits.voyages.v2.steps.save.create');
        Route::get('circuits/voyages/v2/steps/{step}/save', static function (string $step) {
            return redirect()->route('admin.circuits.voyages.create-v2')->with('error', 'Utilisez le formulaire pour enregistrer.');
        });
        Route::post('circuits/voyages', [VoyageController::class, 'store'])->name('circuits.voyages.store');
        Route::get('circuits/voyages/{id}', [VoyageController::class, 'show'])->name('circuits.voyages.show')->whereNumber('id');
        Route::get('circuits/voyages/{id}/edit', [VoyageController::class, 'edit'])->name('circuits.voyages.edit')->whereNumber('id');
        Route::get('circuits/voyages/{id}/edit-v2', [VoyageController::class, 'editV2'])->name('circuits.voyages.edit-v2')->whereNumber('id');
        Route::post('circuits/voyages/{id}/v2/steps/{step}/save', [VoyageController::class, 'saveStepV2'])->name('circuits.voyages.v2.steps.save')->whereNumber('id');
        Route::get('circuits/voyages/{id}/v2/steps/{step}/save', static function (string $id, string $step) {
            return redirect()->route('admin.circuits.voyages.edit-v2', (int) $id)->with('error', 'Utilisez le formulaire pour enregistrer.');
        })->whereNumber('id');
        Route::get('circuits/voyages/{voyage}/reservation-data', VoyageReservationDataController::class)->name('circuits.voyages.reservation-data');
        Route::match(['put', 'patch'], 'circuits/voyages/{id}', [VoyageController::class, 'update'])->name('circuits.voyages.update')->whereNumber('id');
        Route::delete('circuits/voyages/{id}', [VoyageController::class, 'destroy'])->name('circuits.voyages.destroy')->whereNumber('id');
        Route::post('circuits/voyages/ensure-location', [VoyageController::class, 'ensureLocation'])->name('circuits.voyages.ensure-location');
        Route::post('circuits/voyages/{id}/hero-image', [HeroImageController::class, 'upload'])->name('circuits.voyages.hero-image.upload')->whereNumber('id');
        Route::post('circuits/voyages/{id}/hero-image/select', [HeroImageController::class, 'select'])->name('circuits.voyages.hero-image.select')->whereNumber('id');
        Route::post('circuits/voyages/{id}/hero-image/remove', [HeroImageController::class, 'remove'])->name('circuits.voyages.hero-image.remove')->whereNumber('id');

        Route::get('wp-media/list', [WpMediaController::class, 'list'])->name('wp-media.list');
        Route::post('wp-media/upload', [WpMediaController::class, 'upload'])->name('wp-media.upload');
        Route::post('wp-media/select', [WpMediaController::class, 'select'])->name('wp-media.select');
        Route::post('wp-media/remove', [WpMediaController::class, 'remove'])->name('wp-media.remove');
        Route::get('wp-media/get/{id}', [WpMediaController::class, 'get'])->name('wp-media.get')->whereNumber('id');
        Route::get('wp-media/search', [WpMediaController::class, 'search'])->name('wp-media.search');

        // Local media upload for V2 sections (no WordPress attachment involved).
        Route::post('local-media/upload', [LocalMediaController::class, 'upload'])->name('local-media.upload');

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

        Route::post('circuits/voyages/{voyage}/room-availability/sync-departures', [VoyageDepartureManageController::class, 'syncDepartures'])->name('circuits.voyages.sync-departures');
        Route::get('circuits/voyages/{voyage}/room-availability/departures', [VoyageDepartureManageController::class, 'modalDeparturesJson'])->name('circuits.voyages.room-availability.departures');
        Route::get('circuits/voyages/{voyage}/room-availability/departures/{departure}/panel', [VoyageDepartureManageController::class, 'modalDeparturePanel'])->name('circuits.voyages.room-availability.departure-panel');

        Route::get('circuits/voyages/{voyage}/departures/{departure}', [VoyageDepartureManageController::class, 'show'])->name('circuits.voyages.departures.show');
        Route::put('circuits/voyages/{voyage}/departures/{departure}/settings', [VoyageDepartureManageController::class, 'updateSettings'])->name('circuits.voyages.departures.settings.update');
        Route::post('circuits/voyages/{voyage}/departures/{departure}/hotels', [VoyageDepartureManageController::class, 'storeHotel'])->name('circuits.voyages.departures.hotels.store');
        Route::put('circuits/voyages/{voyage}/departures/hotels/{departureHotel}', [VoyageDepartureManageController::class, 'updateHotel'])->name('circuits.voyages.departures.hotels.update');
        Route::delete('circuits/voyages/{voyage}/departures/hotels/{departureHotel}', [VoyageDepartureManageController::class, 'destroyHotel'])->name('circuits.voyages.departures.hotels.destroy');
        Route::post('circuits/voyages/{voyage}/departures/hotels/{departureHotel}/rooms', [VoyageDepartureManageController::class, 'storeRoom'])->name('circuits.voyages.departures.rooms.store');
        Route::put('circuits/voyages/{voyage}/departures/rooms/{departureHotelRoom}', [VoyageDepartureManageController::class, 'updateRoom'])->name('circuits.voyages.departures.rooms.update');
        Route::delete('circuits/voyages/{voyage}/departures/rooms/{departureHotelRoom}', [VoyageDepartureManageController::class, 'destroyRoom'])->name('circuits.voyages.departures.rooms.destroy');

        Route::get('circuits/voyage-themes', [LaravelVoyageThemeController::class, 'index'])->name('circuits.voyage-themes.index');
        Route::post('circuits/voyage-themes', [LaravelVoyageThemeController::class, 'store'])->name('circuits.voyage-themes.store');
        Route::match(['put', 'patch'], 'circuits/voyage-themes/{voyageTheme}', [LaravelVoyageThemeController::class, 'update'])->name('circuits.voyage-themes.update');
        Route::delete('circuits/voyage-themes/{voyageTheme}', [LaravelVoyageThemeController::class, 'destroy'])->name('circuits.voyage-themes.destroy');
        Route::get('circuits/voyage-themes/{voyageTheme}/impact', [LaravelVoyageThemeController::class, 'impact'])->name('circuits.voyage-themes.impact');

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

        Route::prefix('products-services/hajj-omra')->name('hajj-omra.')->group(function () {
            Route::get('/', [HajjOmraPackageController::class, 'index'])->name('index');
            Route::get('create', [HajjOmraPackageController::class, 'create'])->name('create');
            Route::post('/', [HajjOmraPackageController::class, 'store'])->name('store');
            Route::get('requests', [HajjOmraBookingRequestController::class, 'index'])->name('requests.index');
            Route::get('requests/{requestItem}', [HajjOmraBookingRequestController::class, 'show'])->name('requests.show')->whereNumber('requestItem');
            Route::match(['put', 'patch'], 'requests/{requestItem}', [HajjOmraBookingRequestController::class, 'update'])->name('requests.update')->whereNumber('requestItem');
            Route::get('{hajjOmraPackage}', [HajjOmraPackageController::class, 'show'])->name('show')->whereNumber('hajjOmraPackage');
            Route::get('{hajjOmraPackage}/edit', [HajjOmraPackageController::class, 'edit'])->name('edit')->whereNumber('hajjOmraPackage');
            Route::match(['put', 'patch'], '{hajjOmraPackage}', [HajjOmraPackageController::class, 'update'])->name('update')->whereNumber('hajjOmraPackage');
            Route::delete('{hajjOmraPackage}', [HajjOmraPackageController::class, 'destroy'])->name('destroy')->whereNumber('hajjOmraPackage');
        });

        Route::prefix('products-services/economic-offers')->name('economic-offers.')->group(function () {
            Route::get('/', [EconomicOfferController::class, 'index'])->name('index');
            Route::get('create', [EconomicOfferController::class, 'create'])->name('create');
            Route::post('/', [EconomicOfferController::class, 'store'])->name('store');
            Route::get('requests', [EconomicOfferRequestController::class, 'index'])->name('requests.index');
            Route::get('requests/{requestItem}', [EconomicOfferRequestController::class, 'show'])->name('requests.show')->whereNumber('requestItem');
            Route::match(['put', 'patch'], 'requests/{requestItem}', [EconomicOfferRequestController::class, 'update'])->name('requests.update')->whereNumber('requestItem');
            Route::get('{economicOffer}', [EconomicOfferController::class, 'show'])->name('show')->whereNumber('economicOffer');
            Route::get('{economicOffer}/edit', [EconomicOfferController::class, 'edit'])->name('edit')->whereNumber('economicOffer');
            Route::match(['put', 'patch'], '{economicOffer}', [EconomicOfferController::class, 'update'])->name('update')->whereNumber('economicOffer');
            Route::delete('{economicOffer}', [EconomicOfferController::class, 'destroy'])->name('destroy')->whereNumber('economicOffer');
        });

        Route::get('activity-offers', [ActivityOfferController::class, 'index'])->name('activity-offers.index');
        Route::get('activity-offers/create', [ActivityOfferController::class, 'create'])->name('activity-offers.create');
        Route::post('activity-offers', [ActivityOfferController::class, 'store'])->name('activity-offers.store');
        Route::get('activity-offers/{activityOffer}/edit', [ActivityOfferController::class, 'edit'])->name('activity-offers.edit');
        Route::match(['put', 'patch'], 'activity-offers/{activityOffer}', [ActivityOfferController::class, 'update'])->name('activity-offers.update');
        Route::delete('activity-offers/{activityOffer}', [ActivityOfferController::class, 'destroy'])->name('activity-offers.destroy');

        // Activités placeholders
        Route::get('activities/categories', [ActivityController::class, 'index'])->name('activities.categories');
        Route::get('activities/gallery', [ActivityController::class, 'index'])->name('activities.gallery');
        Route::get('activities/availability', [ActivityController::class, 'index'])->name('activities.availability');

        // Transferts placeholders
        Route::get('transfers/vehicles', [TourTransferController::class, 'index'])->name('transfers.vehicles');
        Route::get('transfers/pricing', [TourTransferController::class, 'index'])->name('transfers.pricing');
        Route::get('transfers/availability', [TourTransferController::class, 'index'])->name('transfers.availability');
        Route::get('operations/planning', [OperationsController::class, 'page'])->name('operations.planning')->defaults('submenu', 'planning');
        Route::get('operations/guides-chauffeurs', [OperationsController::class, 'page'])->name('operations.guides-chauffeurs')->defaults('submenu', 'guides-chauffeurs');
        Route::get('operations/vehicules', [OperationsController::class, 'page'])->name('operations.vehicules')->defaults('submenu', 'vehicules');
        Route::get('operations/logistique', [OperationsController::class, 'page'])->name('operations.logistique')->defaults('submenu', 'logistique');

        Route::get('visa', [VisaController::class, 'index'])->name('visa.index');
        Route::get('visa/demandes-visa', [VisaController::class, 'page'])->name('visa.demandes-visa')->defaults('submenu', 'demandes-visa');
        Route::get('visa/statuts', [VisaController::class, 'page'])->name('visa.statuts')->defaults('submenu', 'statuts');
        Route::get('visa/documents', [VisaController::class, 'page'])->name('visa.documents')->defaults('submenu', 'documents');

        Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
        Route::get('agent/commissions', [AgentCommissionPortalController::class, 'index'])->name('agent.commissions.index');
        Route::get('agent/commissions/{entry}', [AgentCommissionPortalController::class, 'show'])->name('agent.commissions.show');
        Route::get('finance/factures', [FinanceController::class, 'page'])->name('finance.factures')->defaults('submenu', 'factures');
        Route::get('finance/paiements', [FinanceController::class, 'page'])->name('finance.paiements')->defaults('submenu', 'paiements');
        Route::get('finance/depenses', [FinanceController::class, 'page'])->name('finance.depenses')->defaults('submenu', 'depenses');
        Route::get('finance/departs', [DepartureFinanceController::class, 'index'])->name('finance.departures.index');
        Route::get('finance/departs/{departure}', [DepartureFinanceController::class, 'show'])->name('finance.departures.show')->whereNumber('departure');
        Route::get('finance/departs/{departure}/charges/create', [DepartureFinanceController::class, 'create'])->name('finance.departures.charges.create')->whereNumber('departure');
        Route::post('finance/departs/{departure}/charges', [DepartureFinanceController::class, 'store'])->name('finance.departures.charges.store')->whereNumber('departure');
        Route::get('finance/departs/{departure}/charges/{charge}/edit', [DepartureFinanceController::class, 'edit'])->name('finance.departures.charges.edit')->whereNumber(['departure', 'charge']);
        Route::put('finance/departs/{departure}/charges/{charge}', [DepartureFinanceController::class, 'update'])->name('finance.departures.charges.update')->whereNumber(['departure', 'charge']);
        Route::delete('finance/departs/{departure}/charges/{charge}', [DepartureFinanceController::class, 'destroy'])->name('finance.departures.charges.destroy')->whereNumber(['departure', 'charge']);
        Route::get('finance/departs/{departure}/charges/{charge}/attachment', [DepartureFinanceController::class, 'attachment'])->name('finance.departures.charges.attachment')->whereNumber(['departure', 'charge']);
        Route::get('finance/departs/{departure}/pdf', [DepartureFinanceController::class, 'pdf'])->name('finance.departures.pdf')->whereNumber('departure');
        Route::get('finance/departs/{departure}/print', [DepartureFinanceController::class, 'print'])->name('finance.departures.print')->whereNumber('departure');
        Route::get('finance/departs/{departure}/excel', [DepartureFinanceController::class, 'exportExcel'])->name('finance.departures.excel')->whereNumber('departure');
        Route::get('finance/commissions', [FinanceAgentCommissionController::class, 'index'])->name('finance.commissions');
        Route::get('finance/commissions/export/excel', [FinanceAgentCommissionController::class, 'exportExcel'])->name('finance.commissions.export.excel');
        Route::get('finance/commissions/export/pdf', [FinanceAgentCommissionController::class, 'exportPdf'])->name('finance.commissions.export.pdf');
        Route::get('finance/commissions/{entry}', [FinanceAgentCommissionController::class, 'show'])->name('finance.commissions.show');
        Route::post('finance/commissions/{entry}/confirm', [FinanceAgentCommissionController::class, 'confirm'])->name('finance.commissions.confirm');
        Route::post('finance/commissions/{entry}/payable', [FinanceAgentCommissionController::class, 'payable'])->name('finance.commissions.payable');
        Route::post('finance/commissions/{entry}/paid', [FinanceAgentCommissionController::class, 'paid'])->name('finance.commissions.paid');
        Route::post('finance/commissions/{entry}/cancel', [FinanceAgentCommissionController::class, 'cancel'])->name('finance.commissions.cancel');
        Route::post('finance/commissions/{entry}/reverse', [FinanceAgentCommissionController::class, 'reverse'])->name('finance.commissions.reverse');
        Route::post('finance/commissions/{entry}/adjust', [FinanceAgentCommissionController::class, 'adjust'])->name('finance.commissions.adjust');
        Route::get('finance/rapports-financiers', [FinanceController::class, 'page'])->name('finance.rapports-financiers')->defaults('submenu', 'rapports-financiers');

        Route::get('partners', [PartnersController::class, 'index'])->name('partners.index');
        Route::get('partners/partenaires', [PartnersController::class, 'page'])->name('partners.partenaires')->defaults('submenu', 'partenaires');
        Route::get('partners/partenaires/create', [PartnerAccountController::class, 'create'])->name('partners.partenaires.create');
        Route::post('partners/partenaires', [PartnerAccountController::class, 'store'])->name('partners.partenaires.store');
        Route::get('partners/fournisseurs', [PartnersController::class, 'page'])->name('partners.fournisseurs')->defaults('submenu', 'fournisseurs');
        Route::get('partners/contrats', [PartnersController::class, 'page'])->name('partners.contrats')->defaults('submenu', 'contrats');
        Route::get('partners/wallet-requests', [PartnerAccountController::class, 'walletRequests'])->name('partners.wallet-requests');
        Route::post('partners/wallet-requests/{transaction}/approve', [PartnerAccountController::class, 'approveWalletRequest'])->name('partners.wallet-requests.approve');
        Route::post('partners/wallet-requests/{transaction}/reject', [PartnerAccountController::class, 'rejectWalletRequest'])->name('partners.wallet-requests.reject');
        Route::get('partners/{partner}', [PartnerAccountController::class, 'show'])->name('partners.show');
        Route::post('partners/{partner}/validate', [PartnerAccountController::class, 'validatePartner'])->name('partners.validate');
        Route::post('partners/{partner}/suspend', [PartnerAccountController::class, 'suspendPartner'])->name('partners.suspend');
        Route::get('partners/{partner}/admin/create', [PartnerAccountController::class, 'createAdmin'])->name('partners.admin.create');
        Route::post('partners/{partner}/admin', [PartnerAccountController::class, 'storeAdmin'])->name('partners.admin.store');
        Route::get('partners/{partner}/agents', [PartnerAccountController::class, 'agents'])->name('partners.agents');
        Route::get('partners/{partner}/reservations', [PartnerAccountController::class, 'reservations'])->name('partners.reservations');
        Route::get('partners/{partner}/wallet', [PartnerAccountController::class, 'wallet'])->name('partners.wallet');

        Route::get('partner-accounts', [PartnerAccountController::class, 'index'])->name('partner-accounts.index');
        Route::get('partner-accounts/{partner}', [PartnerAccountController::class, 'show'])->name('partner-accounts.show');
        Route::post('partner-accounts/{partner}/validate', [PartnerAccountController::class, 'validatePartner'])->name('partner-accounts.validate');
        Route::post('partner-accounts/{partner}/reject', [PartnerAccountController::class, 'rejectPartner'])->name('partner-accounts.reject');
        Route::post('partner-accounts/{partner}/voyage-access', [PartnerAccountController::class, 'updateVoyageAccess'])->name('partner-accounts.voyage-access');
        Route::post('partner-accounts/{partner}/suspend', [PartnerAccountController::class, 'suspendPartner'])->name('partner-accounts.suspend');
        Route::post('partner-accounts/{partner}/activate', [PartnerAccountController::class, 'activatePartner'])->name('partner-accounts.activate');
        Route::post('partner-accounts/{partner}/password-reset', [PartnerAccountController::class, 'sendPasswordReset'])->name('partner-accounts.password-reset');

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
        Route::post('settings/home-page/header', [HomePageSettingsController::class, 'updateHeader'])->name('settings.home-page.update-header');
        Route::get('settings/referentiels-metier', [BusinessReferenceController::class, 'index'])->name('settings.referentiels-metier');
        Route::post('settings/referentiels-metier/import-legacy', [BusinessReferenceController::class, 'importLegacy'])->name('settings.referentiels-metier.import-legacy');
        Route::get('settings/referentiels-metier/{groupKey}', [BusinessReferenceController::class, 'showGroup'])->name('settings.referentiels-metier.group');
        Route::post('settings/referentiels-metier/{groupKey}', [BusinessReferenceController::class, 'store'])->name('settings.referentiels-metier.store');
        Route::match(['put', 'patch'], 'settings/referentiels-metier/{groupKey}/{item}', [BusinessReferenceController::class, 'update'])->name('settings.referentiels-metier.update');
        Route::delete('settings/referentiels-metier/{groupKey}/{item}', [BusinessReferenceController::class, 'destroy'])->name('settings.referentiels-metier.destroy');
        Route::get('settings/types-charges', [FinanceChargeTypeController::class, 'index'])->name('settings.charge-types.index');
        Route::post('settings/types-charges', [FinanceChargeTypeController::class, 'store'])->name('settings.charge-types.store');
        Route::put('settings/types-charges/{chargeType}', [FinanceChargeTypeController::class, 'update'])->name('settings.charge-types.update')->whereNumber('chargeType');
        Route::delete('settings/types-charges/{chargeType}', [FinanceChargeTypeController::class, 'destroy'])->name('settings.charge-types.destroy')->whereNumber('chargeType');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::match(['put', 'patch'], 'profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

        Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
        Route::match(['put', 'patch'], 'branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');

        Route::get('agencies', [AgencyController::class, 'index'])->name('agencies.index');
        Route::get('agencies/create', [AgencyController::class, 'create'])->name('agencies.create');
        Route::post('agencies', [AgencyController::class, 'store'])->name('agencies.store');
        Route::get('agencies/performance', [AgencyController::class, 'performance'])->name('agencies.performance');
        Route::get('agencies/{agency}/dashboard', [AgencyController::class, 'dashboard'])->name('agencies.dashboard');
        Route::get('agencies/{agency}', [AgencyController::class, 'show'])->name('agencies.show');
        Route::get('agencies/{agency}/edit', [AgencyController::class, 'edit'])->name('agencies.edit');
        Route::match(['put', 'patch'], 'agencies/{agency}', [AgencyController::class, 'update'])->name('agencies.update');
        Route::patch('agencies/{agency}/toggle-status', [AgencyController::class, 'toggleStatus'])->name('agencies.toggle-status');
        Route::delete('agencies/{agency}', [AgencyController::class, 'destroy'])->name('agencies.destroy');

        Route::get('points-of-sale', [AgencyController::class, 'index'])->name('points-of-sale.index');
        Route::get('points-of-sale/create', [AgencyController::class, 'create'])->name('points-of-sale.create');
        Route::post('points-of-sale', [AgencyController::class, 'store'])->name('points-of-sale.store');
        Route::get('points-of-sale/performance', [AgencyController::class, 'performance'])->name('points-of-sale.performance');
        Route::get('points-of-sale/{agency}', [AgencyController::class, 'show'])->name('points-of-sale.show');
        Route::get('points-of-sale/{agency}/edit', [AgencyController::class, 'edit'])->name('points-of-sale.edit');
        Route::match(['put', 'patch'], 'points-of-sale/{agency}', [AgencyController::class, 'update'])->name('points-of-sale.update');
        Route::delete('points-of-sale/{agency}', [AgencyController::class, 'destroy'])->name('points-of-sale.destroy');

        Route::get('agency-accounts', [AgencyAccountController::class, 'index'])->name('agency-accounts.index');
        Route::get('agency-accounts/create', [AgencyAccountController::class, 'create'])->name('agency-accounts.create');
        Route::post('agency-accounts', [AgencyAccountController::class, 'store'])->name('agency-accounts.store');
        Route::get('agency-accounts/{user}', [AgencyAccountController::class, 'show'])->name('agency-accounts.show');
        Route::get('agency-accounts/{user}/edit', [AgencyAccountController::class, 'edit'])->name('agency-accounts.edit');
        Route::match(['put', 'patch'], 'agency-accounts/{user}', [AgencyAccountController::class, 'update'])->name('agency-accounts.update');
        Route::patch('agency-accounts/{user}/disable', [AgencyAccountController::class, 'disable'])->name('agency-accounts.disable');
        Route::post('agency-accounts/{user}/reset-password', [AgencyAccountController::class, 'resetPassword'])->name('agency-accounts.reset-password');

        Route::get('agency-employees', [AgencyEmployeeController::class, 'index'])->name('agency-employees.index');
        Route::get('agency-employees/create', [AgencyEmployeeController::class, 'create'])->name('agency-employees.create');
        Route::post('agency-employees', [AgencyEmployeeController::class, 'store'])->name('agency-employees.store');
        Route::get('agency-employees/{employee}', [AgencyEmployeeController::class, 'show'])->name('agency-employees.show');
        Route::get('agency-employees/{employee}/edit', [AgencyEmployeeController::class, 'edit'])->name('agency-employees.edit');
        Route::match(['put', 'patch'], 'agency-employees/{employee}', [AgencyEmployeeController::class, 'update'])->name('agency-employees.update');
        Route::delete('agency-employees/{employee}', [AgencyEmployeeController::class, 'destroy'])->name('agency-employees.destroy');

        Route::prefix('messagerie')
            ->name('messagerie.')
            ->group(function () {
                Route::get('/', [AgentMessagerieController::class, 'index'])->name('index');
                Route::post('/', [AgentMessagerieController::class, 'store'])->name('store');
                Route::get('{message}', [AgentMessagerieController::class, 'show'])->name('show')->whereNumber('message');
                Route::patch('{message}/read', [AgentMessagerieController::class, 'markRead'])->name('read')->whereNumber('message');
                Route::patch('{message}/star', [AgentMessagerieController::class, 'toggleStar'])->name('star')->whereNumber('message');
                Route::delete('{message}', [AgentMessagerieController::class, 'destroy'])->name('destroy')->whereNumber('message');
            });

        Route::prefix('wordpress')->name('wordpress.')->group(function () {
            Route::get('hotels', [HotelController::class, 'index'])->name('hotels.index');
            Route::get('hotels/create', [HotelController::class, 'create'])->name('hotels.create');
            Route::post('hotels', [HotelController::class, 'store'])->name('hotels.store');
            Route::get('hotels/{hotel}/edit', [HotelController::class, 'edit'])->name('hotels.edit')->whereNumber('hotel');
            Route::match(['put', 'patch'], 'hotels/{hotel}', [HotelController::class, 'update'])->name('hotels.update')->whereNumber('hotel');
            Route::delete('hotels/{hotel}', [HotelController::class, 'destroy'])->name('hotels.destroy')->whereNumber('hotel');

            Route::get('tours', [WpTourController::class, 'index'])->name('tours.index');
            Route::get('tours/create', [WpTourController::class, 'create'])->name('tours.create');
            Route::post('tours', [WpTourController::class, 'store'])->name('tours.store');
            Route::get('tours/{tour}/edit', [WpTourController::class, 'edit'])->name('tours.edit')->whereNumber('tour');
            Route::match(['put', 'patch'], 'tours/{tour}', [WpTourController::class, 'update'])->name('tours.update')->whereNumber('tour');
            Route::delete('tours/{tour}', [WpTourController::class, 'destroy'])->name('tours.destroy')->whereNumber('tour');
        });

        // ─── Group Deals ─────────────────────────────────────────────────────────
        Route::prefix('group-deals')->name('group-deals.')->group(function () {
            // Voyages Group Deal
            Route::get('trips', [GroupDealController::class, 'tripsIndex'])->name('trips.index');
            Route::get('trips/{voyage}', [GroupDealController::class, 'tripsShow'])->name('trips.show')->whereNumber('voyage');
            Route::post('trips/{voyage}/tiers', [GroupDealController::class, 'tierStore'])->name('trips.tiers.store')->whereNumber('voyage');
            Route::put('trips/{voyage}/tiers/{tier}', [GroupDealController::class, 'tierUpdate'])->name('trips.tiers.update')->whereNumber(['voyage', 'tier']);
            Route::delete('trips/{voyage}/tiers/{tier}', [GroupDealController::class, 'tierDestroy'])->name('trips.tiers.destroy')->whereNumber(['voyage', 'tier']);

            // Départs Group Deal
            Route::get('departures', [GroupDealController::class, 'departuresIndex'])->name('departures.index');
            Route::get('departures/{departure}', [GroupDealController::class, 'departuresShow'])->name('departures.show')->whereNumber('departure');
            Route::post('departures/{departure}/recalculate', [GroupDealController::class, 'departuresRecalculate'])->name('departures.recalculate')->whereNumber('departure');

            // Participants (gestion admin)
            Route::post('departures/{departure}/participants', [GroupDealController::class, 'participantStore'])->name('departures.participants.store')->whereNumber('departure');
            Route::patch('departures/{departure}/participants/{participant}', [GroupDealController::class, 'participantUpdate'])->name('departures.participants.update')->whereNumber(['departure', 'participant']);
        });

        Route::prefix('group-deals')->name('group-deals.')->group(function () {
            Route::get('/', [GroupDealOfferController::class, 'index'])->name('index');
            Route::get('create', [GroupDealOfferController::class, 'create'])->name('create');
            Route::post('/', [GroupDealOfferController::class, 'store'])->name('store');
            Route::get('{groupDeal}', [GroupDealOfferController::class, 'show'])->name('show')->whereNumber('groupDeal');
            Route::get('{groupDeal}/edit', [GroupDealOfferController::class, 'edit'])->name('edit')->whereNumber('groupDeal');
            Route::match(['put', 'patch'], '{groupDeal}', [GroupDealOfferController::class, 'update'])->name('update')->whereNumber('groupDeal');
            Route::delete('{groupDeal}', [GroupDealOfferController::class, 'destroy'])->name('destroy')->whereNumber('groupDeal');
            Route::post('{groupDeal}/recalculate', [GroupDealOfferController::class, 'recalculate'])->name('recalculate')->whereNumber('groupDeal');
            Route::post('{groupDeal}/tiers', [GroupDealOfferController::class, 'tierStore'])->name('tiers.store')->whereNumber('groupDeal');
            Route::put('{groupDeal}/tiers/{tier}', [GroupDealOfferController::class, 'tierUpdate'])->name('tiers.update')->whereNumber(['groupDeal', 'tier']);
            Route::delete('{groupDeal}/tiers/{tier}', [GroupDealOfferController::class, 'tierDestroy'])->name('tiers.destroy')->whereNumber(['groupDeal', 'tier']);
            Route::get('participants', [GroupDealOfferController::class, 'participantsIndex'])->name('participants.index');
            Route::get('tiers', [GroupDealOfferController::class, 'tiersIndex'])->name('tiers.index');
            Route::post('{groupDeal}/participants', [GroupDealOfferController::class, 'participantStore'])->name('participants.store')->whereNumber('groupDeal');
            Route::patch('{groupDeal}/participants/{participant}', [GroupDealOfferController::class, 'participantUpdate'])->name('participants.update')->whereNumber(['groupDeal', 'participant']);
        });
    });

Route::middleware(['auth', 'admin', 'ensure.not.locked', 'not.client'])
    ->prefix('agent')
    ->name('agent.')
    ->group(function () {
        Route::get('dashboard', [AgentDashboardController::class, 'index'])->name('dashboard');
        Route::get('catalogue', [AgentCatalogueController::class, 'index'])->name('catalogue');
        Route::get('reservations', [AgentReservationController::class, 'index'])->name('reservations.index');
        Route::get('reservations/create', [AgentReservationController::class, 'create'])->name('reservations.create');
        Route::get('reservations/hotels-rooms', [ReservationsController::class, 'hotelsRooms'])->name('reservations.hotels-rooms');
        Route::get('reservations/voyage-departures', [ReservationsController::class, 'voyageDepartures'])->name('reservations.voyage-departures');
        Route::get('reservations/extras', [ReservationsController::class, 'extras'])->name('reservations.extras');
        Route::get('reservations/departure-hotels-rooms', [ReservationsController::class, 'departureHotelsRooms'])->name('reservations.departure-hotels-rooms');
        Route::get('customers/clients/search', [ClientController::class, 'search'])->name('customers.clients.search');
        Route::post('customers/clients/quick-store', [ClientController::class, 'quickStore'])->name('customers.clients.quick-store');
        Route::post('reservations', [AgentReservationController::class, 'store'])->name('reservations.store');
        Route::get('reservations/{reservation}', [AgentReservationController::class, 'show'])->name('reservations.show')->whereNumber('reservation');
        Route::get('reservations-a-la-carte', [AgentCustomReservationController::class, 'index'])->name('custom-reservations.index');
        Route::get('reservations-a-la-carte/create', [AgentCustomReservationController::class, 'create'])->name('custom-reservations.create');
        Route::get('reservations-a-la-carte/clients/search', [AgentCustomReservationController::class, 'searchClients'])->name('custom-reservations.clients.search');
        Route::post('reservations-a-la-carte', [AgentCustomReservationController::class, 'store'])->name('custom-reservations.store');
        Route::post('reservations-a-la-carte/{customRequest}/take', [AgentCustomReservationController::class, 'take'])->name('custom-reservations.take')->whereNumber('customRequest');
        Route::get('reservations-a-la-carte/{customRequest}/quote', [CustomRequestQuoteController::class, 'quote'])->name('custom-reservations.quote')->whereNumber('customRequest');
        Route::post('reservations-a-la-carte/{customRequest}/quote', [CustomRequestQuoteController::class, 'store'])->name('custom-reservations.quote.store')->whereNumber('customRequest');
        Route::put('reservations-a-la-carte/{customRequest}/quote/{quote}', [CustomRequestQuoteController::class, 'update'])->name('custom-reservations.quote.update')->whereNumber(['customRequest', 'quote']);
        Route::post('reservations-a-la-carte/{customRequest}/quote/{quote}/prepare', [CustomRequestQuoteController::class, 'prepare'])->name('custom-reservations.quote.prepare')->whereNumber(['customRequest', 'quote']);
        Route::post('reservations-a-la-carte/{customRequest}/quote/{quote}/send', [CustomRequestQuoteController::class, 'send'])->name('custom-reservations.quote.send')->whereNumber(['customRequest', 'quote']);
        Route::post('reservations-a-la-carte/{customRequest}/documents', [CustomRequestDocumentController::class, 'store'])->name('custom-reservations.documents.store')->whereNumber('customRequest');
        Route::get('reservations-a-la-carte/{customRequest}', [AgentCustomReservationController::class, 'show'])->name('custom-reservations.show')->whereNumber('customRequest');
        Route::get('reservations-a-la-carte/{customRequest}/quote/{quote}/download', [AgentCustomReservationController::class, 'downloadQuote'])->name('custom-reservations.quote.download')->whereNumber(['customRequest', 'quote']);
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
        Route::match(['put', 'patch'], 'profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::prefix('messagerie')
            ->name('messagerie.')
            ->group(function () {
                Route::get('/', [AgentMessagerieController::class, 'index'])->name('index');
                Route::post('/', [AgentMessagerieController::class, 'store'])->name('store');
                Route::get('{message}', [AgentMessagerieController::class, 'show'])->name('show')->whereNumber('message');
                Route::patch('{message}/read', [AgentMessagerieController::class, 'markRead'])->name('read')->whereNumber('message');
                Route::patch('{message}/star', [AgentMessagerieController::class, 'toggleStar'])->name('star')->whereNumber('message');
                Route::delete('{message}', [AgentMessagerieController::class, 'destroy'])->name('destroy')->whereNumber('message');
            });
    });

Route::middleware('auth')->prefix('demo')->name('demo.')->group(function () {
    Route::get('/', [DemoController::class, 'index'])->name('index');
    Route::get('{any}', [DemoController::class, 'page'])->name('page');
});
