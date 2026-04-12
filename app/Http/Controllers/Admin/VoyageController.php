<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWpTourRequest;
use App\Http\Requests\UpdateWpTourRequest;
use App\Models\Airline;
use App\Models\Departure;
use App\Models\DepartureRoomAllocation;
use App\Models\TourHotel;
use App\Models\TourHotelRoom;
use App\Models\TourHotelRoomAvailability;
use App\Models\TourTransfer;
use App\Models\TravelDate;
use App\Models\TravelDayItem;
use App\Models\TravelProgramDay;
use App\Models\Voyage;
use App\Models\VoyageCancellationTerm;
use App\Models\VoyageDeparturePlace;
use App\Models\VoyageDiscountRule;
use App\Models\VoyageExtra;
use App\Models\VoyageTheme;
use App\Models\Wp\Activity;
use App\Models\Wp\TourDay;
use App\Models\Wp\TourDayActivity;
use App\Models\Wp\WpPost;
use App\Services\AdminWpTourCatalogQuery;
use App\Services\BusinessReferentialService;
use App\Services\VoyageAvailabilityService;
use App\Services\VoyageFlightOptionService;
use App\Services\VoyageFlightService;
use App\Services\VoyageThemeWpSyncService;
use App\Services\Wp\ProgramJsonService;
use App\Services\Wp\TourProgramService;
use App\Services\Wp\WpHeroImageService;
use App\Services\Wp\WpTourRepository;
use App\Support\TourPlacesCalculator;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VoyageController extends Controller
{
    protected WpTourRepository $repository;

    protected TourProgramService $programService;

    protected VoyageFlightService $voyageFlightService;

    protected VoyageFlightOptionService $voyageFlightOptionService;

    protected ProgramJsonService $programJsonService;

    protected VoyageAvailabilityService $voyageAvailabilityService;

    public function __construct(WpTourRepository $repository, TourProgramService $programService, VoyageFlightService $voyageFlightService, VoyageFlightOptionService $voyageFlightOptionService, ProgramJsonService $programJsonService, VoyageAvailabilityService $voyageAvailabilityService)
    {
        $this->repository = $repository;
        $this->programService = $programService;
        $this->voyageFlightService = $voyageFlightService;
        $this->voyageFlightOptionService = $voyageFlightOptionService;
        $this->programJsonService = $programJsonService;
        $this->voyageAvailabilityService = $voyageAvailabilityService;
    }

    /**
     * Display listing of WordPress tours.
     */
    public function index(Request $request): View
    {
        $wpConnectionFailed = false;
        $wpCatalogErrorMessage = null;
        $filterTourTypes = [];
        try {
            $filterTourTypes = DB::connection('wp')->table('terms as t')
                ->join('term_taxonomy as tt', 'tt.term_id', '=', 't.term_id')
                ->where('tt.taxonomy', 'st_tour_type')
                ->orderBy('t.name')
                ->get(['t.term_id', 't.name'])
                ->all();
        } catch (\Throwable $e) {
            $filterTourTypes = [];
        }

        try {
            $query = AdminWpTourCatalogQuery::baseQuery();
            $this->applyVoyageIndexFilters($query, $request);

            if (config('app.debug') && $request->filled('destination')) {
                Log::debug('VoyageController@index destination filter (SQL)', [
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings(),
                ]);
            }

            $tours = $query->paginate(20)->withQueryString();

            if (config('app.debug')) {
                Log::debug('VoyageController@index WP catalog', [
                    'total_wp_tours' => $tours->total(),
                    'per_page' => $tours->perPage(),
                ]);
            }

            $tours->getCollection()->transform(function ($tour) {
                $tour->adult_price = $tour->getMeta('adult_price');
                $tour->duration_day = $tour->getMeta('duration_day');
                $tour->address = $tour->getMeta('address');
                if (empty($tour->address)) {
                    $tour->address = $this->repository->getLocationNamesFromMultiLocation($tour->getMeta('multi_location'));
                }
                if (empty($tour->address)) {
                    $tour->address = '-';
                }
                $tour->child_price = $tour->getMeta('child_price');

                return $tour;
            });

            $wpIds = $tours->getCollection()->map(fn ($t) => (int) ($t->ID ?? 0))->filter()->values()->all();
            $lvByWp = $wpIds !== []
                ? Voyage::query()->whereIn('wp_post_id', $wpIds)->get(['wp_post_id', 'slug'])->keyBy('wp_post_id')
                : collect();
            $tours->getCollection()->transform(function ($tour) use ($lvByWp) {
                $wpId = (int) ($tour->ID ?? 0);
                $tour->laravel_slug = $wpId ? (string) ($lvByWp->get($wpId)->slug ?? '') : '';

                return $tour;
            });
        } catch (\Throwable $e) {
            $ctx = [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'wp_connection' => 'wp',
                'wp_database' => (string) config('database.connections.wp.database'),
                'wp_host' => (string) config('database.connections.wp.host'),
            ];
            if ($e instanceof QueryException) {
                $ctx['sql'] = $e->getSql();
                $ctx['bindings'] = $e->getBindings();
                $ctx['mysql_code'] = $e->errorInfo[1] ?? null;
            }
            Log::warning('VoyageController@index: catalogue voyages / filtres — échec requête', $ctx);

            if (! $this->isWpDatabaseConnectionFailure($e)) {
                if (config('app.debug')) {
                    throw $e;
                }
                $wpCatalogErrorMessage = 'Le chargement de la liste a échoué (requête ou filtre). Consultez les logs serveur.';
                report($e);
            } else {
                $wpConnectionFailed = true;
            }

            $tours = new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                20,
                \Illuminate\Pagination\Paginator::resolveCurrentPage(),
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
        }

        return view('admin.circuits.voyages.index', compact('tours', 'wpConnectionFailed', 'filterTourTypes', 'wpCatalogErrorMessage'));
    }

    /**
     * Erreurs typiques de connexion / choix de base MySQL (à ne pas confondre avec une erreur SQL de filtre).
     */
    private function isWpDatabaseConnectionFailure(\Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());
        if (str_contains($msg, 'unknown database')
            || str_contains($msg, 'access denied for user')
            || str_contains($msg, 'could not find driver')
            || str_contains($msg, 'connection refused')
            || str_contains($msg, 'no such file or directory')) {
            return true;
        }
        if ($e instanceof QueryException) {
            $code = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;

            return in_array($code, [1045, 1049, 2002, 2003, 2006], true);
        }

        return false;
    }

    /**
     * Filtres métier sur la liste des tours (WordPress st_tours + métas + taxonomies + dates).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Wp\WpPost>  $query
     */
    private function applyVoyageIndexFilters($query, Request $request): void
    {
        $pref = DB::connection('wp')->getTablePrefix();

        if ($request->filled('status')) {
            $query->where('post_status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $term = '%'.addcslashes($request->string('q')->toString(), '%_\\').'%';
            $query->where(function ($w) use ($term) {
                $w->where('post_title', 'like', $term)->orWhere('post_name', 'like', $term);
            });
        }

        if ($request->filled('modified_from')) {
            $query->whereDate('post_modified', '>=', $request->string('modified_from'));
        }
        if ($request->filled('modified_to')) {
            $query->whereDate('post_modified', '<=', $request->string('modified_to'));
        }

        if ($request->filled('duration_min')) {
            $min = (int) $request->input('duration_min');
            $query->whereExists(function ($q) use ($min, $pref) {
                $q->select(DB::raw(1))
                    ->from(DB::raw('`'.$pref.'postmeta` as pm'))
                    ->whereRaw('`pm`.`post_id` = `'.$pref.'posts`.`ID`')
                    ->whereRaw('`pm`.`meta_key` = ?', ['duration_day'])
                    ->whereRaw('CAST(`pm`.`meta_value` AS UNSIGNED) >= ?', [$min]);
            });
        }
        if ($request->filled('duration_max')) {
            $max = (int) $request->input('duration_max');
            $query->whereExists(function ($q) use ($max, $pref) {
                $q->select(DB::raw(1))
                    ->from(DB::raw('`'.$pref.'postmeta` as pm'))
                    ->whereRaw('`pm`.`post_id` = `'.$pref.'posts`.`ID`')
                    ->whereRaw('`pm`.`meta_key` = ?', ['duration_day'])
                    ->whereRaw('CAST(`pm`.`meta_value` AS UNSIGNED) <= ?', [$max]);
            });
        }

        if ($request->filled('price_min')) {
            $min = (float) $request->input('price_min');
            $query->whereExists(function ($q) use ($min, $pref) {
                $q->select(DB::raw(1))
                    ->from(DB::raw('`'.$pref.'postmeta` as pm'))
                    ->whereRaw('`pm`.`post_id` = `'.$pref.'posts`.`ID`')
                    ->whereRaw('`pm`.`meta_key` = ?', ['adult_price'])
                    ->whereRaw('CAST(`pm`.`meta_value` AS DECIMAL(12,2)) >= ?', [$min]);
            });
        }
        if ($request->filled('price_max')) {
            $max = (float) $request->input('price_max');
            $query->whereExists(function ($q) use ($max, $pref) {
                $q->select(DB::raw(1))
                    ->from(DB::raw('`'.$pref.'postmeta` as pm'))
                    ->whereRaw('`pm`.`post_id` = `'.$pref.'posts`.`ID`')
                    ->whereRaw('`pm`.`meta_key` = ?', ['adult_price'])
                    ->whereRaw('CAST(`pm`.`meta_value` AS DECIMAL(12,2)) <= ?', [$max]);
            });
        }

        if ($request->filled('destination')) {
            $this->applyDestinationFilterForVoyageIndex($query, $request->string('destination')->toString(), $pref);
        }

        if ($request->filled('tour_type') && (int) $request->input('tour_type') > 0) {
            $termId = (int) $request->input('tour_type');
            $query->whereExists(function ($q) use ($termId, $pref) {
                $q->select(DB::raw(1))
                    ->from(DB::raw('`'.$pref.'term_relationships` as tr'))
                    ->join(DB::raw('`'.$pref.'term_taxonomy` as tt'), DB::raw('`tt`.`term_taxonomy_id`'), '=', DB::raw('`tr`.`term_taxonomy_id`'))
                    ->whereRaw('`tr`.`object_id` = `'.$pref.'posts`.`ID`')
                    ->whereRaw('`tt`.`term_id` = ?', [$termId])
                    ->whereRaw('`tt`.`taxonomy` = ?', ['st_tour_type']);
            });
        }

        if ($request->filled('has_departures')) {
            $v = (string) $request->input('has_departures');
            if ($v === '1') {
                $query->whereExists(function ($q) use ($pref) {
                    $q->select(DB::raw(1))
                        ->from(DB::raw('`'.$pref.'aj_travel_dates` as td'))
                        ->whereRaw('`td`.`travel_id` = `'.$pref.'posts`.`ID`')
                        ->whereRaw('`td`.`is_active` = ?', [1]);
                });
            } elseif ($v === '0') {
                $query->whereNotExists(function ($q) use ($pref) {
                    $q->select(DB::raw(1))
                        ->from(DB::raw('`'.$pref.'aj_travel_dates` as td'))
                        ->whereRaw('`td`.`travel_id` = `'.$pref.'posts`.`ID`')
                        ->whereRaw('`td`.`is_active` = ?', [1]);
                });
            }
        }

        if ($request->filled('has_laravel_public') && $request->input('has_laravel_public') === '1') {
            $ids = Voyage::query()->whereNotNull('slug')->where('slug', '!=', '')->pluck('wp_post_id')->filter()->values()->all();
            if ($ids !== []) {
                $query->whereIn('ID', $ids);
            } else {
                $query->whereRaw('1 = 0');
            }
        }
    }

    /**
     * Filtre « Destination » : mêmes sources que la colonne du tableau (voir transform dans index()).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Wp\WpPost>  $query
     */
    private function applyDestinationFilterForVoyageIndex($query, string $term, string $pref): void
    {
        $term = trim($term);
        if ($term === '') {
            return;
        }

        $d = '%'.addcslashes($term, '%_\\').'%';
        $postsTable = '`'.$pref.'posts`';

        $query->where(function ($w) use ($d, $pref, $postsTable) {
            $w->where('post_title', 'like', $d)
                ->orWhere('post_name', 'like', $d)
                ->orWhereExists(function ($q) use ($d, $pref) {
                    $q->select(DB::raw(1))
                        ->from(DB::raw('`'.$pref.'postmeta` as pm'))
                        ->whereRaw('`pm`.`post_id` = `'.$pref.'posts`.`ID`')
                        ->whereRaw('`pm`.`meta_key` = ?', ['address'])
                        ->whereRaw('`pm`.`meta_value` LIKE ?', [$d]);
                })
                ->orWhereExists(function ($q) use ($d, $pref) {
                    $q->select(DB::raw(1))
                        ->from(DB::raw('`'.$pref.'postmeta` as pm'))
                        ->whereRaw('`pm`.`post_id` = `'.$pref.'posts`.`ID`')
                        ->whereRaw('`pm`.`meta_key` = ?', ['aj_catalog_destination'])
                        ->whereRaw('`pm`.`meta_value` LIKE ?', [$d]);
                })
                ->orWhereExists(function ($q) use ($d, $pref) {
                    $q->select(DB::raw(1))
                        ->from(DB::raw('`'.$pref.'postmeta` as pm'))
                        ->whereRaw('`pm`.`post_id` = `'.$pref.'posts`.`ID`')
                        ->whereRaw('`pm`.`meta_key` = ?', ['multi_location'])
                        ->whereRaw('`pm`.`meta_value` LIKE ?', [$d]);
                })
                ->orWhereRaw(
                    'EXISTS (
                        SELECT 1
                        FROM `'.$pref.'posts` AS `loc`
                        INNER JOIN `'.$pref.'postmeta` AS `pm_ml`
                            ON `pm_ml`.`post_id` = '.$postsTable.'.`ID`
                            AND `pm_ml`.`meta_key` = ?
                            AND INSTR(`pm_ml`.`meta_value`, CONCAT(\'_\', `loc`.`ID`, \'_\')) > 0
                        WHERE `loc`.`post_type` = ?
                            AND (`loc`.`post_title` LIKE ? OR `loc`.`post_name` LIKE ?)
                    )',
                    ['multi_location', 'location', $d, $d]
                );
        });
    }

    /**
     * Show single tour (dÃ©tail).
     */
    public function show(int $id): View
    {
        $wpPost = WpPost::tours()->where('ID', $id)->firstOrFail();

        // CrÃ©er un objet compatible avec les vues existantes
        $voyage = $wpPost;
        $voyage->name = $wpPost->post_title; // Alias pour compatibilitÃ©
        $voyage->slug = $wpPost->post_name;
        $voyage->description = $wpPost->post_content;
        $voyage->updated_at = $wpPost->post_modified;
        $voyage->created_at = $wpPost->post_date;

        // Charger les metas (max_people et places sont calculÃ©s Ã  partir des chambres et enregistrÃ©s Ã  la sauvegarde)
        $meta = [
            'adult_price' => $wpPost->getMeta('adult_price'),
            'child_price' => $wpPost->getMeta('child_price'),
            'duration_day' => $wpPost->getMeta('duration_day'),
            'address' => $wpPost->getMeta('address'),
            'min_price' => $wpPost->getMeta('min_price'),
            'min_people' => $wpPost->getMeta('min_people'),
            'max_people' => $wpPost->getMeta('max_people'),
            'places' => $wpPost->getMeta('places'),
            'thumbnail_id' => $wpPost->getMeta('_thumbnail_id'),
            'hero_image_id' => $wpPost->getMeta('_tour_hero_image_id'),
            'hero_gallery_ids' => $wpPost->getMeta('_tour_hero_gallery_ids'),
            'gallery' => $wpPost->getMeta('gallery'),
        ];

        // Programme par jours (aj_tour_days + activitÃ©s) pour la timeline "Programme du circuit"
        $programDays = collect();
        try {
            $programDays = $this->programService->loadProgram((int) $id);
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@show loadProgram failed', ['tour_id' => $id, 'error' => $e->getMessage()]);
        }

        return view('admin.circuits.voyages.show', compact('voyage', 'meta', 'programDays'));
    }

    /**
     * Show create form. Uses the same view as edit with empty/default data.
     */
    public function create(): View
    {
        $voyage = (object) [
            'ID' => 0,
            'post_title' => '',
            'post_name' => '',
            'post_content' => '',
            'post_excerpt' => '',
            'post_status' => 'draft',
            'post_modified' => '',
            'post_date' => '',
            'name' => '',
            'slug' => '',
            'description' => '',
            'accroche' => '',
            'status' => 'draft',
        ];

        $metaKeys = [
            'address', 'id_location', 'location_id', 'multi_location', 'map_lat', 'map_lng', 'map_zoom', 'map_type',
            'is_featured', 'tour_price_by', 'st_tour_external_booking', 'hide_adult_in_booking_form', 'duration_day', 'max_people', 'min_people',
            'contact_email', 'phone', 'fax', 'website',
            'min_price', 'base_price', 'sale_price', 'adult_price', 'child_price', 'infant_price', 'discount', 'discount_type', 'discount_by_people_type', 'calculator_discount_by_people_type',
            'tours_include', 'tours_exclude', 'tours_highlight', 'tours_faq', 'tours_program_style',
            'tours_booking_period', 'st_booking_option_type', 'check_in', 'check_out', 'st_allow_cancel', 'st_cancel_percent', 'st_cancel_number_day', 'ical_url',
            'thumbnail_id', 'hero_image_id', 'hero_gallery_ids', 'gallery', 'video', 'st_google_map',
            'is_meta_payment_gateway_st_paypal', 'is_meta_payment_gateway_st_onepay', 'is_meta_payment_gateway_st_onepay_atm', 'is_meta_payment_gateway_st_payu',
            'is_meta_payment_gateway_st_payulatam', 'is_meta_payment_gateway_st_payumoney', 'is_meta_payment_gateway_st_razor',
        ];
        $meta = array_fill_keys($metaKeys, '');

        $gallery_csv = '';
        $availableTaxonomies = $this->getAvailableTaxonomies();
        $assignedTaxonomies = $this->getPostTaxonomies(0);
        $locationsTree = $this->repository->getLocationsTree();
        $selectedLocationIds = [];

        $worldCountries = config('countries', []);
        $countryCitiesData = $this->buildCountryCitiesData($worldCountries, $locationsTree);
        $worldCities = config('world_cities', []);
        $mergedCitiesByCode = $this->buildMergedCitiesByCode($worldCountries, $worldCities, $countryCitiesData);

        $oldProgrammeDays = $this->getOldProgrammeDaysInput();
        try {
            $activitiesCatalog = Activity::orderBy('title')->get();
        } catch (\Throwable $e) {
            $activitiesCatalog = collect();
        }
        $programDays = $this->buildProgrammeFormDaysFromPayload($oldProgrammeDays, $activitiesCatalog);
        try {
            $airlines = Airline::query()->orderBy('name')->get();
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@create: could not load airlines', ['error' => $e->getMessage()]);
            $airlines = collect();
        }

        // Pas de modÃ¨le Voyage Laravel tant que le tour WP nâ€™existe pas : null Ã©vite les accÃ¨s Ã  des attributs manquants dans la vue partagÃ©e create/edit.
        $laravelVoyage = null;
        $outboundFlight = null;
        $inboundFlight = null;
        $flightOptionsByType = ['outbound' => collect(), 'return' => collect(), 'segment' => collect()];
        $flightOptionsWithIndex = [];
        $nextFlightOptionIndex = 0;
        $lastDayNumber = 1;
        $heroImageUrl = null;
        $tourHotel = null;
        $tourHotels = collect();
        $transferArrival = null;
        $transferDeparture = null;
        $transferArrivals = collect();
        $transferDepartures = collect();
        $suggestedArrivalFrom = '';
        $suggestedArrivalTo = '';
        $suggestedDepartureFrom = '';
        $suggestedDepartureTo = '';
        $tourHotelImageUrl = null;
        $transferArrivalImageUrl = null;
        $transferDepartureImageUrl = null;
        $otherTourHotelsForCopy = collect();
        $otherTourTitles = [];
        $departurePlaces = collect();
        $departurePlaceFlightsFromTour = collect();
        $travelDates = collect();
        $programJson = [];
        $programApiUrl = '';
        $programDayHotelsTransfers = $this->extractProgrammeDayRelationsFromInput($oldProgrammeDays);
        $tourActivities = collect();
        $totalPlacesVoyage = 0;
        $voyageExtras = collect();
        $allVoyageThemes = VoyageTheme::query()->active()->ordered()->get();
        $veDestinationQuick = null;
        $discountRules = collect();
        $cancellationTerms = collect();
        $paymentMethodOptions = BusinessReferentialService::paymentMethods();
        $businessReferentials = BusinessReferentialService::allMerged();

        return view('admin.circuits.voyages.edit', compact(
            'voyage', 'meta', 'gallery_csv', 'availableTaxonomies', 'assignedTaxonomies', 'locationsTree', 'selectedLocationIds',
            'worldCountries', 'countryCitiesData', 'mergedCitiesByCode', 'programDays', 'activitiesCatalog', 'tourActivities', 'airlines',
            'laravelVoyage', 'outboundFlight', 'inboundFlight', 'flightOptionsByType', 'flightOptionsWithIndex', 'nextFlightOptionIndex', 'lastDayNumber',
            'heroImageUrl', 'tourHotel', 'tourHotels', 'otherTourHotelsForCopy', 'otherTourTitles', 'transferArrival', 'transferDeparture', 'transferArrivals', 'transferDepartures',
            'suggestedArrivalFrom', 'suggestedArrivalTo', 'suggestedDepartureFrom', 'suggestedDepartureTo',
            'tourHotelImageUrl', 'transferArrivalImageUrl', 'transferDepartureImageUrl',
            'departurePlaces', 'departurePlaceFlightsFromTour', 'travelDates', 'programJson', 'programApiUrl', 'programDayHotelsTransfers',
            'totalPlacesVoyage', 'voyageExtras', 'allVoyageThemes',
            'veDestinationQuick', 'discountRules', 'cancellationTerms', 'paymentMethodOptions', 'businessReferentials'
        ));
    }

    /**
     * Store new tour in WordPress.
     */
    public function store(StoreWpTourRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // GÃ©nÃ©rer slug si vide
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Convertir gallery CSV en array
        if (! empty($validated['gallery_ids'])) {
            $validated['gallery_ids'] = array_filter(array_map('trim', explode(',', $validated['gallery_ids'])));
        }

        try {
            $tour = $this->repository->createTour($validated);
            $laravelVoyage = Voyage::firstOrCreate(
                ['wp_post_id' => $tour->ID],
                ['name' => $tour->post_title ?? 'Tour', 'slug' => 'tour-'.$tour->ID]
            );
            $this->syncVoyageThemesFromRequest($request, $laravelVoyage);

            // Save tour program if provided (PHP serialized)
            if ($request->has('tours_program')) {
                $programStyle = $request->input('tours_program_style', 'style1');
                $programItems = $request->input('tours_program', []);
                $this->repository->saveTourProgram($tour->ID, $programStyle, $programItems);
            }

            if ($request->has('programme_days')) {
                $this->syncProgrammeDaysAndActivities($tour->ID, $request);
                $this->repository->updateTour($tour->ID, [
                    'duration_day' => $this->programService->countDays($tour->ID),
                ]);
            }

            if (! $request->boolean('without_flight') && $request->input('without_flight') !== '1' && $request->has('flights')) {
                try {
                    $this->voyageFlightService->syncFlights($laravelVoyage->id, $request->input('flights', []));
                } catch (\Throwable $e) {
                    \Log::error('VoyageController@store voyage flights failed', ['tour_id' => $tour->ID, 'message' => $e->getMessage()]);
                }
            }

            return redirect()
                ->route('admin.circuits.voyages.edit', $tour->ID)
                ->with('success', 'Tour créé avec succès dans WordPress ! Visible immédiatement sur ajinsafro.net');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Erreur lors de la création : '.$e->getMessage()]);
        }
    }

    /**
     * Show edit form.
     */
    public function edit(int $id): View
    {
        $wpPost = WpPost::tours()->where('ID', $id)->firstOrFail();

        // CrÃ©er un objet compatible avec les vues existantes
        $voyage = $wpPost;
        $voyage->name = $wpPost->post_title;
        $voyage->slug = $wpPost->post_name;
        $voyage->description = $wpPost->post_content;
        $voyage->accroche = $wpPost->post_excerpt;
        $voyage->updated_at = $wpPost->post_modified;
        $voyage->created_at = $wpPost->post_date;
        $voyage->status = $wpPost->post_status;

        // Charger TOUTES les metas Traveler (lecture complÃ¨te)
        $meta = [
            // LOCATION
            'address' => $wpPost->getMeta('address'),
            'id_location' => $wpPost->getMeta('id_location'),
            'location_id' => $wpPost->getMeta('location_id'),
            'multi_location' => $wpPost->getMeta('multi_location'),
            'map_lat' => $wpPost->getMeta('map_lat'),
            'map_lng' => $wpPost->getMeta('map_lng'),
            'map_zoom' => $wpPost->getMeta('map_zoom'),
            'map_type' => $wpPost->getMeta('map_type'),

            // GENERAL
            'is_featured' => $wpPost->getMeta('is_featured'),
            'tour_price_by' => $wpPost->getMeta('tour_price_by'),
            'st_tour_external_booking' => $wpPost->getMeta('st_tour_external_booking'),
            'hide_adult_in_booking_form' => $wpPost->getMeta('hide_adult_in_booking_form'),
            'duration_day' => $wpPost->getMeta('duration_day'),
            'max_people' => $wpPost->getMeta('max_people'),
            'min_people' => $wpPost->getMeta('min_people'),

            // CONTACT
            'contact_email' => $wpPost->getMeta('contact_email'),
            'phone' => $wpPost->getMeta('phone'),
            'fax' => $wpPost->getMeta('fax'),
            'website' => $wpPost->getMeta('website'),

            // PRICE
            'min_price' => $wpPost->getMeta('min_price'),
            'base_price' => $wpPost->getMeta('base_price'),
            'sale_price' => $wpPost->getMeta('sale_price'),
            'adult_price' => $wpPost->getMeta('adult_price'),
            'child_price' => $wpPost->getMeta('child_price'),
            'infant_price' => $wpPost->getMeta('infant_price'),
            'room_price_double' => $wpPost->getMeta('room_price_double'),
            'room_price_twin' => $wpPost->getMeta('room_price_twin'),
            'room_price_single' => $wpPost->getMeta('room_price_single'),
            'commission_adulte' => $wpPost->getMeta('commission_adulte'),
            'commission_enfant' => $wpPost->getMeta('commission_enfant'),
            'discount' => $wpPost->getMeta('discount'),
            'discount_type' => $wpPost->getMeta('discount_type'),
            'discount_by_people_type' => $wpPost->getMeta('discount_by_people_type'),
            'calculator_discount_by_people_type' => $wpPost->getMeta('calculator_discount_by_people_type'),

            // INFORMATION
            'tours_include' => $wpPost->getMeta('tours_include'),
            'tours_exclude' => $wpPost->getMeta('tours_exclude'),
            'tours_highlight' => $wpPost->getMeta('tours_highlight'),
            'tours_faq' => $wpPost->getMeta('tours_faq'),
            'tours_program_style' => $wpPost->getMeta('tours_program_style'),

            // AVAILABILITY
            'tours_booking_period' => $wpPost->getMeta('tours_booking_period'),
            'st_booking_option_type' => $wpPost->getMeta('st_booking_option_type'),
            'check_in' => $wpPost->getMeta('check_in'),
            'check_out' => $wpPost->getMeta('check_out'),

            // CANCEL BOOKING
            'st_allow_cancel' => $wpPost->getMeta('st_allow_cancel'),
            'st_cancel_percent' => $wpPost->getMeta('st_cancel_percent'),
            'st_cancel_number_day' => $wpPost->getMeta('st_cancel_number_day'),

            // ICAL
            'ical_url' => $wpPost->getMeta('ical_url'),

            // MEDIA
            'thumbnail_id' => $wpPost->getMeta('_thumbnail_id'),
            'hero_image_id' => $wpPost->getMeta('_tour_hero_image_id'),
            'hero_gallery_ids' => $wpPost->getMeta('_tour_hero_gallery_ids'),
            'gallery' => $wpPost->getMeta('gallery'),
            'video' => $wpPost->getMeta('video'),

            // MAP
            'st_google_map' => $wpPost->getMeta('st_google_map'),

            // PAYMENT GATEWAYS
            'is_meta_payment_gateway_st_paypal' => $wpPost->getMeta('is_meta_payment_gateway_st_paypal'),
            'is_meta_payment_gateway_st_onepay' => $wpPost->getMeta('is_meta_payment_gateway_st_onepay'),
            'is_meta_payment_gateway_st_onepay_atm' => $wpPost->getMeta('is_meta_payment_gateway_st_onepay_atm'),
            'is_meta_payment_gateway_st_payu' => $wpPost->getMeta('is_meta_payment_gateway_st_payu'),
            'is_meta_payment_gateway_st_payulatam' => $wpPost->getMeta('is_meta_payment_gateway_st_payulatam'),
            'is_meta_payment_gateway_st_payumoney' => $wpPost->getMeta('is_meta_payment_gateway_st_payumoney'),
            'is_meta_payment_gateway_st_razor' => $wpPost->getMeta('is_meta_payment_gateway_st_razor'),
        ];

        // Convertir gallery en CSV
        $gallery_csv = '';
        if (! empty($meta['gallery'])) {
            $gallery_csv = is_array($meta['gallery']) ? implode(',', $meta['gallery']) : $meta['gallery'];
        }

        // Charger les taxonomies disponibles
        $availableTaxonomies = $this->getAvailableTaxonomies();

        // Charger les taxonomies assignÃ©es Ã  ce tour
        $assignedTaxonomies = $this->getPostTaxonomies($id);

        // Charger les locations (tree)
        $locationsTree = $this->repository->getLocationsTree();

        // Parser multi_location actuel
        $multiLocationValue = $wpPost->getMeta('multi_location');
        $selectedLocationIds = $this->repository->parseMultiLocation($multiLocationValue);

        // Tous les pays du monde (config) + correspondance avec les locations WP
        $worldCountries = config('countries', []);
        $countryCitiesData = $this->buildCountryCitiesData($worldCountries, $locationsTree);
        $worldCities = config('world_cities', []);
        $mergedCitiesByCode = $this->buildMergedCitiesByCode($worldCountries, $worldCities, $countryCitiesData);

        // Programme par jours (Laravel: aj_tour_days + activitÃ©s). The Blade loop iterates over
        // loadProgram() (WP aj_tour_days). If duration_day meta is stale (e.g. 4) but travel_program_days
        // has 7 rows, ensureDaysExist() only used meta and WP stayed at 4 days â€” the UI showed 4 cards.
        $laravelVoyage = Voyage::firstOrCreate(
            ['wp_post_id' => $id],
            ['name' => $wpPost->post_title ?? 'Tour', 'slug' => 'tour-'.$id]
        );

        $programDays = collect();
        $activitiesCatalog = collect();
        try {
            $durationFromMeta = $this->parseDurationDays($meta['duration_day'] ?? null);
            $maxLaravelDayNumber = (int) (TravelProgramDay::where('voyage_id', $laravelVoyage->id)->max('day_number') ?? 0);
            $maxWpDayNumber = (int) (TourDay::where('tour_id', $id)->max('day_number') ?? 0);
            $ensureCount = max($durationFromMeta, $maxLaravelDayNumber, $maxWpDayNumber);
            if ($ensureCount > 1) {
                $this->programService->ensureDaysExist($id, $ensureCount);
            }
            $this->programService->importWpToursProgramToDayNotesIfEmpty($id);
            $programDays = $this->programService->loadProgram($id);
            $activitiesCatalog = Activity::orderBy('title')->get();
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@edit: could not load program days', ['tour_id' => $id, 'error' => $e->getMessage()]);
        }

        $tourActivities = collect();
        try {
            $tourActivities = TravelDayItem::query()
                ->where('voyage_id', $laravelVoyage->id)
                ->where('type', 'activity')
                ->where(function ($query) {
                    $query->where('meta_json->source', 'voyage_activities_tab')
                        ->orWhereNull('meta_json');
                })
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@edit: could not load tour activities', [
                'tour_id' => $id,
                'voyage_id' => $laravelVoyage->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Charger les TravelProgramDay avec relations hotels/transfers pour le modal par jour
        // ClÃ© par index du jour (0, 1, 2...) pour correspondre Ã  $dayIndex dans la vue (programme_days)
        $programDayHotelsTransfers = [];
        $travelProgramDaysWithRelations = collect();
        try {
            // Important: TourTransfer model uses the 'wp' connection, while program_day_transfers
            // lives on 'mysql'. A belongsToMany would attempt to join the pivot table on the 'wp'
            // connection and crash the edit page. So we DO NOT eager-load transfers here.
            $travelProgramDaysWithRelations = $laravelVoyage->programDays()
                ->with(['hotel'])
                ->orderBy('day_number')
                ->get();

            // Load transfer ids from the pivot table using the correct connection.
            $transferIdsByProgramDayId = [];
            try {
                $programDayIds = $travelProgramDaysWithRelations->pluck('id')->filter()->values()->toArray();
                if (! empty($programDayIds) && \Illuminate\Support\Facades\Schema::connection('mysql')->hasTable('program_day_transfers')) {
                    $rows = \Illuminate\Support\Facades\DB::connection('mysql')
                        ->table('program_day_transfers')
                        ->whereIn('program_day_id', $programDayIds)
                        ->get(['program_day_id', 'transfer_id']);

                    foreach ($rows as $r) {
                        $pid = (int) ($r->program_day_id ?? 0);
                        $tid = (int) ($r->transfer_id ?? 0);
                        if ($pid > 0 && $tid > 0) {
                            $transferIdsByProgramDayId[$pid] ??= [];
                            $transferIdsByProgramDayId[$pid][] = $tid;
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('VoyageController@edit: could not load program_day_transfers pivot', [
                    'voyage_id' => $laravelVoyage->id,
                    'error' => $e->getMessage(),
                ]);
            }

            foreach ($travelProgramDaysWithRelations as $index => $pday) {
                $programDayHotelsTransfers[$index] = [
                    'hotel_id' => $pday->hotel_id,
                    'transfer_ids' => array_values(array_unique($transferIdsByProgramDayId[(int) $pday->id] ?? [])),
                ];
            }
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@edit: could not load travel program days with relations', ['voyage_id' => $laravelVoyage->id, 'error' => $e->getMessage()]);
        }

        $programDays = $this->mergeProgrammeDaysWithLaravelData($programDays, $travelProgramDaysWithRelations);

        $oldProgrammeDays = $this->getOldProgrammeDaysInput();
        if (! empty($oldProgrammeDays)) {
            $programDays = $this->buildProgrammeFormDaysFromPayload($oldProgrammeDays, $activitiesCatalog);
            $programDayHotelsTransfers = $this->extractProgrammeDayRelationsFromInput($oldProgrammeDays);
        } elseif ($programDays->isEmpty()) {
            $programDays = $this->buildProgrammeFormDaysFromPayload([], $activitiesCatalog);
        }

        $outboundFlight = $laravelVoyage->outboundFlight;
        $inboundFlight = $laravelVoyage->inboundFlight;
        $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int) ($meta['duration_day'] ?? 1));
        $flightOptionsByType = $this->voyageFlightOptionService->getOptionsForVoyage($laravelVoyage->id);
        if ($flightOptionsByType['outbound']->isEmpty() && $flightOptionsByType['return']->isEmpty() && ($outboundFlight || $inboundFlight)) {
            $this->ensureFlightOptionsFromLegacy($laravelVoyage->id, $lastDayNumber);
            $flightOptionsByType = $this->voyageFlightOptionService->getOptionsForVoyage($laravelVoyage->id);
        }
        $nextFlightOptionIndex = 0;
        $flightOptionsWithIndex = [];
        foreach (['outbound', 'return', 'segment'] as $t) {
            foreach ($flightOptionsByType[$t] as $opt) {
                $flightOptionsWithIndex[] = ['index' => $nextFlightOptionIndex++, 'option' => $opt, 'type' => $t];
            }
        }
        if ($laravelVoyage->wp_post_id) {
            try {
                $this->voyageFlightOptionService->syncOptionsToWp($laravelVoyage->id, (int) $laravelVoyage->wp_post_id, $lastDayNumber);
            } catch (\Throwable $e) {
                \Log::warning('VoyageController@edit: syncOptionsToWp failed', ['tour_id' => $id, 'error' => $e->getMessage()]);
            }
        }
        try {
            $airlines = Airline::query()->orderBy('name')->get();
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@edit: could not load airlines', ['tour_id' => $id, 'error' => $e->getMessage()]);
            $airlines = collect();
        }
        $heroImageUrl = null;
        $heroId = ! empty($meta['hero_image_id']) ? (int) $meta['hero_image_id'] : (! empty($meta['thumbnail_id']) ? (int) $meta['thumbnail_id'] : null);
        if ($heroId) {
            $heroImageUrl = WpHeroImageService::getAttachmentUrl($heroId);
        }

        // HÃ´tel + Transferts (aj_tour_hotels, aj_tour_transfers) â€” multi-row support
        $tourHotels = TourHotel::getAllForTour($id)->load([
            'rooms.dateAvailabilities' => fn ($query) => $query->orderBy('travel_date_id')->orderBy('id'),
        ]);
        $tourHotel = $tourHotels->first();
        // Liste dâ€™hÃ´tels dâ€™autres voyages pour Â« Choisir un hÃ´tel existant Â» (copie des donnÃ©es)
        $otherTourHotelsForCopy = TourHotel::where('tour_id', '!=', $id)->orderBy('hotel_name')->get();
        $otherTourTitles = [];
        if ($otherTourHotelsForCopy->isNotEmpty()) {
            $otherTourTitles = WpPost::on('wp')->whereIn('ID', $otherTourHotelsForCopy->pluck('tour_id')->unique()->filter()->values()->toArray())->pluck('post_title', 'ID')->toArray();
        }
        $transfers = TourTransfer::getForTour($id);
        $transferArrivals = $transfers['arrival'];
        $transferDepartures = $transfers['departure'];
        $transferArrival = $transferArrivals->first();
        $transferDeparture = $transferDepartures->first();
        // Valeurs suggÃ©rÃ©es : transfert aller = aÃ©roport d'arrivÃ©e (vol aller to_city) â†’ hÃ´tel ; transfert retour = hÃ´tel â†’ aÃ©roport de dÃ©part (vol retour from_city)
        $suggestedArrivalFrom = $outboundFlight ? trim($outboundFlight->to_city ?? $outboundFlight->to_label ?? '') : '';
        $suggestedArrivalTo = $tourHotel ? trim($tourHotel->hotel_name ?? '') : '';
        $suggestedDepartureFrom = $tourHotel ? trim($tourHotel->hotel_name ?? '') : '';
        $suggestedDepartureTo = $inboundFlight ? trim($inboundFlight->from_city ?? $inboundFlight->from_label ?? '') : '';

        $tourHotelImageUrl = $tourHotel && $tourHotel->image_id ? WpHeroImageService::getAttachmentUrl((int) $tourHotel->image_id) : null;
        $transferArrivalImageUrl = $transferArrival && $transferArrival->image_id ? WpHeroImageService::getAttachmentUrl((int) $transferArrival->image_id) : null;
        $transferDepartureImageUrl = $transferDeparture && $transferDeparture->image_id ? WpHeroImageService::getAttachmentUrl((int) $transferDeparture->image_id) : null;

        // Lieux de dÃ©part : source Laravel (voyage_departure_places) pour affichage et ajout dans l'onglet Vols
        $departurePlaces = $laravelVoyage
            ? VoyageDeparturePlace::where('voyage_id', $laravelVoyage->id)->orderBy('sort_order')->orderBy('id')->get()
            : collect();
        // Vols associÃ©s par lieu (source unique : aj_tour_flights.departure_place_id) pour l'onglet Lieux de dÃ©part en lecture seule
        $departurePlaceFlightsFromTour = collect();
        try {
            if (\Illuminate\Support\Facades\Schema::connection('wp')->hasColumn('aj_tour_flights', 'departure_place_id')) {
                $rows = \Illuminate\Support\Facades\DB::connection('wp')->table('aj_tour_flights')->where('tour_id', $id)->whereNotNull('departure_place_id')->get();
                $departurePlaceFlightsFromTour = collect($rows)->groupBy('departure_place_id');
            }
        } catch (\Throwable $e) {
            // table or column may not exist yet
        }

        // Charger les dates disponibles
        $travelDates = TravelDate::getDatesForTour($id);

        try {
            $this->voyageAvailabilityService->syncFromWpDates($laravelVoyage, [
                'duration_days' => $lastDayNumber,
                'base_price' => $meta['base_price'] ?? $meta['adult_price'] ?? null,
                'sale_price' => $meta['sale_price'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('VoyageController@edit: availability sync failed', [
                'tour_id' => $id,
                'voyage_id' => $laravelVoyage->id,
                'error' => $e->getMessage(),
            ]);
        }

        $programJson = [];
        $programApiUrl = route('admin.circuits.voyages.program.save', ['id' => $id]);
        try {
            $programJson = $this->programJsonService->getProgram($id);
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@edit: getProgram failed', ['tour_id' => $id, 'error' => $e->getMessage()]);
        }

        // Total places calculÃ© Ã  partir des chambres (affichÃ© en lecture seule dans paramÃ¨tres gÃ©nÃ©raux)
        $totalPlacesVoyage = $this->computeTourTotalPlacesFromRooms($id);

        $voyageExtras = ($laravelVoyage && $this->voyageExtrasTableAvailable())
            ? VoyageExtra::query()->where('voyage_id', $laravelVoyage->id)->orderBy('sort_order')->orderBy('id')->get()
            : collect();

        \Log::info('EDIT PROGRAM DAYS COUNT', [
            'voyage_id' => $laravelVoyage->id,
            'tour_id' => $id,
            'count' => $programDays->count(),
            'day_numbers' => $programDays->pluck('day.day_number')->filter()->values()->toArray(),
        ]);

        $laravelVoyage->load('themes');
        $allVoyageThemes = VoyageTheme::query()->active()->ordered()->get();

        $destinationAreaLabel = $this->repository->getPrimaryDestinationAreaLabel($wpPost);
        if (! $destinationAreaLabel && $laravelVoyage && trim((string) ($laravelVoyage->destination ?? '')) !== '') {
            $destinationAreaLabel = trim((string) $laravelVoyage->destination);
        }
        $veDestinationQuick = $destinationAreaLabel;
        $discountRules = $laravelVoyage
            ? VoyageDiscountRule::query()->where('voyage_id', $laravelVoyage->id)->orderBy('sort_order')->orderBy('priority')->orderBy('id')->get()
            : collect();
        $cancellationTerms = $laravelVoyage
            ? VoyageCancellationTerm::query()->where('voyage_id', $laravelVoyage->id)->orderBy('sort_order')->orderBy('id')->get()
            : collect();
        $paymentMethodOptions = BusinessReferentialService::paymentMethods();
        $businessReferentials = BusinessReferentialService::allMerged();

        return view('admin.circuits.voyages.edit', compact('voyage', 'meta', 'gallery_csv', 'availableTaxonomies', 'assignedTaxonomies', 'locationsTree', 'selectedLocationIds', 'worldCountries', 'countryCitiesData', 'mergedCitiesByCode', 'programDays', 'activitiesCatalog', 'tourActivities', 'airlines', 'laravelVoyage', 'outboundFlight', 'inboundFlight', 'flightOptionsByType', 'flightOptionsWithIndex', 'nextFlightOptionIndex', 'lastDayNumber', 'heroImageUrl', 'tourHotel', 'tourHotels', 'otherTourHotelsForCopy', 'otherTourTitles', 'transferArrival', 'transferDeparture', 'transferArrivals', 'transferDepartures', 'suggestedArrivalFrom', 'suggestedArrivalTo', 'suggestedDepartureFrom', 'suggestedDepartureTo', 'tourHotelImageUrl', 'transferArrivalImageUrl', 'transferDepartureImageUrl', 'departurePlaces', 'departurePlaceFlightsFromTour', 'travelDates', 'programJson', 'programApiUrl', 'programDayHotelsTransfers', 'totalPlacesVoyage', 'voyageExtras', 'allVoyageThemes', 'veDestinationQuick', 'discountRules', 'cancellationTerms', 'paymentMethodOptions', 'businessReferentials'));
    }

    private function ensureFlightOptionsFromLegacy(int $voyageId, int $lastDayNumber): void
    {
        $out = \App\Models\VoyageFlight::where('voyage_id', $voyageId)->where('direction', 'outbound')->first();
        $in = \App\Models\VoyageFlight::where('voyage_id', $voyageId)->where('direction', 'inbound')->first();
        $items = [];
        if ($out) {
            $items[] = [
                'type' => 'outbound',
                'day_number' => 1,
                'from_city' => $out->from_city,
                'to_city' => $out->to_city,
                'departure_date' => $out->departure_date?->format('Y-m-d'),
                'airline_id' => $out->airline_id,
                'flight_number' => $out->flight_number,
                'cabin' => $out->cabin ?? 'economy',
                'baggage_cabin_kg' => $out->baggage_cabin_kg,
                'baggage_checkin_kg' => $out->baggage_checkin_kg,
                'is_tentative' => $out->is_tentative,
            ];
        }
        if ($in) {
            $items[] = [
                'type' => 'return',
                'day_number' => $lastDayNumber,
                'from_city' => $in->from_city,
                'to_city' => $in->to_city,
                'departure_date' => $in->departure_date?->format('Y-m-d'),
                'airline_id' => $in->airline_id,
                'flight_number' => $in->flight_number,
                'cabin' => $in->cabin ?? 'economy',
                'baggage_cabin_kg' => $in->baggage_cabin_kg,
                'baggage_checkin_kg' => $in->baggage_checkin_kg,
                'is_tentative' => $in->is_tentative,
            ];
        }
        if (! empty($items)) {
            $this->voyageFlightOptionService->syncOptions($voyageId, $items, $lastDayNumber);
        }
    }

    /**
     * Associer les pays du monde (config) aux locations WP (arbre) et produire les donnÃ©es pour le select + villes.
     *
     * @param  array  $worldCountries  [ code => nom ]
     * @param  array  $locationsTree  [ [ 'id', 'title', 'children' => [...] ], ... ]
     * @return array [ code => [ 'id' => wpId, 'title' => nom, 'cities' => [ [ 'id', 'title' ], ... ] ], ... ]
     */
    private function buildCountryCitiesData(array $worldCountries, array $locationsTree): array
    {
        $normalize = function (string $s): string {
            $s = mb_strtolower($s, 'UTF-8');
            $accents = ['à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y', 'œ' => 'oe'];

            return strtr($s, $accents);
        };
        $nameToCode = [];
        foreach ($worldCountries as $code => $name) {
            $key = $normalize($name);
            $nameToCode[$key] = $code;
        }
        $out = [];
        foreach ($locationsTree as $node) {
            $title = $node['title'] ?? '';
            $key = $normalize($title);
            $code = $nameToCode[$key] ?? null;
            if ($code !== null) {
                $children = $node['children'] ?? [];
                $cities = [];
                foreach ($children as $child) {
                    $cities[] = ['id' => $child['id'], 'title' => $child['title'] ?? ''];
                }
                $out[$code] = [
                    'id' => $node['id'],
                    'title' => $title,
                    'cities' => $cities,
                ];
            }
        }

        return $out;
    }

    /**
     * Construire la liste fusionnÃ©e Pays â†’ Villes (catalogue + WP) pour lâ€™UI.
     * Chaque ville a : id (WP ou null), title, needsCreate (true si pas encore en WP).
     *
     * @param  array  $worldCountries  [ code => nom ]
     * @param  array  $worldCities  [ code => [ 'Ville1', 'Ville2', ... ] ]
     * @param  array  $countryCitiesData  [ code => [ 'id', 'title', 'cities' => [ [ 'id', 'title' ], ... ] ] ]
     * @return array [ code => [ [ 'id' => int|null, 'title' => string, 'needsCreate' => bool ], ... ] ]
     */
    private function buildMergedCitiesByCode(array $worldCountries, array $worldCities, array $countryCitiesData): array
    {
        $normalize = function (string $s): string {
            $s = mb_strtolower($s, 'UTF-8');
            $accents = ['à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y', 'œ' => 'oe'];

            return strtr($s, $accents);
        };

        $merged = [];
        $codes = array_unique(array_merge(array_keys($worldCities), array_keys($countryCitiesData)));

        foreach ($codes as $code) {
            $wpCities = $countryCitiesData[$code]['cities'] ?? [];
            $wpByNorm = [];
            foreach ($wpCities as $c) {
                $wpByNorm[$normalize($c['title'])] = ['id' => $c['id'], 'title' => $c['title']];
            }
            $list = [];
            $seenNorm = [];

            // Dâ€™abord les villes du catalogue world_cities
            foreach ($worldCities[$code] ?? [] as $title) {
                $norm = $normalize($title);
                $seenNorm[$norm] = true;
                $wp = $wpByNorm[$norm] ?? null;
                $list[] = [
                    'id' => $wp ? $wp['id'] : null,
                    'title' => $title,
                    'needsCreate' => $wp === null,
                ];
            }
            // Puis les villes WP qui ne sont pas dans le catalogue
            foreach ($wpCities as $c) {
                $norm = $normalize($c['title']);
                if (! isset($seenNorm[$norm])) {
                    $list[] = ['id' => $c['id'], 'title' => $c['title'], 'needsCreate' => false];
                }
            }

            $merged[$code] = $list;
        }

        return $merged;
    }

    /**
     * Ensure a location exists in WP (country and optionally city). Used when user selects a city from the catalogue that is not yet in WordPress.
     *
     * POST country_code (required), city_name (optional).
     * Returns JSON { id, title }.
     */
    public function ensureLocation(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'country_code' => 'required|string|max:2',
                'city_name' => 'nullable|string|max:255',
            ]);
            $countryCode = strtoupper(trim((string) $request->input('country_code')));
            if (strlen($countryCode) !== 2) {
                return response()->json(['error' => 'country_code must be 2 characters'], 422);
            }
            $cityName = $request->input('city_name');

            if (empty($cityName) || trim((string) $cityName) === '') {
                $id = $this->repository->ensureCountryLocation($countryCode);
                $countries = config('countries', []);
                $title = $countries[$countryCode] ?? $countryCode;

                return response()->json(['id' => $id, 'title' => $title]);
            }

            $id = $this->repository->ensureCityLocation($countryCode, trim((string) $cityName));

            return response()->json(['id' => $id, 'title' => trim((string) $cityName)]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('VoyageController@ensureLocation failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'country_code' => $request->input('country_code'),
                'city_name' => $request->input('city_name'),
            ]);

            return response()->json([
                'error' => 'Impossible de créer la destination.',
                'message' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Sync hotels for tour: replace all by request data (tour_hotels[] or tour_hotel).
     * RÃ¨gle mÃ©tier : un seul hÃ´tel par voyage (premier Ã©lÃ©ment conservÃ© si plusieurs).
     *
     * @return int[] IDs des enregistrements TourHotel crÃ©Ã©s, dans lâ€™ordre dâ€™affichage (pour sync des chambres).
     */
    private function syncTourHotels(int $tourId, \Illuminate\Http\Request $request): array
    {
        $inputHotels = $request->has('tour_hotels') && is_array($request->input('tour_hotels'))
            ? array_values($request->input('tour_hotels'))
            : ($request->has('tour_hotel') ? [$request->input('tour_hotel', [])] : []);
        $existingHotels = TourHotel::where('tour_id', $tourId)->get()->keyBy('id');
        $keptHotelIds = [];
        $savedHotelIds = [];
        $sortOrder = 0;
        foreach ($inputHotels as $raw) {
            if (! is_array($raw)) {
                continue;
            }
            $checkInDay = isset($raw['check_in_day']) && $raw['check_in_day'] !== '' ? max(1, (int) $raw['check_in_day']) : null;
            $checkOutDay = isset($raw['check_out_day']) && $raw['check_out_day'] !== '' ? max(1, (int) $raw['check_out_day']) : null;
            $oldDayNumber = isset($raw['day_number']) && $raw['day_number'] !== '' ? max(1, (int) $raw['day_number']) : null;
            if (! $checkInDay && $oldDayNumber) {
                $checkInDay = $oldDayNumber;
            }
            if (! $checkOutDay && $oldDayNumber) {
                $checkOutDay = $oldDayNumber;
            }
            $checkInDay = $checkInDay ?: 1;
            $checkOutDay = max($checkInDay, $checkOutDay ?: 1);
            $hotelName = trim((string) ($raw['hotel_name'] ?? ''));
            $address = trim((string) ($raw['address'] ?? ''));
            $mealPlan = trim((string) ($raw['meal_plan'] ?? ''));
            $notes = trim((string) ($raw['notes'] ?? ''));
            $hasHotelPayload = $hotelName !== ''
                || $address !== ''
                || $mealPlan !== ''
                || $notes !== ''
                || ! empty($raw['image_id'])
                || ! empty($raw['is_optional']);
            if (! $hasHotelPayload) {
                continue;
            }
            $payload = [
                'tour_id' => $tourId,
                'check_in_day' => $checkInDay,
                'check_out_day' => $checkOutDay,
                'day_number' => $oldDayNumber ?? $checkInDay,
                'is_optional' => ! empty($raw['is_optional']) ? 1 : 0,
                'hotel_name' => $hotelName !== '' ? $hotelName : null,
                'stars' => isset($raw['stars']) && $raw['stars'] !== '' ? (int) $raw['stars'] : null,
                'address' => $address !== '' ? $address : null,
                'meal_plan' => $mealPlan !== '' ? $mealPlan : null,
                'notes' => $notes !== '' ? $notes : null,
                'image_id' => isset($raw['image_id']) && $raw['image_id'] !== '' ? (int) $raw['image_id'] : null,
                'sort_order' => $sortOrder++,
            ];
            $hotelId = isset($raw['id']) && $raw['id'] !== '' ? (int) $raw['id'] : 0;
            $hotel = $hotelId > 0 ? $existingHotels->get($hotelId) : null;
            if ($hotel) {
                $hotel->fill($payload);
                $hotel->save();
            } else {
                $hotel = TourHotel::create($payload);
            }
            $savedHotelIds[] = (int) $hotel->id;
            $keptHotelIds[] = (int) $hotel->id;
        }
        $hotelIdsToDelete = $existingHotels->keys()
            ->map(fn ($value) => (int) $value)
            ->diff($keptHotelIds)
            ->values();
        if ($hotelIdsToDelete->isNotEmpty()) {
            $roomIdsToDelete = TourHotelRoom::whereIn('tour_hotel_id', $hotelIdsToDelete->all())->pluck('id');
            if ($roomIdsToDelete->isNotEmpty()) {
                TourHotelRoomAvailability::whereIn('tour_hotel_room_id', $roomIdsToDelete->all())->delete();
            }
            TourHotelRoomAvailability::whereIn('tour_hotel_id', $hotelIdsToDelete->all())->delete();
            TourHotelRoom::whereIn('tour_hotel_id', $hotelIdsToDelete->all())->delete();
            TourHotel::whereIn('id', $hotelIdsToDelete->all())->delete();
        }

        return $savedHotelIds;
    }

    /**
     * Sync rooms for each tour hotel. Expects hotel ids in same order as tour_hotels request array.
     */
    private function syncTourHotelRooms(int $tourId, \Illuminate\Http\Request $request, array $hotelIdsOrdered): array
    {
        $tourHotelsInput = $request->input('tour_hotels', []);
        if (! is_array($tourHotelsInput)) {
            return [];
        }
        $tourHotelsInput = array_values($tourHotelsInput);
        $roomsByTourHotelId = [];
        $roomsByIndex = [];
        foreach ($tourHotelsInput as $idx => $maybeTourHotel) {
            if (! is_array($maybeTourHotel)) {
                continue;
            }
            $thId = isset($maybeTourHotel['id']) && $maybeTourHotel['id'] !== '' ? (int) $maybeTourHotel['id'] : 0;
            $roomList = isset($maybeTourHotel['rooms']) && is_array($maybeTourHotel['rooms']) ? $maybeTourHotel['rooms'] : [];
            if ($thId > 0) {
                $roomsByTourHotelId[$thId] = $roomList;
            }
            $roomsByIndex[$idx] = $roomList;
        }
        $savedRoomIdsByHotelIndex = [];
        foreach ($hotelIdsOrdered as $index => $tourHotelId) {
            $roomsInput = $roomsByTourHotelId[$tourHotelId] ?? ($roomsByIndex[$index] ?? ($tourHotelsInput[$index]['rooms'] ?? []));
            if (! is_array($roomsInput)) {
                $roomsInput = [];
            }
            $roomsInput = array_values($roomsInput);
            $existingRooms = TourHotelRoom::where('tour_hotel_id', $tourHotelId)->get()->keyBy('id');
            try {
                $firstRoom = null;
                foreach ($roomsInput as $rr) {
                    if (is_array($rr)) {
                        $firstRoom = $rr;
                        break;
                    }
                }
                \Log::info('VoyageController@syncTourHotelRooms - roomsInput', [
                    'tour_id' => $tourId,
                    'tour_hotel_id' => $tourHotelId,
                    'roomsInput_count' => count($roomsInput),
                    'first_room' => $firstRoom ? [
                        'id' => $firstRoom['id'] ?? null,
                        'room_type' => $firstRoom['room_type'] ?? null,
                        'room_count' => $firstRoom['room_count'] ?? null,
                        'capacity_adults' => $firstRoom['capacity_adults'] ?? null,
                        'capacity_children' => $firstRoom['capacity_children'] ?? null,
                        'capacity_total' => $firstRoom['capacity_total'] ?? null,
                        'supplement' => $firstRoom['supplement'] ?? null,
                        'is_active' => $firstRoom['is_active'] ?? null,
                    ] : null,
                ]);
            } catch (\Throwable $e) {
            }
            $keptRoomIds = [];
            $savedRoomIdsByRoomIndex = [];
            $sortOrder = 0;
            foreach ($roomsInput as $roomIndex => $r) {
                if (! is_array($r)) {
                    continue;
                }
                $roomId = isset($r['id']) && $r['id'] !== '' ? (int) $r['id'] : null;
                $roomType = $r['room_type'] ?? null;
                if ($roomType === null || $roomType === '') {
                    continue;
                }
                $adults = max(0, (int) ($r['capacity_adults'] ?? 0));
                $children = max(0, (int) ($r['capacity_children'] ?? 0));
                $rawCapTotal = $r['capacity_total'] ?? null;
                $rawCapTotalProvided = ! ($rawCapTotal === null || $rawCapTotal === '');
                $capTotalStored = $rawCapTotalProvided
                    ? max(0, (int) $rawCapTotal)
                    : max(0, $adults + $children);
                $payload = [
                    'tour_hotel_id' => $tourHotelId,
                    'room_type' => $roomType,
                    'room_label' => $r['room_label'] ?? null,
                    'room_code' => $r['room_code'] ?? null,
                    'room_count' => max(0, (int) ($r['room_count'] ?? 0)),
                    'capacity_adults' => $adults,
                    'capacity_children' => $children,
                    'capacity_total' => $capTotalStored,
                    'supplement' => max(0, (float) ($r['supplement'] ?? 0)),
                    'description' => $r['description'] ?? null,
                    'is_active' => (int) ($r['is_active'] ?? 0) === 1,
                    'sort_order' => $sortOrder++,
                    'is_default' => ! empty($r['is_default']),
                    'notes' => $r['notes'] ?? null,
                ];
                if ($roomId && $existingRooms->has($roomId)) {
                    $room = $existingRooms->get($roomId);
                    $room->fill($payload);
                    $room->save();
                } else {
                    $maxId = (int) TourHotelRoom::query()->max('id');
                    $payload['id'] = $maxId > 0 ? ($maxId + 1) : 1;
                    $room = TourHotelRoom::create($payload);
                }
                $keptRoomIds[] = (int) $room->id;
                $savedRoomIdsByRoomIndex[$roomIndex] = (int) $room->id;
            }
            $roomIdsToDelete = $existingRooms->keys()
                ->map(fn ($value) => (int) $value)
                ->diff($keptRoomIds)
                ->values();
            if ($roomIdsToDelete->isNotEmpty()) {
                TourHotelRoomAvailability::whereIn('tour_hotel_room_id', $roomIdsToDelete->all())->delete();
                TourHotelRoom::whereIn('id', $roomIdsToDelete->all())->delete();
            }
            $savedRoomIdsByHotelIndex[$index] = $savedRoomIdsByRoomIndex;
        }

        return $savedRoomIdsByHotelIndex;
    }

    /**
     * @see TourPlacesCalculator Logique unique partagÃ©e avec le formulaire (JS).
     */
    private function computeTourTotalPlacesFromRooms(int $tourId): int
    {
        try {
            $tourHotels = TourHotel::getAllForTour($tourId)->load('rooms');

            return TourPlacesCalculator::sumFromDatabase($tourHotels);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Sync transfers for tour: replace all by request (tour_transfer_arrivals / tour_transfer_departures or single).
     * Writes day_number, is_optional, sort_order. Default: arrival = day 1, departure = lastDayNumber.
     */
    private function syncTourTransfers(int $tourId, \Illuminate\Http\Request $request, int $lastDayNumber = 1): void
    {
        $transfers = [];

        // Nouveau format unifiÃ© : tour_transfers[]
        if ($request->has('tour_transfers') && is_array($request->input('tour_transfers'))) {
            foreach ($request->input('tour_transfers') as $transfer) {
                if (is_array($transfer) && (isset($transfer['from_label']) || isset($transfer['to_label']) || isset($transfer['pickup_time']))) {
                    $transfers[] = $transfer;
                }
            }
        }

        // Ancien format : tour_transfer_arrivals[] et tour_transfer_departures[] (compatibilitÃ©)
        $arrivals = [];
        if ($request->has('tour_transfer_arrivals') && is_array($request->input('tour_transfer_arrivals'))) {
            foreach ($request->input('tour_transfer_arrivals') as $arr) {
                if (is_array($arr)) {
                    $arrivals[] = $arr;
                }
            }
        } elseif ($request->has('tour_transfer_arrival')) {
            $arr = $request->input('tour_transfer_arrival', []);
            if (is_array($arr) && (isset($arr['from_label']) || isset($arr['to_label']) || isset($arr['pickup_time']))) {
                $arrivals[] = $arr;
            }
        }

        $departures = [];
        if ($request->has('tour_transfer_departures') && is_array($request->input('tour_transfer_departures'))) {
            foreach ($request->input('tour_transfer_departures') as $dep) {
                if (is_array($dep)) {
                    $departures[] = $dep;
                }
            }
        } elseif ($request->has('tour_transfer_departure')) {
            $dep = $request->input('tour_transfer_departure', []);
            if (is_array($dep) && (isset($dep['from_label']) || isset($dep['to_label']) || isset($dep['pickup_time']))) {
                $departures[] = $dep;
            }
        }

        // Si nouveau format utilisÃ©, ignorer l'ancien format
        if (! empty($transfers)) {
            TourTransfer::where('tour_id', $tourId)->delete();
            $sortOrder = 0;
            foreach ($transfers as $transfer) {
                $dayNumber = isset($transfer['day_number']) && $transfer['day_number'] !== '' ? max(1, (int) $transfer['day_number']) : 1;
                // Par dÃ©faut, on utilise 'arrival' comme direction (peut Ãªtre changÃ© plus tard si nÃ©cessaire)
                // Pour l'instant, on garde la compatibilitÃ© avec le modÃ¨le qui nÃ©cessite une direction
                TourTransfer::create([
                    'tour_id' => $tourId,
                    'direction' => TourTransfer::DIRECTION_ARRIVAL, // Par dÃ©faut, peut Ãªtre adaptÃ© selon besoin
                    'day_number' => $dayNumber,
                    'sort_order' => $sortOrder++,
                    'is_optional' => ! empty($transfer['is_optional']) ? 1 : 0,
                    'from_label' => $transfer['from_label'] ?? null,
                    'to_label' => $transfer['to_label'] ?? null,
                    'pickup_time' => $transfer['pickup_time'] ?? null,
                    'dropoff_time' => $transfer['dropoff_time'] ?? null,
                    'vehicle_type' => $transfer['vehicle_type'] ?? null,
                    'notes' => $transfer['notes'] ?? null,
                    'image_id' => isset($transfer['image_id']) && $transfer['image_id'] !== '' ? (int) $transfer['image_id'] : null,
                ]);
            }
        } else {
            // Ancien format : arrivals + departures
            TourTransfer::where('tour_id', $tourId)->delete();
            $sortOrder = 0;
            foreach ($arrivals as $arr) {
                $dayNumber = isset($arr['day_number']) && $arr['day_number'] !== '' ? max(1, (int) $arr['day_number']) : 1;
                TourTransfer::create([
                    'tour_id' => $tourId,
                    'direction' => TourTransfer::DIRECTION_ARRIVAL,
                    'day_number' => $dayNumber,
                    'sort_order' => $sortOrder++,
                    'is_optional' => ! empty($arr['is_optional']) ? 1 : 0,
                    'from_label' => $arr['from_label'] ?? null,
                    'to_label' => $arr['to_label'] ?? null,
                    'pickup_time' => $arr['pickup_time'] ?? null,
                    'dropoff_time' => $arr['dropoff_time'] ?? null,
                    'vehicle_type' => $arr['vehicle_type'] ?? null,
                    'notes' => $arr['notes'] ?? null,
                    'image_id' => isset($arr['image_id']) && $arr['image_id'] !== '' ? (int) $arr['image_id'] : null,
                ]);
            }
            $sortOrder = 0;
            foreach ($departures as $dep) {
                $dayNumber = isset($dep['day_number']) && $dep['day_number'] !== '' ? max(1, min((int) $dep['day_number'], $lastDayNumber)) : $lastDayNumber;
                TourTransfer::create([
                    'tour_id' => $tourId,
                    'direction' => TourTransfer::DIRECTION_DEPARTURE,
                    'day_number' => $dayNumber,
                    'sort_order' => $sortOrder++,
                    'is_optional' => ! empty($dep['is_optional']) ? 1 : 0,
                    'from_label' => $dep['from_label'] ?? null,
                    'to_label' => $dep['to_label'] ?? null,
                    'pickup_time' => $dep['pickup_time'] ?? null,
                    'dropoff_time' => $dep['dropoff_time'] ?? null,
                    'vehicle_type' => $dep['vehicle_type'] ?? null,
                    'notes' => $dep['notes'] ?? null,
                    'image_id' => isset($dep['image_id']) && $dep['image_id'] !== '' ? (int) $dep['image_id'] : null,
                ]);
            }
        }
    }

    /**
     * Sync departure places from request into Laravel table voyage_departure_places.
     * Single source of truth for admin: affichage et ajout depuis Laravel uniquement.
     */
    private function syncDeparturePlaces(int $tourId, \Illuminate\Http\Request $request): void
    {
        $voyage = Voyage::where('wp_post_id', $tourId)->first();
        if (! $voyage) {
            return;
        }

        $places = $request->input('departure_places', []);
        if (! is_array($places)) {
            $places = [];
        }
        uksort($places, fn ($a, $b) => (int) $a <=> (int) $b);

        $keptIds = [];
        $sortOrder = 0;
        foreach ($places as $placeIndex => $placeData) {
            if (! is_array($placeData)) {
                continue;
            }
            $placeName = trim($placeData['name'] ?? '');
            if ($placeName === '') {
                continue;
            }
            $placeId = isset($placeData['id']) && $placeData['id'] !== '' ? (int) $placeData['id'] : null;
            $data = [
                'name' => $placeName,
                'code' => ! empty($placeData['code']) ? trim($placeData['code']) : null,
                'is_active' => isset($placeData['is_active']) ? (bool) $placeData['is_active'] : true,
                'sort_order' => $sortOrder++,
                'price' => isset($placeData['price']) && $placeData['price'] !== '' ? (float) $placeData['price'] : null,
            ];
            try {
                if ($placeId) {
                    $place = VoyageDeparturePlace::where('voyage_id', $voyage->id)->where('id', $placeId)->first();
                    if ($place) {
                        $place->update($data);
                        $keptIds[] = $place->id;
                    } else {
                        $place = VoyageDeparturePlace::create(array_merge($data, ['voyage_id' => $voyage->id]));
                        $keptIds[] = $place->id;
                    }
                } else {
                    $place = VoyageDeparturePlace::create(array_merge($data, ['voyage_id' => $voyage->id]));
                    $keptIds[] = $place->id;
                }
            } catch (\Exception $e) {
                \Log::warning('VoyageController@syncDeparturePlaces: Error saving place', [
                    'name' => $placeName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $idsToDelete = VoyageDeparturePlace::where('voyage_id', $voyage->id)->whereNotIn('id', $keptIds)->pluck('id')->toArray();
        if (! empty($idsToDelete)) {
            \App\Models\VoyageFlightOption::where('voyage_id', $voyage->id)->whereIn('departure_place_id', $idsToDelete)->update(['departure_place_id' => null]);
            VoyageDeparturePlace::where('voyage_id', $voyage->id)->whereIn('id', $idsToDelete)->delete();
        }
    }

    /**
     * Extras rÃ©servation (workspace) : une ligne par option, liÃ©e au voyage Laravel.
     */
    private function syncVoyageExtras(Voyage $voyage, Request $request): void
    {
        if (! $this->voyageExtrasTableAvailable()) {
            return;
        }

        if (! $request->has('voyage_extras')) {
            return;
        }
        $rows = $request->input('voyage_extras', []);
        if (! is_array($rows)) {
            return;
        }
        if ($rows === []) {
            VoyageExtra::query()->where('voyage_id', $voyage->id)->delete();

            return;
        }
        $keptIds = [];
        $sort = 0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $data = [
                'name' => $name,
                'description' => trim((string) ($row['description'] ?? '')) ?: null,
                'price_adult' => isset($row['price_adult']) && $row['price_adult'] !== '' ? (float) $row['price_adult'] : 0.0,
                'price_child' => isset($row['price_child']) && $row['price_child'] !== '' ? (float) $row['price_child'] : 0.0,
                'is_active' => ! empty($row['is_active']),
                'sort_order' => $sort++,
                'extra_type' => trim((string) ($row['extra_type'] ?? '')) ?: null,
                'icon' => trim((string) ($row['icon'] ?? '')) ?: 'fa-plus-circle',
            ];
            $rowId = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null;
            if ($rowId) {
                $ex = VoyageExtra::query()->where('voyage_id', $voyage->id)->where('id', $rowId)->first();
                if ($ex) {
                    $ex->update($data);
                    $keptIds[] = $ex->id;
                } else {
                    $created = VoyageExtra::query()->create(array_merge($data, ['voyage_id' => $voyage->id]));
                    $keptIds[] = $created->id;
                }
            } else {
                $created = VoyageExtra::query()->create(array_merge($data, ['voyage_id' => $voyage->id]));
                $keptIds[] = $created->id;
            }
        }
        VoyageExtra::query()->where('voyage_id', $voyage->id)->whereNotIn('id', $keptIds)->delete();
    }

    private function voyageExtrasTableAvailable(): bool
    {
        static $available = null;

        if ($available !== null) {
            return $available;
        }

        try {
            $model = new VoyageExtra;
            $connection = $model->getConnectionName() ?: config('database.default');
            $available = Schema::connection($connection)->hasTable($model->getTable());
        } catch (\Throwable $e) {
            $available = false;
        }

        return $available;
    }

    /**
     * Sync travel dates for tour.
     */
    private function syncTravelDates(int $tourId, \Illuminate\Http\Request $request): Collection
    {
        $dates = $request->input('travel_dates', []);
        if (! is_array($dates)) {
            return collect();
        }
        $existingDates = TravelDate::where('travel_id', $tourId)->orderBy('date')->orderBy('id')->get();
        $existingById = $existingDates->keyBy('id');
        $existingByDate = $existingDates->keyBy(fn (TravelDate $travelDate) => optional($travelDate->date)->format('Y-m-d'));
        /** @var array<string, TravelDate> Même requête : évite plusieurs lignes aj_travel_dates pour la même date (clé unique travel_id+date). */
        $resolvedByDate = [];
        foreach ($existingByDate as $ymd => $td) {
            $resolvedByDate[$ymd] = $td;
        }
        $keptDateIds = [];
        $persistedDates = collect();
        foreach (array_values($dates) as $dateData) {
            if (! is_array($dateData)) {
                continue;
            }
            $date = trim((string) ($dateData['date'] ?? ''));
            if ($date === '') {
                continue;
            }
            $travelDateId = isset($dateData['id']) && $dateData['id'] !== '' ? (int) $dateData['id'] : 0;
            $travelDate = $travelDateId > 0 ? $existingById->get($travelDateId) : null;
            if (! $travelDate) {
                $travelDate = $resolvedByDate[$date] ?? null;
            }
            if (! $travelDate) {
                $travelDate = $existingByDate->get($date);
            }
            $payload = [
                'travel_id' => $tourId,
                'date' => $date,
                'is_active' => isset($dateData['is_active']) ? (bool) $dateData['is_active'] : true,
                'seats' => isset($dateData['seats']) && $dateData['seats'] !== '' ? max(0, (int) $dateData['seats']) : 0,
                'price_override' => isset($dateData['price_override']) && $dateData['price_override'] !== '' ? $dateData['price_override'] : null,
            ];
            if ($travelDate) {
                $travelDate->fill($payload);
                $travelDate->save();
            } else {
                $travelDate = TravelDate::create($payload);
            }
            $resolvedByDate[$date] = $travelDate->fresh();
            $keptDateIds[] = (int) $travelDate->id;
            $persistedDates->push($travelDate->fresh());
        }
        $persistedDates = $persistedDates->unique('id')->values();
        $dateIdsToDelete = $existingDates->pluck('id')
            ->map(fn ($value) => (int) $value)
            ->diff($keptDateIds)
            ->values();
        if ($dateIdsToDelete->isNotEmpty()) {
            TourHotelRoomAvailability::whereIn('travel_date_id', $dateIdsToDelete->all())->delete();
            TravelDate::whereIn('id', $dateIdsToDelete->all())->delete();
        }

        return $persistedDates->sortBy('date')->values();
    }

    private function syncTourHotelRoomDateAvailabilities(int $tourId, \Illuminate\Http\Request $request, array $hotelIdsOrdered, array $roomIdsByHotelIndex, Collection $travelDates): void
    {
        $tourHotelsInput = $request->input('tour_hotels', []);
        if (! is_array($tourHotelsInput)) {
            $tourHotelsInput = [];
        }
        $tourHotelsInput = array_values($tourHotelsInput);
        $travelDatesById = $travelDates->keyBy('id');
        $travelDatesByDate = $travelDates->keyBy(fn (TravelDate $travelDate) => optional($travelDate->date)->format('Y-m-d'));
        $validTravelDateIds = $travelDatesById->keys()->map(fn ($value) => (int) $value)->all();
        foreach ($hotelIdsOrdered as $hotelIndex => $tourHotelId) {
            $hotelInput = $tourHotelsInput[$hotelIndex] ?? [];
            $roomsInput = isset($hotelInput['rooms']) && is_array($hotelInput['rooms']) ? array_values($hotelInput['rooms']) : [];
            $savedRoomIds = $roomIdsByHotelIndex[$hotelIndex] ?? [];
            foreach ($roomsInput as $roomIndex => $roomInput) {
                if (! is_array($roomInput)) {
                    continue;
                }
                $roomId = $savedRoomIds[$roomIndex] ?? null;
                if (! $roomId) {
                    continue;
                }
                $roomModel = TourHotelRoom::find($roomId);
                if (! $roomModel) {
                    continue;
                }
                $existingAvailabilities = TourHotelRoomAvailability::where('tour_hotel_room_id', $roomId)->get()->keyBy('travel_date_id');
                $postedRows = isset($roomInput['date_availabilities']) && is_array($roomInput['date_availabilities']) ? array_values($roomInput['date_availabilities']) : [];
                $postedByTravelDateId = [];
                foreach ($postedRows as $availabilityInput) {
                    if (! is_array($availabilityInput)) {
                        continue;
                    }
                    $travelDate = null;
                    $travelDateId = isset($availabilityInput['travel_date_id']) && $availabilityInput['travel_date_id'] !== '' ? (int) $availabilityInput['travel_date_id'] : 0;
                    if ($travelDateId > 0) {
                        $travelDate = $travelDatesById->get($travelDateId);
                    }
                    if (! $travelDate) {
                        $dateKey = trim((string) ($availabilityInput['date'] ?? ''));
                        if ($dateKey !== '') {
                            $travelDate = $travelDatesByDate->get($dateKey);
                        }
                    }
                    if (! $travelDate) {
                        continue;
                    }
                    $postedByTravelDateId[(int) $travelDate->id] = $availabilityInput;
                }
                foreach ($travelDates as $travelDate) {
                    $travelDateId = (int) $travelDate->id;
                    $availabilityInput = $postedByTravelDateId[$travelDateId] ?? [];
                    $availability = $existingAvailabilities->get($travelDateId) ?: new TourHotelRoomAvailability([
                        'tour_id' => $tourId,
                        'tour_hotel_id' => $tourHotelId,
                        'tour_hotel_room_id' => $roomId,
                        'travel_date_id' => $travelDateId,
                    ]);
                    $defaults = $this->buildDefaultRoomDateAvailabilityPayload($roomModel);
                    $availableRooms = isset($availabilityInput['available_rooms']) && $availabilityInput['available_rooms'] !== ''
                        ? max(0, (int) $availabilityInput['available_rooms'])
                        : $defaults['available_rooms'];
                    $availablePlaces = isset($availabilityInput['available_places']) && $availabilityInput['available_places'] !== ''
                        ? max(0, (int) $availabilityInput['available_places'])
                        : max(0, $availableRooms * $defaults['capacity_per_room']);
                    $status = $this->normalizeRoomAvailabilityStatus($availabilityInput['status'] ?? $defaults['status']);
                    if (in_array($status, [TourHotelRoomAvailability::STATUS_FULL, TourHotelRoomAvailability::STATUS_CLOSED], true)) {
                        $availableRooms = 0;
                        $availablePlaces = 0;
                    }
                    $availability->fill([
                        'tour_id' => $tourId,
                        'tour_hotel_id' => $tourHotelId,
                        'tour_hotel_room_id' => $roomId,
                        'travel_date_id' => $travelDateId,
                        'available_rooms' => $availableRooms,
                        'available_places' => $availablePlaces,
                        'status' => $status,
                        'supplement' => isset($availabilityInput['supplement']) && $availabilityInput['supplement'] !== ''
                            ? max(0, (float) $availabilityInput['supplement'])
                            : $defaults['supplement'],
                    ]);
                    $availability->save();
                }
                if (! empty($validTravelDateIds)) {
                    TourHotelRoomAvailability::where('tour_hotel_room_id', $roomId)
                        ->whereNotIn('travel_date_id', $validTravelDateIds)
                        ->delete();
                } else {
                    TourHotelRoomAvailability::where('tour_hotel_room_id', $roomId)->delete();
                }
            }
        }
    }

    private function computeMaxPeopleFromTravelDates(Collection $travelDates, int $fallbackSeats): int
    {
        if ($travelDates->isEmpty()) {
            return $fallbackSeats;
        }

        $maxSeats = 0;
        foreach ($travelDates as $travelDate) {
            $seats = max(0, (int) ($travelDate->seats ?? 0));
            $maxSeats = max($maxSeats, $seats);
        }

        return $maxSeats > 0 ? $maxSeats : $fallbackSeats;
    }

    private function buildDefaultRoomDateAvailabilityPayload(TourHotelRoom $room): array
    {
        $capacityPerRoom = TourPlacesCalculator::effectiveCapacity(
            (int) ($room->capacity_total ?? 0),
            (int) ($room->capacity_adults ?? 0),
            (int) ($room->capacity_children ?? 0),
        );
        $availableRooms = max(0, (int) ($room->room_count ?? 0));

        return [
            'available_rooms' => $availableRooms,
            'capacity_per_room' => $capacityPerRoom,
            'available_places' => max(0, $availableRooms * $capacityPerRoom),
            'status' => $availableRooms > 0 ? TourHotelRoomAvailability::STATUS_AVAILABLE : TourHotelRoomAvailability::STATUS_FULL,
            'supplement' => max(0, (float) ($room->supplement ?? 0)),
        ];
    }

    private function normalizeRoomAvailabilityStatus(mixed $status): string
    {
        $status = strtolower(trim((string) $status));

        return in_array($status, TourHotelRoomAvailability::STATUSES, true)
            ? $status
            : TourHotelRoomAvailability::STATUS_AVAILABLE;
    }

    /**
     * Compatibilité : 1 date WP (aj_travel_dates) = 1 départ Laravel (departures).
     * Stocke wp_travel_date_id pour faire le lien avec les réservations existantes (travel_date_id).
     */
    private function syncDepartureRoomAllocations(Voyage $voyage, Request $request, Collection $travelDates, array $hotelIdsOrdered = []): void
    {
        $activeTravelDates = $travelDates
            ->filter(fn (TravelDate $travelDate) => (bool) ($travelDate->is_active ?? false) && $travelDate->date)
            ->values();

        if ($activeTravelDates->isEmpty()) {
            return;
        }

        $postedAllocations = $request->input('departure_allocations', []);
        $postedAllocations = is_array($postedAllocations) ? array_values($postedAllocations) : [];

        foreach ($activeTravelDates as $travelDate) {
            $departure = $this->resolveDepartureForTravelDate($voyage, $travelDate);
            if (! $departure) {
                continue;
            }

            $postedRow = $this->findPostedDepartureAllocationRow($postedAllocations, $departure, $travelDate);
            if (is_array($postedRow)) {
                $rows = $this->normalizePostedDepartureAllocationRooms($postedRow['rooms'] ?? [], $hotelIdsOrdered);
                $this->replaceDepartureRoomAllocations($departure, $rows !== [] ? $rows : $this->buildDefaultDepartureRoomAllocations((int) $departure->total_capacity));

                continue;
            }

            if (! $departure->roomAllocations()->exists()) {
                $this->replaceDepartureRoomAllocations($departure, $this->buildDefaultDepartureRoomAllocations((int) $departure->total_capacity));
            }
        }
    }

    private function resolveDepartureForTravelDate(Voyage $voyage, TravelDate $travelDate): ?Departure
    {
        $wpTravelDateId = (int) ($travelDate->id ?? 0);
        $date = optional($travelDate->date)?->format('Y-m-d');

        return Departure::query()
            ->where('voyage_id', (int) $voyage->id)
            ->where(function ($query) use ($wpTravelDateId, $date) {
                if ($wpTravelDateId > 0) {
                    $query->where('wp_travel_date_id', $wpTravelDateId);
                }

                if ($date !== null) {
                    $method = $wpTravelDateId > 0 ? 'orWhereDate' : 'whereDate';
                    $query->{$method}('start_date', $date);
                }
            })
            ->orderBy('id')
            ->first();
    }

    private function findPostedDepartureAllocationRow(array $postedAllocations, Departure $departure, TravelDate $travelDate): ?array
    {
        $departureId = (int) $departure->id;
        $travelDateId = (int) ($travelDate->id ?? 0);
        $date = optional($travelDate->date)?->format('Y-m-d');

        foreach ($postedAllocations as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rowDepartureId = isset($row['departure_id']) && $row['departure_id'] !== '' ? (int) $row['departure_id'] : 0;
            $rowTravelDateId = isset($row['travel_date_id']) && $row['travel_date_id'] !== '' ? (int) $row['travel_date_id'] : 0;
            $rowDate = trim((string) ($row['date'] ?? ''));

            if ($rowDepartureId > 0 && $rowDepartureId === $departureId) {
                return $row;
            }

            if ($rowTravelDateId > 0 && $travelDateId > 0 && $rowTravelDateId === $travelDateId) {
                return $row;
            }

            if ($date !== null && $rowDate !== '' && $rowDate === $date) {
                return $row;
            }
        }

        return null;
    }

    private function normalizePostedDepartureAllocationRooms(array $rows, array $hotelIdsOrdered = []): array
    {
        $normalized = [];
        $sortOrder = 0;

        foreach (array_values($rows) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $roomType = trim((string) ($row['room_type'] ?? ''));
            if ($roomType === '') {
                continue;
            }

            $hotelIndex = isset($row['hotel_index']) && $row['hotel_index'] !== '' ? max(0, (int) $row['hotel_index']) : null;
            $hotelId = isset($row['hotel_id']) && $row['hotel_id'] !== '' ? (int) $row['hotel_id'] : null;
            if ($hotelIndex !== null && array_key_exists($hotelIndex, $hotelIdsOrdered)) {
                $hotelId = (int) $hotelIdsOrdered[$hotelIndex];
            } elseif ($hotelId !== null && ! in_array($hotelId, $hotelIdsOrdered, true)) {
                $hotelId = null;
            }

            $normalized[] = [
                'hotel_id' => $hotelId,
                'room_type' => $roomType,
                'quantity' => max(0, (int) ($row['quantity'] ?? 0)),
                'capacity_per_room' => max(1, (int) ($row['capacity_per_room'] ?? 1)),
                'sort_order' => $sortOrder++,
            ];
        }

        return $normalized;
    }

    private function replaceDepartureRoomAllocations(Departure $departure, array $rows): void
    {
        DepartureRoomAllocation::query()->where('departure_id', (int) $departure->id)->delete();

        foreach ($rows as $row) {
            DepartureRoomAllocation::query()->create([
                'departure_id' => (int) $departure->id,
                'hotel_id' => $row['hotel_id'] ?? null,
                'room_type' => (string) $row['room_type'],
                'quantity' => max(0, (int) ($row['quantity'] ?? 0)),
                'capacity_per_room' => max(1, (int) ($row['capacity_per_room'] ?? 1)),
                'sort_order' => max(0, (int) ($row['sort_order'] ?? 0)),
            ]);
        }
    }

    private function buildDefaultDepartureRoomAllocations(int $capacity): array
    {
        $capacity = max(0, $capacity);
        if ($capacity === 0) {
            return [];
        }

        $rows = [];
        $doubleQty = intdiv($capacity, 2);
        if ($doubleQty > 0) {
            $rows[] = [
                'hotel_id' => null,
                'room_type' => 'Double',
                'quantity' => $doubleQty,
                'capacity_per_room' => 2,
                'sort_order' => 0,
            ];
        }

        if ($capacity % 2 !== 0) {
            $rows[] = [
                'hotel_id' => null,
                'room_type' => 'Single',
                'quantity' => 1,
                'capacity_per_room' => 1,
                'sort_order' => count($rows),
            ];
        }

        return $rows;
    }

    private function syncLaravelDeparturesFromWpTravelDates(Voyage $laravelVoyage, Collection $travelDates, int $lastDayNumber, Request $request): void
    {
        if (! $laravelVoyage) {
            return;
        }

        $basePrice = $request->input('base_price');
        if ($basePrice === null || $basePrice === '') {
            $basePrice = $request->input('adult_price');
        }

        $this->voyageAvailabilityService->syncFromTravelDates($laravelVoyage, $travelDates, [
            'duration_days' => $lastDayNumber,
            'base_price' => $basePrice,
            'sale_price' => $request->input('sale_price'),
        ]);
    }

    /**
     * Get available taxonomies for tours.
     */
    protected function getAvailableTaxonomies(): array
    {
        $taxonomies = ['language', 'languages', 'durations', 'st_tour_type'];
        $result = [];

        foreach ($taxonomies as $taxonomy) {
            try {
                $terms = \DB::connection('wp')
                    ->table('terms as t')
                    ->join('term_taxonomy as tt', 't.term_id', '=', 'tt.term_id')
                    ->where('tt.taxonomy', $taxonomy)
                    ->select('t.term_id', 't.name', 't.slug')
                    ->orderBy('t.name')
                    ->get();

                $result[$taxonomy] = $terms;
            } catch (\Exception $e) {
                \Log::warning("Taxonomy '$taxonomy' not found or error loading terms", [
                    'error' => $e->getMessage(),
                ]);
                $result[$taxonomy] = collect(); // Empty collection
            }
        }

        return $result;
    }

    /**
     * Get taxonomies assigned to a post.
     */
    protected function getPostTaxonomies(int $postId): array
    {
        $taxonomies = ['language', 'languages', 'durations', 'st_tour_type'];
        $result = [];

        foreach ($taxonomies as $taxonomy) {
            try {
                $termIds = \DB::connection('wp')
                    ->table('term_relationships as tr')
                    ->join('term_taxonomy as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
                    ->where('tr.object_id', $postId)
                    ->where('tt.taxonomy', $taxonomy)
                    ->pluck('tt.term_id')
                    ->toArray();

                $result[$taxonomy] = $termIds;
            } catch (\Exception $e) {
                \Log::warning("Error loading assigned terms for taxonomy '$taxonomy' on post $postId", [
                    'error' => $e->getMessage(),
                ]);
                $result[$taxonomy] = [];
            }
        }

        return $result;
    }

    public function syncDepartures(int $voyageId): JsonResponse
    {
        $voyage = Voyage::findOrFail($voyageId);

        Log::info('[AVAILABILITY_SYNC_MODAL] syncing departures from WP', [
            'voyage_id' => (int) $voyage->id,
            'wp_post_id' => (int) ($voyage->wp_post_id ?? 0),
        ]);

        $departures = $this->voyageAvailabilityService->syncFromWpDates($voyage);

        return response()->json(['success' => true, 'departures_count' => $departures->count()]);
    }

    public function syncDeparturesFromWp(int $wpPostId, int $voyageId): void
    {
        if ($voyageId <= 0) {
            return;
        }

        $voyage = Voyage::find($voyageId);
        if (! $voyage) {
            return;
        }

        if ($wpPostId > 0 && (int) ($voyage->wp_post_id ?? 0) !== $wpPostId) {
            $voyage->wp_post_id = $wpPostId;
            $voyage->save();
        }

        $this->voyageAvailabilityService->syncFromWpDates($voyage);
    }

    /**
     * Update tour in WordPress.
     */
    public function update(UpdateWpTourRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();

        // Option "Utiliser l'image principale comme image Ã  la une WP"
        if (! empty($validated['hero_use_as_thumbnail']) && ! empty($validated['hero_image_id'])) {
            $validated['thumbnail_id'] = $validated['hero_image_id'];
        }

        // Convertir gallery CSV en array
        if (! empty($validated['gallery_ids'])) {
            $validated['gallery_ids'] = array_filter(array_map('trim', explode(',', $validated['gallery_ids'])));
        }

        try {
            $this->repository->updateTour($id, $validated);

            // Programme par jours uniquement (aj_tour_days + aj_tour_day_activities). Plus d'Ã©dition tours_program.
            if ($request->has('programme_days')) {
                try {
                    $voyage = Voyage::firstOrCreate(
                        ['wp_post_id' => $id],
                        ['name' => optional($this->repository->getPost($id))->post_title ?? 'Tour', 'slug' => 'tour-'.$id]
                    );
                    $tour = $this->repository->getPost($id);

                    \Log::info('ProgrammeDays BEFORE SAVE', [
                        'voyage_id' => $voyage->id ?? null,
                        'tour_id' => $tour?->ID ?? null,
                        'request_count' => is_array($request->programme_days ?? null) ? count($request->programme_days) : 0,
                        'request_days' => $request->programme_days ?? [],
                    ]);

                    \Log::info('VoyageController@update - programme_days payload received', [
                        'tour_id' => $id,
                        'programme_days_count' => is_array($request->input('programme_days')) ? count($request->input('programme_days')) : null,
                        'programme_days_payload_len' => is_string($request->input('programme_days_payload')) ? strlen($request->input('programme_days_payload')) : null,
                    ]);
                    $this->syncProgrammeDaysAndActivities($id, $request);

                    \Log::info('ProgrammeDays AFTER SAVE', [
                        'voyage_id' => $voyage->id ?? null,
                        'tour_id' => $tour?->ID ?? null,
                        'wp_days_count' => \App\Models\Wp\TourDay::where('tour_id', $id)->count(),
                        'laravel_days_count' => \App\Models\TravelProgramDay::where('voyage_id', $voyage->id)->count(),
                        'laravel_day_numbers' => \App\Models\TravelProgramDay::where('voyage_id', $voyage->id)->orderBy('day_number')->pluck('day_number')->toArray(),
                    ]);

                    $dayCount = $this->programService->countDays($id);
                    $this->repository->updateTour($id, ['duration_day' => $dayCount]);
                } catch (\Throwable $e) {
                    \Log::error('VoyageController@update syncProgrammeDaysAndActivities failed', [
                        'tour_id' => $id,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            }

            $laravelVoyage = Voyage::firstOrCreate(
                ['wp_post_id' => $id],
                ['name' => optional($this->repository->getPost($id))->post_title ?? 'Tour', 'slug' => 'tour-'.$id]
            );

            $this->syncDeparturePlaces($id, $request);
            $this->resolveFlightOptionsDeparturePlaceIds($request, $laravelVoyage->id);

            $this->syncActivities($laravelVoyage, $request);
            $lastDayNumber = 1;
            try {
                $program = $this->programJsonService->getProgram($id);
                $lastDayNumber = max(1, count($program['program_days'] ?? []));
            } catch (\Throwable $e) {
                // keep 1
            }

            // Log TOUTES les clÃ©s de la requÃªte pour diagnostic
            \Log::info('VoyageController@update - Request keys received', [
                'tour_id' => $id,
                'has_flight_options' => $request->has('flight_options'),
                'has_flights' => $request->has('flights'),
                'all_keys' => array_keys($request->all()),
                'flight_options_count' => $request->has('flight_options') ? count($request->input('flight_options', [])) : 0,
            ]);

            // Diagnostic chambres: vÃ©rifier si le payload contient bien tour_hotels[*].rooms
            try {
                $tourHotels = $request->input('tour_hotels', []);
                $tourHotels = is_array($tourHotels) ? $tourHotels : [];
                $roomsCounts = [];
                foreach ($tourHotels as $hi => $hotelRow) {
                    if (! is_array($hotelRow)) {
                        $roomsCounts[$hi] = 0;

                        continue;
                    }
                    $rooms = $hotelRow['rooms'] ?? [];
                    $roomsCounts[$hi] = is_array($rooms) ? count($rooms) : 0;
                }
                \Log::info('VoyageController@update - tour_hotels rooms payload', [
                    'tour_id' => $id,
                    'tour_hotels_count' => count($tourHotels),
                    'roomsCounts' => $roomsCounts,
                ]);
            } catch (\Throwable $e) {
                \Log::warning('VoyageController@update - rooms payload diagnostics failed', [
                    'tour_id' => $id,
                    'message' => $e->getMessage(),
                ]);
            }

            $withoutFlight = $request->boolean('without_flight') || $request->input('without_flight') === '1';
            if ($withoutFlight) {
                try {
                    $this->voyageFlightOptionService->syncOptions($laravelVoyage->id, [], $lastDayNumber);
                    if ($laravelVoyage->wp_post_id) {
                        $this->voyageFlightOptionService->syncOptionsToWp($laravelVoyage->id, (int) $laravelVoyage->wp_post_id, $lastDayNumber);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('VoyageController@update clear flights (without_flight) failed', ['tour_id' => $id, 'message' => $e->getMessage()]);
                }
            } elseif ($request->has('flight_options') && is_array($request->input('flight_options'))) {
                try {
                    $flightOptionsInput = $request->input('flight_options');
                    \Log::debug('VoyageController@update flight_options payload FULL', [
                        'tour_id' => $id,
                        'voyage_id' => $laravelVoyage->id,
                        'count' => count($flightOptionsInput),
                        'all_options' => $flightOptionsInput, // Log TOUTES les options
                    ]);
                    $this->voyageFlightOptionService->syncOptions($laravelVoyage->id, $flightOptionsInput, $lastDayNumber);
                    \Log::info('VoyageController@update flight_options sync SUCCESS', ['tour_id' => $id]);
                } catch (\Throwable $e) {
                    \Log::error('VoyageController@update flight options failed', [
                        'tour_id' => $id,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            } elseif ($request->has('flights')) {
                try {
                    $this->voyageFlightService->syncFlights($laravelVoyage->id, $request->input('flights', []));
                    $this->ensureFlightOptionsFromLegacy($laravelVoyage->id, $lastDayNumber);
                    if ($laravelVoyage->wp_post_id) {
                        $this->voyageFlightOptionService->syncOptionsToWp($laravelVoyage->id, (int) $laravelVoyage->wp_post_id, $lastDayNumber);
                    }
                } catch (\Throwable $e) {
                    \Log::error('VoyageController@update voyage flights failed', ['tour_id' => $id, 'message' => $e->getMessage()]);
                    throw $e;
                }
            }

            // HÃ´tels puis chambres (IDs nouveaux aprÃ¨s delete/create) â€” un seul sync hÃ´tels
            $hotelIdsOrdered = $this->syncTourHotels($id, $request);
            $this->syncTourTransfers($id, $request, $lastDayNumber);

            // Lieux de départ : déjà synchronisés avant les vols (référencement des options vol).
            $this->syncTravelDates($id, $request);
            // Vérité terrain après écriture WP : la collection retournée par syncTravelDates peut être vide
            // si le POST ne repasse pas travel_dates (ex. soumission partielle), alors que aj_travel_dates contient déjà des lignes.
            $travelDates = TravelDate::getDatesForTour($id);

            \Log::info('AVAILABILITY_SYNC_CHECK', [
                'laravel_voyage_id' => $laravelVoyage->id,
                'wp_tour_post_id' => $id,
                'travel_dates_in_request' => is_array($request->input('travel_dates')) ? count($request->input('travel_dates')) : null,
                'wp_travel_dates_count_after_db_reload' => $travelDates->count(),
                'wp_travel_dates' => $travelDates->map(fn (TravelDate $td) => [
                    'id' => $td->id,
                    'date' => $td->date?->format('Y-m-d'),
                ])->values()->all(),
                'laravel_departures_count_before_sync' => Departure::query()->where('voyage_id', $laravelVoyage->id)->count(),
            ]);

            $maxPeople = $this->computeMaxPeopleFromTravelDates($travelDates, (int) $request->input('max_people', 0));
            $this->repository->updateTour($id, ['max_people' => $maxPeople, 'places' => $maxPeople]);
            $this->syncLaravelDeparturesFromWpTravelDates($laravelVoyage, $travelDates, $lastDayNumber, $request);
            $this->syncDepartureRoomAllocations($laravelVoyage, $request, $travelDates, $hotelIdsOrdered);

            \Log::info('AVAILABILITY_SYNC_CHECK', [
                'laravel_voyage_id' => $laravelVoyage->id,
                'wp_tour_post_id' => $id,
                'phase' => 'after_laravel_departure_sync',
                'laravel_departures_count' => Departure::query()->where('voyage_id', $laravelVoyage->id)->count(),
                'laravel_departures' => Departure::query()
                    ->where('voyage_id', $laravelVoyage->id)
                    ->orderBy('start_date')
                    ->get(['id', 'start_date', 'wp_travel_date_id', 'status'])
                    ->map(fn (Departure $d) => [
                        'id' => $d->id,
                        'start_date' => $d->start_date?->format('Y-m-d'),
                        'wp_travel_date_id' => $d->wp_travel_date_id,
                        'status' => $d->status,
                    ])
                    ->values()
                    ->all(),
            ]);

            $this->syncVoyageExtras($laravelVoyage, $request);
            // Toujours synchroniser les vols Laravel â†’ WP aprÃ¨s chaque enregistrement (pour que le plugin affiche les vols)
            if ($laravelVoyage && $laravelVoyage->wp_post_id) {
                try {
                    $this->voyageFlightOptionService->syncOptionsToWp(
                        $laravelVoyage->id,
                        (int) $laravelVoyage->wp_post_id,
                        $lastDayNumber ?? max(1, (int) ($validated['duration_day'] ?? 1))
                    );
                } catch (\Throwable $e) {
                    \Log::warning('VoyageController@update: syncOptionsToWp after save failed', [
                        'tour_id' => $id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $laravelVoyage->refresh();
            $this->syncVoyageDiscountRulesFromRequest($request, $laravelVoyage);
            $this->syncVoyageCancellationTermsFromRequest($request, $laravelVoyage);
            $this->syncVoyageLogisticsMetaFromRequest($request, $laravelVoyage);
            $this->syncVoyageThemesFromRequest($request, $laravelVoyage);

            return redirect()
                ->route('admin.circuits.voyages.edit', $id)
                ->with('success', 'Tour mis à jour avec succès dans WordPress ! Modifications visibles immédiatement.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Erreur lors de la mise à jour : '.$e->getMessage()]);
        }
    }

    /**
     * Sync programme days and day-activities from request.
     * - Accepts new days (empty id/day_id): creates them then renumbers.
     * - Deletes days not in the submitted list.
     * - Renumbers day_number 1..N according to submitted order.
     * Tables: aj_tour_days, aj_tour_day_activities.
     * - Also syncs day-level hotels and transfers (TravelProgramDay.hotel_id + transfers pivot).
     */
    protected function syncProgrammeDaysAndActivities(int $tourId, Request $request): void
    {
        $programmeDays = $request->input('programme_days', []);
        if (! is_array($programmeDays)) {
            return;
        }

        $orderedDayIds = [];
        foreach ($programmeDays as $dayRow) {
            $dayId = (int) ($dayRow['id'] ?? $dayRow['day_id'] ?? 0);
            if ($dayId <= 0) {
                $newDay = $this->programService->addDay($tourId);
                $dayId = $newDay->id;
            }
            $orderedDayIds[] = $dayId;
        }

        if (! empty($orderedDayIds)) {
            $this->programService->reorderAndRenumberDays($tourId, $orderedDayIds);
        }

        $submittedDayActivityIds = [];
        $submittedDayNumbers = [];
        foreach ($programmeDays as $i => $dayRow) {
            $dayId = (int) ($orderedDayIds[$i] ?? 0);
            if ($dayId <= 0) {
                continue;
            }
            $dayNumber = $i + 1;
            $submittedDayNumbers[] = $dayNumber;
            $dayTitle = isset($dayRow['day_title']) ? trim((string) $dayRow['day_title']) : null;
            $plainDescription = isset($dayRow['description']) ? trim((string) $dayRow['description']) : null;
            $notes = isset($dayRow['notes']) ? trim((string) $dayRow['notes']) : null;
            $this->programService->updateDay($dayId, [
                'mode' => $dayRow['mode'] ?? 'program',
                'day_title' => $dayTitle !== '' ? $dayTitle : null,
                'notes' => $notes !== '' ? $notes : null,
                'title' => $dayTitle !== '' ? $dayTitle : ($dayRow['title'] ?? null),
                'description' => $plainDescription !== '' ? $plainDescription : null,
            ]);

            $this->syncTravelProgramDayContent($tourId, $dayNumber, is_array($dayRow) ? $dayRow : []);

            $activities = $dayRow['activities'] ?? [];
            if (! is_array($activities)) {
                $this->syncDayHotelsAndTransfers($tourId, $dayId, is_array($dayRow) ? $dayRow : []);

                continue;
            }
            foreach ($activities as $k => $row) {
                $activityId = (int) ($row['activity_id'] ?? 0);
                if ($activityId <= 0) {
                    continue;
                }
                $dayActivityId = (int) ($row['day_activity_id'] ?? $row['id'] ?? 0);
                $isIncluded = $this->normalizeCheckboxValue($row['is_included'] ?? 0, 1);
                $isMandatory = $this->normalizeCheckboxValue($row['is_mandatory'] ?? 0, 0);

                if ($dayActivityId > 0) {
                    $this->programService->updateDayActivity($dayActivityId, [
                        'is_mandatory' => $isMandatory,
                        'is_included' => $isIncluded,
                        'custom_title' => $row['custom_title'] ?? null,
                        'custom_description' => $row['custom_description'] ?? null,
                        'sort_order' => $k,
                    ]);
                    $submittedDayActivityIds[] = $dayActivityId;
                } else {
                    $newDa = $this->programService->addActivityToDay($dayId, $activityId, [
                        'sort_order' => $k,
                        'is_included' => $isIncluded,
                        'is_mandatory' => $isMandatory,
                        'custom_title' => $row['custom_title'] ?? null,
                        'custom_description' => $row['custom_description'] ?? null,
                    ]);
                    $submittedDayActivityIds[] = $newDa->id;
                }
            }

            // Sync hotel & transfers for this day (TravelProgramDay, rÃ©solu depuis TourDay)
            $this->syncDayHotelsAndTransfers($tourId, $dayId, is_array($dayRow) ? $dayRow : []);
        }

        $voyage = Voyage::where('wp_post_id', $tourId)->first();
        if ($voyage) {
            TravelProgramDay::where('voyage_id', $voyage->id)
                ->when(! empty($submittedDayNumbers), function ($query) use ($submittedDayNumbers) {
                    $query->whereNotIn('day_number', $submittedDayNumbers);
                })
                ->delete();
        }

        $current = TourDayActivity::where('tour_id', $tourId)->get();
        foreach ($current as $da) {
            if (in_array($da->id, $submittedDayActivityIds, true)) {
                continue;
            }
            if ($da->is_mandatory) {
                continue;
            }
            $this->programService->removeDayActivity($da->id);
        }
    }

    /**
     * Sync inline "Activités" tab rows for a voyage (save-global strategy).
     * Stores rows in travel_day_items with type=activity and source=voyage_activities_tab.
     */
    protected function syncActivities(Voyage $voyage, UpdateWpTourRequest $request): void
    {
        $payload = $request->input('tour_activities', []);
        if (! is_array($payload)) {
            return;
        }

        $keptIds = [];
        $sortOrder = 0;

        foreach ($payload as $row) {
            if (! is_array($row)) {
                continue;
            }

            $activityId = (int) ($row['activity_id'] ?? 0);
            if ($activityId <= 0 || ! Activity::whereKey($activityId)->exists()) {
                continue;
            }

            $activity = Activity::find($activityId);
            $title = trim((string) ($row['title'] ?? ($activity?->title ?? 'Activité')));
            $pricingType = ($row['pricing_type'] ?? 'per_person') === 'fixed' ? 'fixed' : 'per_person';
            $unitPrice = (float) ($row['unit_price'] ?? 0);
            $unitPrice = $unitPrice < 0 ? 0 : round($unitPrice, 2);
            $childPrice = (float) ($row['child_price'] ?? 0);
            $childPrice = $childPrice < 0 ? 0 : round($childPrice, 2);
            $description = trim((string) ($row['description'] ?? ''));

            $itemData = [
                'voyage_id' => $voyage->id,
                'day_number' => 1,
                'start_day' => 1,
                'end_day' => 1,
                'nights' => 0,
                'type' => 'activity',
                'title' => $title !== '' ? $title : ($activity?->title ?? 'Activité'),
                'details' => $description !== '' ? $description : null,
                'included' => true,
                'price_delta_per_person' => $pricingType === 'per_person' ? (int) round($unitPrice * 100) : 0,
                'options_json' => [
                    'activity_id' => $activityId,
                    'pricing_type' => $pricingType,
                    'unit_price' => $unitPrice,
                    'child_price' => $childPrice,
                    'description' => $description,
                ],
                'meta_json' => [
                    'source' => 'voyage_activities_tab',
                ],
                'sort_order' => $sortOrder++,
            ];

            $itemId = (int) ($row['id'] ?? 0);
            $existing = $itemId > 0
                ? TravelDayItem::query()
                    ->where('id', $itemId)
                    ->where('voyage_id', $voyage->id)
                    ->where('type', 'activity')
                    ->first()
                : null;

            if ($existing) {
                $existing->fill($itemData);
                $existing->save();
                $keptIds[] = $existing->id;
            } else {
                $new = TravelDayItem::create($itemData);
                $keptIds[] = $new->id;
            }
        }

        TravelDayItem::query()
            ->where('voyage_id', $voyage->id)
            ->where('type', 'activity')
            ->where('meta_json->source', 'voyage_activities_tab')
            ->when(! empty($keptIds), fn ($query) => $query->whereNotIn('id', $keptIds))
            ->delete();
    }

    /**
     * Sync hotel and transfers for a specific day.
     * - $tourId: WP tour id (wp_posts.ID)
     * - $dayId: TourDay.id (aj_tour_days) envoyÃ© par le formulaire
     * - $dayRow: current programme_days[$i] request array
     * On rÃ©sout TourDay -> day_number puis TravelProgramDay par voyage_id + day_number.
     */
    protected function syncDayHotelsAndTransfers(int $tourId, int $dayId, array $dayRow): void
    {
        $tourDay = TourDay::where('tour_id', $tourId)->where('id', $dayId)->first();
        if (! $tourDay) {
            return;
        }
        $voyage = Voyage::where('wp_post_id', $tourId)->first();
        if (! $voyage) {
            return;
        }
        $day = TravelProgramDay::where('voyage_id', $voyage->id)->where('day_number', (int) $tourDay->day_number)->first();
        if (! $day) {
            return;
        }

        // Syncer l'hÃ´tel (0..1). Si hotel_id vide, lier au TourHotel crÃ©Ã© pour ce jour (ex. ajout depuis le drawer).
        $hotelId = ! empty($dayRow['hotel_id']) ? (int) $dayRow['hotel_id'] : null;
        if ($hotelId) {
            $hotel = TourHotel::find($hotelId);
            if ($hotel) {
                $day->update(['hotel_id' => $hotelId]);
            } else {
                $day->update(['hotel_id' => null]);
            }
        } else {
            // Chercher un hÃ´tel oÃ¹ le jour est dans la plage check-in -> check-out
            $dayNumber = (int) $tourDay->day_number;
            $hotelForDay = TourHotel::where('tour_id', $tourId)
                ->where(function ($query) use ($dayNumber) {
                    // Nouveau format : check_in_day / check_out_day
                    $query->where(function ($q) use ($dayNumber) {
                        $q->whereNotNull('check_in_day')
                            ->whereNotNull('check_out_day')
                            ->where('check_in_day', '<=', $dayNumber)
                            ->where('check_out_day', '>=', $dayNumber);
                    })
                    // CompatibilitÃ© ancien format : day_number
                        ->orWhere(function ($q) use ($dayNumber) {
                            $q->whereNull('check_in_day')
                                ->whereNull('check_out_day')
                                ->where('day_number', $dayNumber);
                        });
                })
                ->first();
            $day->update(['hotel_id' => $hotelForDay?->id]);
        }

        // Syncer les transferts (0..n)
        $transferIds = [];
        $transferInput = $dayRow['transfer_ids'] ?? '';
        if (is_string($transferInput) && ! empty($transferInput)) {
            // Format: "1,2,3" ou "1" ou ""
            $transferIds = array_filter(
                array_map('intval', array_map('trim', explode(',', $transferInput))),
                fn ($id) => $id > 0
            );
        } elseif (is_array($transferInput)) {
            $transferIds = array_filter(
                array_map('intval', $transferInput),
                fn ($id) => $id > 0
            );
        }

        // Valider que chaque transfert existe, puis syncer
        // Utiliser directement DB::connection('mysql') pour forcer la bonne connexion pour la table pivot
        // car la relation belongsToMany utilise la connexion du modÃ¨le liÃ© (TourTransfer sur 'wp')
        if (! empty($transferIds)) {
            $validIds = TourTransfer::whereIn('id', $transferIds)->pluck('id')->toArray();

            // Utiliser la connexion 'mysql' pour la table pivot
            $pivotTable = 'program_day_transfers';

            $programDayId = $day->id; // TravelProgramDay.id pour la table pivot
            // Supprimer les anciennes associations
            DB::connection('mysql')->table($pivotTable)
                ->where('program_day_id', $programDayId)
                ->delete();

            // InsÃ©rer les nouvelles associations
            if (! empty($validIds)) {
                $insertData = array_map(function ($transferId) use ($programDayId) {
                    return [
                        'program_day_id' => $programDayId,
                        'transfer_id' => $transferId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $validIds);

                DB::connection('mysql')->table($pivotTable)->insert($insertData);
            }
        } else {
            // Supprimer toutes les associations
            DB::connection('mysql')->table('program_day_transfers')
                ->where('program_day_id', $day->id)
                ->delete();
        }
    }

    protected function syncTravelProgramDayContent(int $tourId, int $dayNumber, array $dayRow): void
    {
        $voyage = Voyage::firstOrCreate(
            ['wp_post_id' => $tourId],
            ['name' => optional($this->repository->getPost($tourId))->post_title ?? 'Tour', 'slug' => 'tour-'.$tourId]
        );

        $dayTitle = trim((string) ($dayRow['day_title'] ?? $dayRow['title'] ?? ''));
        $description = trim((string) ($dayRow['description'] ?? ''));
        $notes = trim((string) ($dayRow['notes'] ?? ''));
        $contentHtml = trim((string) ($dayRow['content_html'] ?? ''));
        $dayType = TravelProgramDay::normalizeDayType((string) ($dayRow['day_type'] ?? 'visite'));

        $programDay = TravelProgramDay::firstOrNew([
            'voyage_id' => $voyage->id,
            'day_number' => $dayNumber,
        ]);

        $city = trim((string) ($dayRow['city'] ?? ''));

        $programDay->fill([
            'title' => $dayTitle !== '' ? $dayTitle : ('Jour '.$dayNumber),
            'city' => $city !== '' ? $city : null,
            'description' => $description !== '' ? $description : ($notes !== '' ? strip_tags($notes) : null),
            'content_html' => $contentHtml !== '' ? $contentHtml : null,
            'day_type' => $dayType,
        ]);
        $programDay->save();
    }

    protected function getOldProgrammeDaysInput(): array
    {
        $rows = session()->getOldInput('programme_days', []);

        return is_array($rows) ? array_values($rows) : [];
    }

    protected function extractProgrammeDayRelationsFromInput(array $rows): array
    {
        $out = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $transferIds = $row['transfer_ids'] ?? [];
            if (is_string($transferIds)) {
                $transferIds = array_values(array_filter(array_map('intval', array_map('trim', explode(',', $transferIds)))));
            } elseif (is_array($transferIds)) {
                $transferIds = array_values(array_filter(array_map('intval', $transferIds)));
            } else {
                $transferIds = [];
            }

            $out[$index] = [
                'hotel_id' => $row['hotel_id'] ?? '',
                'transfer_ids' => $transferIds,
            ];
        }

        return $out;
    }

    protected function buildProgrammeFormDaysFromPayload(array $rows, $activitiesCatalog): \Illuminate\Support\Collection
    {
        $catalogById = $activitiesCatalog instanceof \Illuminate\Support\Collection
            ? $activitiesCatalog->keyBy('id')
            : collect();

        $payload = collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->values();

        if ($payload->isEmpty()) {
            $payload = collect([[
                'mode' => 'program',
                'day_title' => 'Jour 1',
                'city' => '',
                'day_type' => 'visite',
                'content_html' => '',
                'description' => '',
                'notes' => '',
                'title' => 'Jour 1',
                'activities' => [],
            ]]);
        }

        return $payload->map(function (array $row, int $index) use ($catalogById) {
            $dayNumber = $index + 1;
            $dayTitle = trim((string) ($row['day_title'] ?? $row['title'] ?? ''));
            $title = trim((string) ($row['title'] ?? ''));
            $description = (string) ($row['description'] ?? '');
            $notes = (string) ($row['notes'] ?? '');
            $contentHtml = (string) ($row['content_html'] ?? '');
            $dayType = TravelProgramDay::normalizeDayType((string) ($row['day_type'] ?? 'visite'));

            $day = (object) [
                'id' => (int) ($row['id'] ?? $row['day_id'] ?? 0),
                'day_number' => $dayNumber,
                'mode' => ($row['mode'] ?? 'program') === 'free' ? 'free' : 'program',
                'day_title' => $dayTitle !== '' ? $dayTitle : ('Jour '.$dayNumber),
                'title' => $title !== '' ? $title : ($dayTitle !== '' ? $dayTitle : ('Jour '.$dayNumber)),
                'city' => trim((string) ($row['city'] ?? '')),
                'day_type' => $dayType,
                'content_html' => $contentHtml,
                'description' => $description,
                'notes' => $notes,
            ];

            $activities = collect($row['activities'] ?? [])
                ->filter(fn ($activityRow) => is_array($activityRow))
                ->values()
                ->map(function (array $activityRow, int $actIndex) use ($catalogById) {
                    $activityId = (int) ($activityRow['activity_id'] ?? 0);
                    $catalogActivity = $catalogById->get($activityId);

                    return (object) [
                        'id' => (int) ($activityRow['day_activity_id'] ?? $activityRow['id'] ?? 0),
                        'activity_id' => $activityId,
                        'sort_order' => (int) ($activityRow['sort_order'] ?? $actIndex),
                        'is_included' => $this->normalizeCheckboxValue($activityRow['is_included'] ?? 0, 1),
                        'is_mandatory' => $this->normalizeCheckboxValue($activityRow['is_mandatory'] ?? 0, 0),
                        'is_editable' => true,
                        'custom_title' => (string) ($activityRow['custom_title'] ?? ''),
                        'custom_description' => (string) ($activityRow['custom_description'] ?? ''),
                        'activity' => (object) [
                            'title' => $catalogActivity->title ?? ('Activité #'.$activityId),
                        ],
                    ];
                });

            return [
                'day' => $day,
                'activities' => $activities,
            ];
        });
    }

    protected function mergeProgrammeDaysWithLaravelData($programDays, $travelProgramDaysWithRelations): \Illuminate\Support\Collection
    {
        $days = $programDays instanceof \Illuminate\Support\Collection ? $programDays : collect();
        $travelDaysByNumber = $travelProgramDaysWithRelations instanceof \Illuminate\Support\Collection
            ? $travelProgramDaysWithRelations->keyBy('day_number')
            : collect();

        return $days->values()->map(function (array $entry) use ($travelDaysByNumber) {
            $day = $entry['day'];
            $travelDay = $travelDaysByNumber->get((int) ($day->day_number ?? 0));

            if ($travelDay) {
                $day->city = $travelDay->city;
                $day->day_type = $travelDay->day_type ?: 'visite';
                $day->content_html = $travelDay->content_html;
                if (empty($day->description)) {
                    $day->description = $travelDay->description;
                }
            } else {
                $day->city = $day->city ?? '';
                $day->day_type = $day->day_type ?? 'visite';
                $day->content_html = $day->content_html ?? '';
            }

            return [
                'day' => $day,
                'activities' => $entry['activities'] ?? collect(),
            ];
        });
    }

    /**
     * Parse duration_day meta to number of days. Avoids "5 hours" creating 5 days.
     */
    protected function parseDurationDays($value): int
    {
        if ($value === null || $value === '') {
            return 1;
        }
        $s = is_string($value) ? trim($value) : (string) $value;
        if (stripos($s, 'hour') !== false) {
            return 1;
        }
        $n = (int) $s;

        return $n >= 1 && $n <= 365 ? $n : 1;
    }

    /**
     * Normalize checkbox value (hidden 0 + checkbox 1 can send array [0,1]).
     */
    protected function normalizeCheckboxValue(mixed $value, int $default): int
    {
        if (is_array($value)) {
            $value = end($value);
        }

        return (int) ($value ?? $default);
    }

    /**
     * Add a program day (POST). Redirects back to edit with #program-days.
     */
    public function addProgramDay(int $id): RedirectResponse
    {
        try {
            $this->programService->addDay($id);

            return redirect()
                ->route('admin.circuits.voyages.edit', $id)
                ->with('success', 'Jour ajouté.')
                ->withFragment('program-days');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Impossible d\'ajouter le jour : '.$e->getMessage()]);
        }
    }

    /**
     * Delete a program day (POST). Renumbers remaining days. Confirmation expected via JS/form.
     */
    public function deleteProgramDay(int $id, int $dayId): RedirectResponse
    {
        try {
            $count = $this->programService->countDays($id);
            if ($count <= 1) {
                return back()->withErrors(['error' => 'Impossible de supprimer le dernier jour.']);
            }
            $this->programService->deleteDay($id, $dayId);
            $this->repository->updateTour($id, ['duration_day' => $this->programService->countDays($id)]);

            return redirect()
                ->route('admin.circuits.voyages.edit', $id)
                ->with('success', 'Jour supprimé.')
                ->withFragment('program-days');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Impossible de supprimer le jour : '.$e->getMessage()]);
        }
    }

    private function resolveFlightOptionsDeparturePlaceIds(Request $request, int $laravelVoyageId): void
    {
        $voyage = Voyage::find($laravelVoyageId);
        if (! $voyage) {
            return;
        }
        $places = VoyageDeparturePlace::query()
            ->where('voyage_id', $voyage->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $flightOptions = $request->input('flight_options', []);
        if (! is_array($flightOptions)) {
            return;
        }
        foreach ($flightOptions as $k => $row) {
            if (! is_array($row)) {
                continue;
            }
            $raw = $row['departure_place_id'] ?? '';
            if (is_string($raw) && preg_match('/^NEW_(\d+)$/', $raw, $m)) {
                $idx = (int) $m[1];
                $place = $places->get($idx);
                if ($place) {
                    $flightOptions[$k]['departure_place_id'] = (string) $place->id;
                }
            }
        }
        $request->merge(['flight_options' => $flightOptions]);
    }

    private function syncVoyageDiscountRulesFromRequest(Request $request, Voyage $voyage): void
    {
        if (! $request->has('discount_rules')) {
            return;
        }
        $rows = $request->input('discount_rules', []);
        if (! is_array($rows)) {
            return;
        }
        VoyageDiscountRule::query()->where('voyage_id', $voyage->id)->delete();
        $sort = 0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $sort++;
            $type = in_array($row['reduction_type'] ?? '', ['fixed', 'percent'], true) ? $row['reduction_type'] : 'percent';
            $scope = preg_match('/^[a-z_]+$/', (string) ($row['scope'] ?? '')) ? $row['scope'] : 'global';
            $cond = preg_match('/^[a-z_]+$/', (string) ($row['condition_type'] ?? '')) ? $row['condition_type'] : 'none';
            VoyageDiscountRule::query()->create([
                'voyage_id' => $voyage->id,
                'reduction_type' => $type,
                'scope' => $scope,
                'condition_type' => $cond,
                'condition_json' => isset($row['condition_json']) && is_array($row['condition_json']) ? $row['condition_json'] : null,
                'value' => isset($row['value']) && $row['value'] !== '' ? (float) $row['value'] : 0,
                'priority' => isset($row['priority']) && $row['priority'] !== '' ? (int) $row['priority'] : 100,
                'is_active' => ! empty($row['is_active']) && (string) $row['is_active'] !== '0',
                'sort_order' => $sort,
            ]);
        }
    }

    private function syncVoyageCancellationTermsFromRequest(Request $request, Voyage $voyage): void
    {
        if (! $request->has('cancellation_terms_submitted')) {
            return;
        }
        $rows = $request->input('cancellation_terms', []);
        if (! is_array($rows)) {
            return;
        }
        VoyageCancellationTerm::query()->where('voyage_id', $voyage->id)->delete();
        $sort = 0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $hasDays = isset($row['days_before_departure']) && $row['days_before_departure'] !== '' && $row['days_before_departure'] !== null;
            $hasRefund = isset($row['refund_percent']) && $row['refund_percent'] !== '' && $row['refund_percent'] !== null;
            $note = trim((string) ($row['note'] ?? ''));
            if (! $hasDays && ! $hasRefund && $note === '') {
                continue;
            }
            $sort++;
            $sortOrder = isset($row['sort_order']) && $row['sort_order'] !== '' ? (int) $row['sort_order'] : $sort;
            VoyageCancellationTerm::query()->create([
                'voyage_id' => $voyage->id,
                'days_before_departure' => isset($row['days_before_departure']) ? (int) $row['days_before_departure'] : 0,
                'refund_percent' => isset($row['refund_percent']) && $row['refund_percent'] !== '' ? (float) $row['refund_percent'] : 0,
                'is_active' => ! isset($row['is_active']) || (string) $row['is_active'] !== '0',
                'sort_order' => $sortOrder,
                'note' => isset($row['note']) ? (string) $row['note'] : null,
            ]);
        }
    }

    private function syncVoyageLogisticsMetaFromRequest(Request $request, Voyage $voyage): void
    {
        if (! $request->has('logistics_meta')) {
            return;
        }
        $meta = $request->input('logistics_meta');
        if (! is_array($meta)) {
            return;
        }
        $voyage->logistics_meta = $meta;
        $voyage->saveQuietly();
    }

    private function syncVoyageThemesFromRequest(Request $request, Voyage $laravelVoyage): void
    {
        $d = $request->input('destination');
        if (is_string($d) && trim($d) !== '') {
            $laravelVoyage->destination = trim($d);
            $laravelVoyage->saveQuietly();
        }

        $ids = $request->input('voyage_theme_ids', []);
        if (! is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_unique(array_filter(array_map(static fn ($v) => (int) $v, $ids), static fn ($id) => $id > 0)));
        $laravelVoyage->themes()->sync($ids);
        $laravelVoyage->refresh();
        app(VoyageThemeWpSyncService::class)->syncFromLaravelVoyage($laravelVoyage);
    }

    /**
     * Delete tour from WordPress.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->repository->deleteTour($id);

            return redirect()
                ->route('admin.circuits.voyages.index')
                ->with('success', 'Tour supprimé avec succès de WordPress !');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Erreur lors de la suppression : '.$e->getMessage()]);
        }
    }
}
