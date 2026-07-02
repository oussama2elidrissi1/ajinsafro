<?php

namespace App\Http\Controllers\Admin;

use App\Models\VoyageTheme;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWpTourRequest;
use App\Http\Requests\UpdateWpTourRequest;
use App\Models\Wp\WpPost;
use App\Models\Wp\Activity;
use App\Models\Wp\TourDay;
use App\Models\Wp\TourDayActivity;
use App\Models\Voyage;
use App\Models\VoyageExtra;
use App\Models\TravelProgramDay;
use App\Models\TravelDayItem;
use App\Models\Airline;
use App\Models\TourHotel;
use App\Models\TourHotelRoom;
use App\Models\TourHotelRoomAvailability;
use App\Models\TourTransfer;
use App\Models\TravelDeparturePlace;
use App\Models\TravelDepartureFlight;
use App\Models\VoyageDeparturePlace;
use App\Models\TravelDate;
use App\Models\Departure;
use App\Models\DepartureRoomAllocation;
use App\Services\AdminWpTourCatalogQuery;
use App\Services\BusinessReferentialService;
use App\Services\VoyageAvailabilityService;
use App\Services\VoyageFlightService;
use App\Services\VoyageFlightOptionService;
use App\Services\Wp\ProgramJsonService;
use App\Services\Wp\TourProgramService;
use App\Services\Wp\WpHeroImageService;
use App\Services\Wp\WpTourRepository;
use App\Support\TourPlacesCalculator;
use Database\Seeders\VoyageThemeSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class VoyageController extends Controller
{
    protected WpTourRepository $repository;

    protected TourProgramService $programService;

    protected VoyageFlightService $voyageFlightService;

    protected VoyageFlightOptionService $voyageFlightOptionService;

    protected ProgramJsonService $programJsonService;

    protected VoyageAvailabilityService $voyageAvailabilityService;

    private const V2_STEPS = [
        's-general',
        's-pricing',
        's-location',
        's-media',
        's-programme',
        's-information',
        's-taxonomies',
        's-flights',
        's-hotels',
        's-transfers',
        's-activities',
        's-extras',
        's-availability',
        's-logistics',
    ];

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
        $filters = AdminWpTourCatalogQuery::filtersFromRequest($request);
        $filterTourTypes = collect();
        $catalogSummary = [
            'total' => 0,
            'published' => 0,
            'draft' => 0,
            'private' => 0,
            'pending' => 0,
            'with_departures' => 0,
        ];

        try {
            $filterTourTypes = AdminWpTourCatalogQuery::tourTypeOptions()
                ->map(fn ($tt) => [
                    'term_id' => (int) ($tt['term_id'] ?? 0),
                    'name' => (string) ($tt['name'] ?? ''),
                ])
                ->filter(fn (array $tt) => $tt['term_id'] > 0 && $tt['name'] !== '')
                ->values();
            $catalogSummary = AdminWpTourCatalogQuery::catalogSummary($filters);
            $tours = AdminWpTourCatalogQuery::queryFromFilters($filters)
                ->paginate(20)
                ->appends($request->query());

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
                $thumbnailId = (int) $tour->getMeta('_thumbnail_id');
                $tour->image_url = $thumbnailId > 0 ? WpHeroImageService::getAttachmentUrl($thumbnailId) : null;
                return $tour;
            });

            // Enrichir la liste avec le slug Laravel (pour le bouton "Voir la page client").
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
            \Log::warning('VoyageController@index: WP connection failed', ['error' => $e->getMessage()]);
            $wpConnectionFailed = true;
            $wpCatalogErrorMessage = 'La liste des voyages ne peut pas ?tre charg?e pour le moment.';
            $tours = new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                20,
                \Illuminate\Pagination\Paginator::resolveCurrentPage(),
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
            );
        }

        return view('admin.circuits.voyages.index', compact(
            'tours',
            'wpConnectionFailed',
            'wpCatalogErrorMessage',
            'filterTourTypes',
            'catalogSummary',
            'filters'
        ));
    }

    /**
     * Show single tour (d?tail).
     */
    public function show(int $id): View
    {
        $wpPost = WpPost::tours()->where('ID', $id)->firstOrFail();
        
        // Cr?er un objet compatible avec les vues existantes
        $voyage = $wpPost;
        $voyage->name = $wpPost->post_title; // Alias pour compatibilit?
        $voyage->slug = $wpPost->post_name;
        $voyage->description = $wpPost->post_content;
        $voyage->updated_at = $wpPost->post_modified;
        $voyage->created_at = $wpPost->post_date;
        
        // Charger les metas (max_people et places sont calcul?s ? partir des chambres et enregistr?s ? la sauvegarde)
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

        // Programme par jours (aj_tour_days + activit?s) pour la timeline "Programme du circuit"
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
        foreach (BusinessReferentialService::paymentMethods() as $paymentMethod) {
            $paymentMetaKey = (string) ($paymentMethod['meta_key'] ?? '');
            if ($paymentMetaKey !== '') {
                $metaKeys[] = $paymentMetaKey;
            }
        }
        $metaKeys = array_values(array_unique($metaKeys));
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

        // Pas de mod?le Voyage Laravel tant que le tour WP n?â‚¬â„¢existe pas : null ?vite les acc?s ? des attributs manquants dans la vue partag?e create/edit.
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
        $otherTourHotelsForCopy = $this->getHotelsFromWordPress();
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
        $allVoyageThemes = $this->loadVoyageThemesForEdit();

        return view('admin.circuits.voyages.edit', compact(
            'voyage', 'meta', 'gallery_csv', 'availableTaxonomies', 'assignedTaxonomies', 'locationsTree', 'selectedLocationIds',
            'worldCountries', 'countryCitiesData', 'mergedCitiesByCode', 'programDays', 'activitiesCatalog', 'tourActivities', 'airlines',
            'laravelVoyage', 'outboundFlight', 'inboundFlight', 'flightOptionsByType', 'flightOptionsWithIndex', 'nextFlightOptionIndex', 'lastDayNumber',
            'heroImageUrl', 'tourHotel', 'tourHotels', 'otherTourHotelsForCopy', 'otherTourTitles', 'transferArrival', 'transferDeparture', 'transferArrivals', 'transferDepartures',
            'suggestedArrivalFrom', 'suggestedArrivalTo', 'suggestedDepartureFrom', 'suggestedDepartureTo',
            'tourHotelImageUrl', 'transferArrivalImageUrl', 'transferDepartureImageUrl',
            'departurePlaces', 'departurePlaceFlightsFromTour', 'travelDates', 'programJson', 'programApiUrl', 'programDayHotelsTransfers',
            'totalPlacesVoyage', 'voyageExtras', 'allVoyageThemes'
        ));
    }

    /**
     * V2: Show create form with new sidebar navigation design.
     */
    public function createV2(): View
    {
        $view = $this->create();
        $data = $view->getData();
        $data['v2StepStates'] = collect(self::V2_STEPS)
            ->mapWithKeys(fn ($stepId) => [$stepId => 'incomplete'])
            ->all();
        return view('admin.circuits.voyages.create_v2', $data);
    }

    /**
     * V2: Show edit form with new sidebar navigation design.
     */
    public function editV2(int $id): View
    {
        $view = $this->edit($id);
        $data = $view->getData();
        $data['v2StepStates'] = $this->buildV2StepStates($id);
        return view('admin.circuits.voyages.edit_v2', $data);
    }

    /**
     * V2: Enregistrement d'une étape avec un flux unifié.
     * Browser: POST -> Redirect -> GET (edit-v2#step)
     * AJAX legacy: JSON.
     */
    public function saveStepV2(Request $request, string $stepOrId, ?string $step = null): JsonResponse|RedirectResponse
    {
        $isAjax = $this->isV2AjaxSaveRequest($request);
        $routeStep = $step ?? $stepOrId;
        $routeId = $step === null ? 0 : (int) $stepOrId;

        $step = $this->normalizeV2StepId($routeStep);
        if ($step === null) {
            $message = "\u{00C9}tape inconnue.";
            if (! $isAjax) {
                return redirect()
                    ->to($this->buildV2SaveRedirectUrl($request, 0, 's-general'))
                    ->withErrors(['error' => $message])
                    ->withInput();
            }

            return response()->json([
                'success' => false,
                'ok' => false,
                'state' => 'error',
                'message' => $message,
            ], 404);
        }

        $wpPostId = $routeId > 0 ? $routeId : (int) $request->input('voyage_id', 0);

        $this->mergeProgrammeDaysPayloadIntoRequest($request);
        $this->normalizeStepCheckboxDefaults($request, $step);
        if ($step === 's-activities') {
            $this->normalizeV2ActivitiesPayloadIntoRequest($request);
        }

        $validator = Validator::make(
            $request->all(),
            $this->v2StepValidationRules($step),
            [],
            [
                'title' => 'titre',
                'slug' => 'slug',
                'post_status' => 'statut',
                'adult_price' => 'prix adulte',
                'child_price' => 'prix enfant',
                'min_price' => 'prix minimum',
                'travel_dates.*.date' => 'date de départ',
                'travel_dates.*.seats' => 'nombre de places',
            ]
        );
        $this->appendV2StepRequiredValidationErrors($validator, $step, $request);

        if ($validator->fails()) {
            if (! $isAjax) {
                return redirect()
                    ->to($this->buildV2SaveRedirectUrl($request, $wpPostId, $step))
                    ->withErrors($validator)
                    ->withInput();
            }

            return response()->json([
                'success' => false,
                'ok' => false,
                'state' => 'error',
                'step' => $step,
                'errors' => $validator->errors()->toArray(),
                'step_states' => $wpPostId > 0 ? $this->safeBuildV2StepStates($wpPostId, $step) : [],
                'message' => 'Completez les champs obligatoires de cette etape avant de continuer.',
            ], 422);
        }

        try {
            if ($wpPostId <= 0) {
                $wpPostId = $this->createV2DraftFromStep($request);
            }

            $this->persistV2Step($step, $wpPostId, $request);

            if (! $isAjax) {
                return redirect()
                    ->to($this->buildV2SaveRedirectUrl($request, $wpPostId, $step))
                    ->with('success', "\u{00C9}tape enregistr\u{00E9}e.");
            }

            return response()->json([
                'success' => true,
                'ok' => true,
                'state' => 'saved',
                'step' => $step,
                'voyage_id' => $wpPostId,
                'redirect_url' => route('admin.circuits.voyages.edit-v2', $wpPostId),
                'step_states' => $this->safeBuildV2StepStates($wpPostId, $step),
                'saved_at' => now()->toIso8601String(),
                'message' => "\u{00C9}tape enregistr\u{00E9}e.",
            ]);
        } catch (\Throwable $e) {
            if ($step === 's-activities') {
                Log::error('Activities Save Error', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'request' => $request->all(),
                ]);

                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'ok' => false,
                        'state' => 'error',
                        'step' => $step,
                        'error' => $e->getMessage(),
                        'message' => $this->buildV2StepSaveErrorMessage($e, $step),
                        'errors' => [
                            'server' => [$this->buildV2StepSaveErrorMessage($e, $step)],
                        ],
                    ], 500);
                }
            }

            Log::error('VoyageController@saveStepV2 failed', [
                'step' => $step,
                'tour_id' => $wpPostId,
                'request_keys' => array_values(array_map('strval', array_keys($request->all()))),
                'tour_activities_count' => is_array($request->input('tour_activities')) ? count($request->input('tour_activities')) : null,
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'error' => $e->getMessage(),
            ]);

            if (! $isAjax) {
                return redirect()
                    ->to($this->buildV2SaveRedirectUrl($request, $wpPostId, $step))
                    ->withErrors(['error' => "Erreur lors de l'enregistrement de l'\u{00E9}tape."])
                    ->withInput();
            }

            return response()->json([
                'success' => false,
                'ok' => false,
                'state' => 'error',
                'step' => $step,
                'message' => $this->buildV2StepSaveErrorMessage($e, $step),
                'errors' => [
                    'server' => [$this->buildV2StepSaveErrorMessage($e, $step)],
                ],
            ], 500);
        }
    }

    private function isV2AjaxSaveRequest(Request $request): bool
    {
        $accept = (string) $request->header('Accept', '');

        return $request->expectsJson()
            || $request->ajax()
            || str_contains($accept, 'application/json');
    }

    private function resolveV2RedirectStep(Request $request, string $fallbackStep): string
    {
        $raw = trim((string) $request->input('redirect_step', $fallbackStep));
        $normalized = $this->normalizeV2StepId($raw);

        return $normalized ?? $fallbackStep;
    }

    private function buildV2SaveRedirectUrl(Request $request, int $wpPostId, string $fallbackStep): string
    {
        $targetStep = $this->resolveV2RedirectStep($request, $fallbackStep);
        $baseUrl = $wpPostId > 0
            ? route('admin.circuits.voyages.edit-v2', $wpPostId)
            : route('admin.circuits.voyages.create-v2');

        return $baseUrl . '#' . $targetStep;
    }

    private function normalizeV2StepId(string $step): ?string
    {
        $step = trim($step);

        return in_array($step, self::V2_STEPS, true) ? $step : null;
    }

    private function mergeProgrammeDaysPayloadIntoRequest(Request $request): void
    {
        $payload = $request->input('programme_days_payload');
        if (! is_string($payload) || trim($payload) === '') {
            return;
        }

        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            return;
        }

        if (isset($decoded['programme_days']) && is_array($decoded['programme_days'])) {
            $decoded = $decoded['programme_days'];
        }

        $request->merge([
            'programme_days' => array_values(array_filter($decoded, 'is_array')),
        ]);
    }

    private function normalizeStepCheckboxDefaults(Request $request, string $step): void
    {
        $defaultsByStep = [
            's-general' => ['is_featured', 'is_group_deal'],
            's-media' => ['hero_use_as_thumbnail'],
            's-availability' => ['st_allow_cancel'],
        ];

        $defaults = $defaultsByStep[$step] ?? [];
        if ($defaults === []) {
            return;
        }

        $merged = [];
        foreach ($defaults as $key) {
            if (! $request->exists($key)) {
                $merged[$key] = 0;
            }
        }

        if ($merged !== []) {
            $request->merge($merged);
        }
    }

    private function v2StepValidationRules(string $step): array
    {
        return match ($step) {
            's-general' => [
                'title' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255',
                'content' => 'nullable|string',
                'excerpt' => 'nullable|string',
                'post_status' => 'nullable|in:publish,draft,pending,private',
                'destination' => 'nullable|string|max:255',
                'duration_text' => 'nullable|string|max:100',
                'is_featured' => 'nullable',
                'is_group_deal' => 'nullable',
            ],
            's-pricing' => [
                'adult_price' => 'nullable|numeric|min:0',
                'child_price' => 'nullable|numeric|min:0',
                'min_price' => 'nullable|numeric|min:0',
                'base_price' => 'nullable|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'infant_price' => 'nullable|numeric|min:0',
                'commission_adulte' => 'nullable|numeric|min:0',
                'commission_adulte_type' => 'nullable|in:fixed,percentage',
                'commission_enfant' => 'nullable|numeric|min:0',
                'commission_enfant_type' => 'nullable|in:fixed,percentage',
                'discount' => 'nullable|string',
                'discount_type' => 'nullable|string|max:100',
                'discount_by_people_type' => 'nullable|string|max:100',
                'calculator_discount_by_people_type' => 'nullable|string|max:100',
                'min_people' => 'nullable|integer|min:1',
                'max_people' => 'nullable|integer|min:0',
            ],
            's-location' => [
                'locations' => 'nullable|array',
                'locations.*' => 'integer|min:1',
                'address' => 'nullable|string|max:500',
                'contact_email' => 'nullable|email',
                'phone' => 'nullable|string|max:255',
                'fax' => 'nullable|string|max:255',
                'website' => 'nullable|string|max:255',
                'map_lat' => 'nullable|string|max:100',
                'map_lng' => 'nullable|string|max:100',
                'map_zoom' => 'nullable|integer|min:0',
                'map_type' => 'nullable|string|max:100',
            ],
            's-media' => [
                'thumbnail_id' => 'nullable|integer|min:0',
                'hero_image_id' => 'nullable|integer|min:0',
                'hero_gallery_ids' => 'nullable|string',
                'gallery_ids' => 'nullable|string',
                'video' => 'nullable|string|max:1000',
                'st_google_map' => 'nullable|string',
                'hero_use_as_thumbnail' => 'nullable',
            ],
            's-programme' => [
                'programme_days' => 'nullable|array',
                'programme_days.*.id' => 'nullable|integer',
                'programme_days.*.day_id' => 'nullable|integer',
                'programme_days.*.mode' => 'nullable|string|in:free,program',
                'programme_days.*.day_title' => 'nullable|string|max:255',
                'programme_days.*.title' => 'nullable|string|max:255',
                'programme_days.*.description' => 'nullable|string',
                'programme_days.*.notes' => 'nullable|string',
                'programme_days.*.activities' => 'nullable|array',
                'programme_days.*.activities.*.day_activity_id' => 'nullable|integer',
                'programme_days.*.activities.*.activity_id' => 'nullable|integer',
                'programme_days.*.activities.*.custom_title' => 'nullable|string|max:255',
                'programme_days.*.activities.*.custom_description' => 'nullable|string',
            ],
            's-information' => [
                'tours_include' => 'nullable|string',
                'tours_exclude' => 'nullable|string',
                'tours_highlight' => 'nullable|string',
                'tours_faq' => 'nullable|string',
                'tours_program_style' => 'nullable|string|max:100',
            ],
            's-taxonomies' => [
                'st_tour_type' => 'nullable|array',
                'st_tour_type.*' => 'integer|min:1',
                'durations' => 'nullable|array',
                'durations.*' => 'integer|min:1',
                'language' => 'nullable|array',
                'language.*' => 'integer|min:1',
                'languages' => 'nullable|array',
                'languages.*' => 'integer|min:1',
                'voyage_theme_ids' => 'nullable|array',
                'voyage_theme_ids.*' => 'integer|min:1',
            ],
            's-flights' => [
                'flight_options' => 'nullable|array',
                'flight_options.*.id' => 'nullable|integer',
                'flight_options.*.type' => 'nullable|string|in:outbound,return,segment',
                'flight_options.*.day_number' => 'nullable|integer|min:1',
                'flight_options.*.departure_place_id' => 'nullable',
                'flight_options.*.airline_id' => 'nullable|integer|min:1',
                'flight_options.*.cabin' => 'nullable|string|in:economy,business,first',
                'flight_options.*.from_city' => 'nullable|string|max:255',
                'flight_options.*.to_city' => 'nullable|string|max:255',
                'flight_options.*.departure_date' => 'nullable|date',
                'flight_options.*.departure_time' => 'nullable|string|max:20',
                'flight_options.*.arrival_date' => 'nullable|date',
                'flight_options.*.arrival_time' => 'nullable|string|max:20',
                'flight_options.*.flight_number' => 'nullable|string|max:50',
                'flight_options.*.baggage_cabin_kg' => 'nullable|integer|min:0',
                'flight_options.*.baggage_checkin_kg' => 'nullable|integer|min:0',
                'flight_options.*.notes' => 'nullable|string|max:2000',
            ],
            's-hotels' => [
                'tour_hotels' => 'nullable|array',
                'tour_hotels.*.id' => 'nullable|integer',
                'tour_hotels.*.hotel_name' => 'nullable|string|max:255',
                'tour_hotels.*.stars' => 'nullable|integer|min:0|max:5',
                'tour_hotels.*.address' => 'nullable|string|max:500',
                'tour_hotels.*.meal_plan' => 'nullable|string|max:255',
                'tour_hotels.*.notes' => 'nullable|string|max:2000',
                'tour_hotels.*.is_optional' => 'nullable|boolean',
                'tour_hotels.*.image_id' => 'nullable|integer|min:0',
                'tour_hotels.*.image_path' => 'nullable|string|max:512',
                'tour_hotels.*.rooms' => 'nullable|array',
                'tour_hotels.*.rooms.*.id' => 'nullable|integer',
                'tour_hotels.*.rooms.*.room_type' => 'nullable|string|max:100',
                'tour_hotels.*.rooms.*.room_count' => 'nullable|integer|min:0',
                'tour_hotels.*.rooms.*.capacity_adults' => 'nullable|integer|min:0',
                'tour_hotels.*.rooms.*.capacity_children' => 'nullable|integer|min:0',
                'tour_hotels.*.rooms.*.capacity_total' => 'nullable|integer|min:0',
                'tour_hotels.*.rooms.*.supplement' => 'nullable|numeric|min:0',
                'tour_hotels.*.rooms.*.is_active' => 'nullable',
                'tour_hotels.*.rooms.*.is_default' => 'nullable',
                'departure_allocations' => 'nullable|array',
                'departure_allocations.*.rooms' => 'nullable|array',
                'departure_allocations.*.rooms.*.room_type' => 'nullable|string|max:100',
                'departure_allocations.*.rooms.*.quantity' => 'nullable|integer|min:0',
                'departure_allocations.*.rooms.*.capacity_per_room' => 'nullable|integer|min:1',
                'departure_allocations.*.rooms.*.hotel_index' => 'nullable|integer|min:0',
                'departure_allocations.*.rooms.*.hotel_id' => 'nullable|integer',
            ],
            's-transfers' => [
                'tour_transfers' => 'nullable|array',
                'tour_transfers.*.id' => 'nullable|integer',
                'tour_transfers.*.day_number' => 'nullable|integer|min:1',
                'tour_transfers.*.is_optional' => 'nullable|boolean',
                'tour_transfers.*.from_label' => 'nullable|string|max:255',
                'tour_transfers.*.to_label' => 'nullable|string|max:255',
                'tour_transfers.*.pickup_time' => 'nullable|string|max:20',
                'tour_transfers.*.dropoff_time' => 'nullable|string|max:20',
                'tour_transfers.*.vehicle_type' => 'nullable|string|max:255',
                'tour_transfers.*.notes' => 'nullable|string|max:2000',
                'tour_transfers.*.image_id' => 'nullable|integer|min:0',
                'tour_transfers.*.image_path' => 'nullable|string|max:512',
                'tour_transfer_arrivals' => 'nullable|array',
                'tour_transfer_arrivals.*.day_number' => 'nullable|integer|min:1',
                'tour_transfer_arrivals.*.from_label' => 'nullable|string|max:255',
                'tour_transfer_arrivals.*.to_label' => 'nullable|string|max:255',
                'tour_transfer_arrivals.*.pickup_time' => 'nullable|string|max:20',
                'tour_transfer_arrivals.*.dropoff_time' => 'nullable|string|max:20',
                'tour_transfer_arrivals.*.vehicle_type' => 'nullable|string|max:255',
                'tour_transfer_arrivals.*.notes' => 'nullable|string|max:2000',
                'tour_transfer_arrivals.*.image_id' => 'nullable|integer|min:0',
                'tour_transfer_arrivals.*.image_path' => 'nullable|string|max:512',
                'tour_transfer_departures' => 'nullable|array',
                'tour_transfer_departures.*.day_number' => 'nullable|integer|min:1',
                'tour_transfer_departures.*.from_label' => 'nullable|string|max:255',
                'tour_transfer_departures.*.to_label' => 'nullable|string|max:255',
                'tour_transfer_departures.*.pickup_time' => 'nullable|string|max:20',
                'tour_transfer_departures.*.dropoff_time' => 'nullable|string|max:20',
                'tour_transfer_departures.*.vehicle_type' => 'nullable|string|max:255',
                'tour_transfer_departures.*.notes' => 'nullable|string|max:2000',
                'tour_transfer_departures.*.image_id' => 'nullable|integer|min:0',
                'tour_transfer_departures.*.image_path' => 'nullable|string|max:512',
            ],
            's-activities' => [
                'tour_activities' => 'nullable|array',
                'tour_activities.*.id' => 'nullable|integer',
                'tour_activities.*.activity_id' => 'nullable|integer|min:1',
                'tour_activities.*.group_uuid' => 'nullable|string|max:64',
                'tour_activities.*.title' => 'nullable|string|max:255',
                'tour_activities.*.display_title' => 'nullable|string|max:255',
                'tour_activities.*.description' => 'nullable|string|max:5000',
                'tour_activities.*.status' => 'nullable|string|in:included,optional,proposition',
                'tour_activities.*.activity_title' => 'nullable|string|max:255',
                'tour_activities.*.activity_type' => 'nullable|string|max:120',
                'tour_activities.*.day_number' => 'nullable|integer|min:1',
                'tour_activities.*.days' => 'nullable|array',
                'tour_activities.*.days.*' => 'nullable|integer|min:1',
                'tour_activities.*.visibility_mode' => 'nullable|string|in:single_day,multiple_days,all_days',
                'tour_activities.*.sort_order' => 'nullable|integer|min:0',
                'tour_activities.*.included' => 'nullable|boolean',
                'tour_activities.*.day_scope' => 'nullable|string|in:fixed,open,all',
                'tour_activities.*.start_time' => ['nullable', 'regex:/^([01]\\d|2[0-3]):[0-5]\\d$/'],
                'tour_activities.*.end_time' => ['nullable', 'regex:/^([01]\\d|2[0-3]):[0-5]\\d$/'],
                'tour_activities.*.pricing_type' => 'nullable|string|max:100',
                'tour_activities.*.unit_price' => 'nullable|numeric|min:0',
                'tour_activities.*.child_price' => 'nullable|numeric|min:0',
                'tour_activities.*.custom_price' => 'nullable|numeric|min:0',
            ],
            's-extras' => [
                'voyage_extras' => 'nullable|array',
                'voyage_extras.*.id' => 'nullable|integer',
                'voyage_extras.*.name' => 'nullable|string|max:255',
                'voyage_extras.*.description' => 'nullable|string|max:2000',
                'voyage_extras.*.price_adult' => 'nullable|numeric|min:0',
                'voyage_extras.*.price_child' => 'nullable|numeric|min:0',
                'voyage_extras.*.is_active' => 'nullable|boolean',
                'voyage_extras.*.extra_type' => 'nullable|string|max:64',
                'voyage_extras.*.icon' => 'nullable|string|max:80',
            ],
            's-availability' => [
                'tours_booking_period' => 'nullable|string|max:255',
                'st_booking_option_type' => 'nullable|string|max:255',
                'check_in' => 'nullable|string|max:20',
                'check_out' => 'nullable|string|max:20',
                'st_allow_cancel' => 'nullable',
                'st_cancel_percent' => 'nullable|integer|min:0|max:100',
                'st_cancel_number_day' => 'nullable|integer|min:0',
                'ical_url' => 'nullable|string|max:1000',
                'departure_places' => 'nullable|array',
                'departure_places.*.id' => 'nullable|integer',
                'departure_places.*.name' => 'nullable|string|max:255',
                'departure_places.*.code' => 'nullable|string|max:100',
                'departure_places.*.price' => 'nullable|numeric|min:0',
                'departure_places.*.is_active' => 'nullable|boolean',
                'travel_dates' => 'nullable|array',
                'travel_dates.*.id' => 'nullable|integer',
                'travel_dates.*.date' => 'nullable|date',
                'travel_dates.*.seats' => 'nullable|integer|min:0',
                'travel_dates.*.is_active' => 'nullable|boolean',
                'travel_dates.*.price_override' => 'nullable|numeric|min:0',
            ],
            's-logistics' => [
                'logistics_meta' => 'nullable|array',
                'logistics_meta.train.reference' => 'nullable|string|max:255',
                'logistics_meta.train.class' => 'nullable|string|max:255',
                'logistics_meta.train.notes' => 'nullable|string|max:2000',
                'logistics_meta.boat.route' => 'nullable|string|max:255',
                'logistics_meta.boat.company' => 'nullable|string|max:255',
                'logistics_meta.boat.notes' => 'nullable|string|max:2000',
                'logistics_meta.transport.type' => 'nullable|string|max:100',
                'logistics_meta.transport.capacity' => 'nullable|string|max:100',
                'logistics_meta.transport.notes' => 'nullable|string|max:2000',
            ],
            default => [],
        };
    }

    private function appendV2StepRequiredValidationErrors($validator, string $step, Request $request): void
    {
        $validator->after(function ($validator) use ($step, $request) {
            switch ($step) {
                case 's-general':
                    if (trim((string) $request->input('title', '')) === '') {
                        $validator->errors()->add('title', 'Le titre du voyage est obligatoire.');
                    }
                    break;

                case 's-pricing':
                    $priceKeys = ['adult_price', 'base_price', 'min_price', 'sale_price'];
                    $hasPrice = collect($priceKeys)->contains(
                        fn (string $key) => trim((string) $request->input($key, '')) !== ''
                    );
                    if (! $hasPrice) {
                        $validator->errors()->add('adult_price', 'Renseignez au moins un prix (adulte, base, minimum ou solde).');
                    }
                    break;

                case 's-location':
                    $locationIds = collect($this->normalizeArrayInput($request->input('locations')))
                        ->map(fn ($id) => (int) $id)
                        ->filter(fn (int $id) => $id > 0)
                        ->values();
                    if ($locationIds->isEmpty()) {
                        $validator->errors()->add('locations', 'Selectionnez au moins une destination.');
                    }
                    break;

                case 's-media':
                    $hasHero = (int) $request->input('hero_image_id', 0) > 0;
                    $hasThumbnail = (int) $request->input('thumbnail_id', 0) > 0;
                    $hasHeroGallery = $this->countCsvValues($request->input('hero_gallery_ids', '')) > 0;
                    $hasGallery = $this->countCsvValues($request->input('gallery_ids', '')) > 0;
                    if (! $hasHero && ! $hasThumbnail && ! $hasHeroGallery && ! $hasGallery) {
                        $validator->errors()->add('hero_image_id', 'Ajoutez au moins un media (hero, image a la une ou galerie).');
                    }
                    break;

                case 's-programme':
                    $programmeDays = collect($this->normalizeArrayInput($request->input('programme_days')))
                        ->filter(static fn ($value): bool => is_array($value))
                        ->values();
                    $hasProgrammeDay = $programmeDays->contains(function (array $day): bool {
                        return trim((string) ($day['day_title'] ?? '')) !== ''
                            || trim((string) ($day['title'] ?? '')) !== ''
                            || trim((string) ($day['description'] ?? '')) !== '';
                    });
                    if (! $hasProgrammeDay) {
                        $validator->errors()->add('programme_days', 'Ajoutez au moins un jour au programme.');
                    }
                    break;

                case 's-information':
                    if (trim((string) $request->input('tours_include', '')) === '') {
                        $validator->errors()->add('tours_include', 'Le bloc "Inclus" est obligatoire.');
                    }
                    if (trim((string) $request->input('tours_exclude', '')) === '') {
                        $validator->errors()->add('tours_exclude', 'Le bloc "Exclus" est obligatoire.');
                    }
                    break;

                case 's-taxonomies':
                    $taxonomyKeys = ['st_tour_type', 'durations', 'language', 'languages', 'voyage_theme_ids'];
                    $hasTaxonomy = collect($taxonomyKeys)->contains(function (string $key) use ($request): bool {
                        return collect($this->normalizeArrayInput($request->input($key)))
                            ->map(fn ($id) => (int) $id)
                            ->contains(fn (int $id) => $id > 0);
                    });
                    if (! $hasTaxonomy) {
                        $validator->errors()->add('st_tour_type', 'Selectionnez au moins une categorie (taxonomie ou theme).');
                    }
                    break;

                case 's-flights':
                    // Optional step in V2 workflow: no minimum flight option required.
                    break;

                case 's-hotels':
                    $hotels = collect($this->normalizeArrayInput($request->input('tour_hotels')))
                        ->filter(static fn ($value): bool => is_array($value));
                    $hasHotel = $hotels->contains(function (array $row): bool {
                        // Accept both manual hotels and hotels linked/imported from WordPress.
                        return trim((string) ($row['hotel_name'] ?? '')) !== ''
                            || (int) ($row['hotel_id'] ?? 0) > 0
                            || (int) ($row['source_hotel_id'] ?? 0) > 0
                            || (int) ($row['id'] ?? 0) > 0;
                    });
                    if (! $hasHotel) {
                        $validator->errors()->add('tour_hotels', 'Ajoutez au moins un hotel.');
                    }
                    break;

                case 's-transfers':
                    // Optional step in V2 workflow: no minimum transfer required.
                    break;

                case 's-activities':
                    // Optional step in V2 workflow: no minimum activity required.
                    break;

                case 's-extras':
                    // Optional step in V2 workflow: no minimum extra required.
                    break;

                case 's-availability':
                    $dates = collect($this->normalizeArrayInput($request->input('travel_dates')))
                        ->filter(static fn ($value): bool => is_array($value));
                    $hasDate = $dates->contains(function (array $row): bool {
                        return trim((string) ($row['date'] ?? '')) !== ''
                            && trim((string) ($row['seats'] ?? '')) !== '';
                    });
                    if (! $hasDate) {
                        $validator->errors()->add('travel_dates', 'Ajoutez au moins une date de depart avec le nombre de places.');
                    }
                    break;

                case 's-logistics':
                    // Optional step in V2 workflow: no minimum logistics information required.
                    break;
            }
        });
    }

    /**
     * @param mixed $value
     * @return array<int,mixed>
     */
    private function normalizeArrayInput(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    private function normalizeV2ActivitiesPayloadIntoRequest(Request $request): void
    {
        $raw = $request->input('tour_activities', []);
        $request->merge([
            'tour_activities' => $this->normalizeV2ActivitiesPayload($raw),
        ]);
    }

    /**
     * @param mixed $payload
     * @return array<int, array<string, mixed>>
     */
    private function normalizeV2ActivitiesPayload(mixed $payload): array
    {
        if (is_string($payload) && trim($payload) !== '') {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $payload = $decoded;
            }
        }

        if (! is_array($payload)) {
            return [];
        }

        $rows = [];
        foreach ($payload as $row) {
            if (! is_array($row)) {
                continue;
            }

            $idRaw = $row['id'] ?? null;
            $activityIdRaw = $row['activity_id'] ?? null;
            $groupUuidRaw = trim((string) ($row['group_uuid'] ?? ''));
            $dayNumberRaw = $row['day_number'] ?? null;
            $sortOrderRaw = $row['sort_order'] ?? null;
            $unitPriceRaw = $row['unit_price'] ?? null;
            $childPriceRaw = $row['child_price'] ?? null;
            $customPriceRaw = array_key_exists('custom_price', $row) ? $row['custom_price'] : $unitPriceRaw;
            $quantityRaw = $row['quantity'] ?? null;

            $id = is_numeric($idRaw) && (int) $idRaw > 0 ? (int) $idRaw : null;
            $activityId = is_numeric($activityIdRaw) && (int) $activityIdRaw > 0 ? (int) $activityIdRaw : null;
            $dayNumber = is_numeric($dayNumberRaw) && (int) $dayNumberRaw > 0 ? (int) $dayNumberRaw : null;
            $sortOrder = is_numeric($sortOrderRaw) && (int) $sortOrderRaw >= 0 ? (int) $sortOrderRaw : null;
            $unitPrice = is_numeric($unitPriceRaw) ? max(0, round((float) $unitPriceRaw, 2)) : null;
            $childPrice = is_numeric($childPriceRaw) ? max(0, round((float) $childPriceRaw, 2)) : null;
            $customPrice = is_numeric($customPriceRaw) ? max(0, round((float) $customPriceRaw, 2)) : null;
            $quantity = is_numeric($quantityRaw) ? max(1, (int) $quantityRaw) : 1;

            $displayTitle = trim((string) ($row['display_title'] ?? $row['title'] ?? ''));
            $title = $displayTitle;
            $description = trim((string) ($row['description'] ?? ''));
            $startTime = trim((string) ($row['start_time'] ?? ''));
            $endTime = trim((string) ($row['end_time'] ?? ''));
            $startTime = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $startTime) ? $startTime : null;
            $endTime = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $endTime) ? $endTime : null;

            $includedSource = array_key_exists('included', $row)
                ? $row['included']
                : (array_key_exists('is_optional', $row) ? ($this->normalizeCheckboxValue($row['is_optional'], 0) ? 0 : 1) : 1);
            $statusRaw = strtolower(trim((string) ($row['status'] ?? '')));
            if ($statusRaw === 'proposition') {
                $included = 0;
            } elseif ($statusRaw === 'optional') {
                $included = 0;
            } elseif ($statusRaw === 'included') {
                $included = 1;
            } else {
                $included = $this->normalizeCheckboxValue($includedSource, 1) ? 1 : 0;
            }
            $status = in_array($statusRaw, ['included', 'optional', 'proposition'], true)
                ? $statusRaw
                : ($included ? 'included' : 'optional');

            $dayScopeRaw = strtolower(trim((string) ($row['day_scope'] ?? 'fixed')));
            if ($dayScopeRaw === 'all') {
                $dayScopeRaw = 'open';
            }
            $dayScope = in_array($dayScopeRaw, ['fixed', 'open'], true) ? $dayScopeRaw : 'fixed';

            $visibilityRaw = strtolower(trim((string) ($row['visibility_mode'] ?? '')));
            if ($visibilityRaw === '') {
                $visibilityRaw = $dayScope === 'open' ? 'all_days' : 'single_day';
            }
            if (! in_array($visibilityRaw, ['single_day', 'multiple_days', 'all_days'], true)) {
                $visibilityRaw = 'single_day';
            }

            $daysRaw = $row['days'] ?? [];
            if (is_string($daysRaw)) {
                $decodedDays = json_decode($daysRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedDays)) {
                    $daysRaw = $decodedDays;
                } else {
                    $daysRaw = array_filter(array_map('trim', explode(',', $daysRaw)), static fn (string $v): bool => $v !== '');
                }
            }
            if (! is_array($daysRaw)) {
                $daysRaw = [];
            }

            $days = collect($daysRaw)
                ->map(fn ($d) => (int) $d)
                ->filter(fn (int $d) => $d > 0)
                ->unique()
                ->values()
                ->all();

            if ($dayNumber !== null && $dayNumber > 0 && ! in_array($dayNumber, $days, true)) {
                array_unshift($days, $dayNumber);
                $days = array_values(array_unique(array_map('intval', $days)));
            }

            if ($visibilityRaw === 'single_day') {
                $singleDay = (int) ($days[0] ?? $dayNumber ?? 1);
                $days = [$singleDay > 0 ? $singleDay : 1];
            } elseif ($visibilityRaw === 'multiple_days' && $days === []) {
                $fallbackDay = (int) ($dayNumber ?? 1);
                $days = [$fallbackDay > 0 ? $fallbackDay : 1];
            }

            $dayNumber = (int) ($days[0] ?? $dayNumber ?? 1);
            if ($dayNumber < 1) {
                $dayNumber = 1;
            }

            // Keep legacy fixed/open semantics for existing readers.
            if ($visibilityRaw === 'all_days') {
                $dayScope = 'open';
            } else {
                $dayScope = 'fixed';
            }

            $pricingTypeRaw = strtolower(trim((string) ($row['pricing_type'] ?? 'per_person')));
            $pricingType = $pricingTypeRaw === 'fixed' ? 'fixed' : 'per_person';

            if ($activityId === null && $title === '' && $description === '') {
                continue;
            }

            $rows[] = [
                'id' => $id,
                'activity_id' => $activityId,
                'group_uuid' => $groupUuidRaw !== '' ? $groupUuidRaw : null,
                'title' => $title !== '' ? $title : null,
                'display_title' => $title !== '' ? $title : null,
                'description' => $description !== '' ? $description : null,
                'activity_title' => trim((string) ($row['activity_title'] ?? '')) ?: null,
                'activity_type' => trim((string) ($row['activity_type'] ?? '')) ?: null,
                'status' => $status,
                'day_number' => $dayNumber,
                'days' => $days,
                'visibility_mode' => $visibilityRaw,
                'sort_order' => $sortOrder,
                'included' => $included,
                'day_scope' => $dayScope,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'pricing_type' => $pricingType,
                'unit_price' => $unitPrice,
                'child_price' => $childPrice,
                'custom_price' => $customPrice,
                'quantity' => $quantity,
            ];
        }

        return array_values($rows);
    }

    private function buildV2StepSaveErrorMessage(\Throwable $e, string $step): string
    {
        $message = trim($e->getMessage());

        if ($e instanceof \Illuminate\Database\QueryException) {
            if (str_contains($message, 'Duplicate entry') && str_contains($message, 'voyages.slug')) {
                return "Conflit de slug lors de la synchronisation du voyage Laravel. Rechargez la page et réessayez.";
            }
            if (str_contains($message, 'travel_day_items')) {
                return "Erreur SQL pendant l'enregistrement des activités. Vérifiez la structure de la table travel_day_items.";
            }

            return "Erreur SQL pendant l'enregistrement de l'étape {$step}.";
        }

        return $message !== '' ? $message : "Erreur lors de l'enregistrement de l'étape {$step}.";
    }

    private function countCsvValues(mixed $value): int
    {
        if (! is_string($value)) {
            return 0;
        }

        return count(array_filter(array_map('trim', explode(',', $value)), fn (string $v) => $v !== ''));
    }

    private function hasAnyNonEmptyScalar(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $entry) {
                if ($this->hasAnyNonEmptyScalar($entry)) {
                    return true;
                }
            }

            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return true;
        }

        return trim((string) $value) !== '';
    }

    private function createV2DraftFromStep(Request $request): int
    {
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            $title = 'Brouillon voyage ' . now()->format('Y-m-d H:i');
        }

        $slugInput = trim((string) $request->input('slug', ''));
        $slug = $slugInput !== '' ? $slugInput : Str::slug($title);
        if ($slug === '') {
            $slug = 'voyage-' . now()->format('YmdHis');
        }

        $tour = $this->repository->createTour([
            'title' => $title,
            'slug' => $slug,
            'content' => (string) $request->input('content', ''),
            'excerpt' => (string) $request->input('excerpt', ''),
            'post_status' => 'draft',
        ]);

        $this->syncLaravelVoyageFromRequest((int) $tour->ID, [
            'title' => $title,
            'slug' => $slug,
            'destination' => $request->input('destination'),
            'duration_text' => $request->input('duration_text'),
            'is_group_deal' => $this->normalizeCheckboxValue($request->input('is_group_deal', 0), 0),
        ]);

        return (int) $tour->ID;
    }

    private function persistV2Step(string $step, int $wpPostId, Request $request): void
    {
        $wpPayload = $this->extractWpPayloadForV2Step($step, $request);
        if ($wpPayload !== []) {
            $this->repository->updateTour($wpPostId, $wpPayload);
        }

        $laravelVoyage = null;
        if (in_array($step, ['s-general', 's-location', 's-taxonomies'], true)) {
            $laravelVoyage = $this->syncLaravelVoyageFromRequest($wpPostId, $this->extractLaravelPayloadForV2Step($step, $request));
        }

        switch ($step) {
            case 's-programme':
                if ($request->has('programme_days')) {
                    $this->syncProgrammeDaysAndActivities($wpPostId, $request);
                    $this->repository->updateTour($wpPostId, [
                        'duration_day' => $this->programService->countDays($wpPostId),
                    ]);
                }
                break;

            case 's-flights':
                $laravelVoyage = $laravelVoyage ?: $this->resolveOrCreateLaravelVoyage($wpPostId);
                $lastDayNumber = $this->resolveLastDayNumberForV2($wpPostId, $request);
                $hasFlightOptions = $request->exists('flight_options');
                $hasLegacyFlights = $request->exists('flights');

                if (! $hasFlightOptions && ! $hasLegacyFlights) {
                    break;
                }

                if ($hasFlightOptions) {
                    $flightOptionsInput = $request->input('flight_options');
                    $flightOptionsPayload = is_array($flightOptionsInput) ? $flightOptionsInput : [];
                    $this->voyageFlightOptionService->syncOptions($laravelVoyage->id, $flightOptionsPayload, $lastDayNumber);
                    if ($laravelVoyage->wp_post_id) {
                        $this->voyageFlightOptionService->syncOptionsToWp($laravelVoyage->id, (int) $laravelVoyage->wp_post_id, $lastDayNumber);
                    }
                    break;
                }

                if ($hasLegacyFlights) {
                    $legacyFlights = $request->input('flights', []);
                    $legacyFlights = is_array($legacyFlights) ? $legacyFlights : [];
                    $this->voyageFlightService->syncFlights($laravelVoyage->id, $legacyFlights);
                    $this->ensureFlightOptionsFromLegacy($laravelVoyage->id, $lastDayNumber);
                    if ($laravelVoyage->wp_post_id) {
                        $this->voyageFlightOptionService->syncOptionsToWp($laravelVoyage->id, (int) $laravelVoyage->wp_post_id, $lastDayNumber);
                    }
                }
                break;

            case 's-hotels':
                $laravelVoyage = $laravelVoyage ?: $this->resolveOrCreateLaravelVoyage($wpPostId);
                $hasTourHotels = $request->exists('tour_hotels');
                $hasDepartureAllocations = $request->exists('departure_allocations');
                if (! $hasTourHotels && ! $hasDepartureAllocations) {
                    break;
                }

                $hotelIdsOrdered = $hasTourHotels
                    ? $this->syncTourHotels($wpPostId, $request)
                    : TourHotel::query()
                        ->where('tour_id', $wpPostId)
                        ->orderBy('day_number')
                        ->orderBy('id')
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->values()
                        ->all();
                $roomIdsByHotelIndex = $hasTourHotels
                    ? $this->syncTourHotelRooms($wpPostId, $request, $hotelIdsOrdered)
                    : [];
                $travelDates = TravelDate::getDatesForTour($wpPostId);
                if ($hasTourHotels) {
                    $this->syncTourHotelRoomDateAvailabilities($wpPostId, $request, $hotelIdsOrdered, $roomIdsByHotelIndex, $travelDates);
                }
                if ($hasDepartureAllocations) {
                    $this->syncDepartureRoomAllocations($laravelVoyage, $request, $travelDates, $hotelIdsOrdered);
                }
                break;

            case 's-transfers':
                if (! $request->exists('tour_transfers') && ! $request->exists('tour_transfer_arrivals') && ! $request->exists('tour_transfer_departures')) {
                    break;
                }
                $this->syncTourTransfers($wpPostId, $request, $this->resolveLastDayNumberForV2($wpPostId, $request));
                break;

            case 's-activities':
                $activitiesPayload = $this->normalizeV2ActivitiesPayload($request->input('tour_activities', []));
                $request->merge(['tour_activities' => $activitiesPayload]);
                $laravelVoyage = $laravelVoyage ?: $this->resolveOrCreateLaravelVoyage($wpPostId);
                $this->syncActivities($laravelVoyage, $activitiesPayload);
                try {
                    $this->syncActivitiesTabToWpProgramme($wpPostId, $activitiesPayload);
                } catch (\Throwable $e) {
                    Log::warning('VoyageController@persistV2Step wp activities sync failed', [
                        'step' => 's-activities',
                        'tour_id' => $wpPostId,
                        'error' => $e->getMessage(),
                    ]);
                }
                break;

            case 's-extras':
                if (! $request->exists('voyage_extras')) {
                    break;
                }
                $laravelVoyage = $laravelVoyage ?: $this->resolveOrCreateLaravelVoyage($wpPostId);
                $this->syncVoyageExtras($laravelVoyage, $request);
                break;

            case 's-availability':
                $laravelVoyage = $laravelVoyage ?: $this->resolveOrCreateLaravelVoyage($wpPostId);
                $hasDeparturePlaces = $request->exists('departure_places');
                $hasTravelDates = $request->exists('travel_dates');
                $hasDepartureAllocations = $request->exists('departure_allocations');
                if (! $hasDeparturePlaces && ! $hasTravelDates && ! $hasDepartureAllocations) {
                    break;
                }

                if ($hasDeparturePlaces) {
                    $this->syncDeparturePlaces($wpPostId, $request);
                }

                $travelDates = $hasTravelDates
                    ? $this->syncTravelDates($wpPostId, $request)
                    : TravelDate::getDatesForTour($wpPostId);
                $travelDates = $travelDates->isNotEmpty() ? $travelDates : TravelDate::getDatesForTour($wpPostId);

                if ($hasTravelDates) {
                    $maxPeople = $this->computeMaxPeopleFromTravelDates($travelDates, (int) $request->input('max_people', 0));
                    $this->repository->updateTour($wpPostId, ['max_people' => $maxPeople, 'places' => $maxPeople]);
                    $lastDayNumber = $this->resolveLastDayNumberForV2($wpPostId, $request);
                    $this->syncLaravelDeparturesFromWpTravelDates($laravelVoyage, $travelDates, $lastDayNumber, $request);
                }

                if ($hasDepartureAllocations) {
                    $this->syncDepartureRoomAllocations($laravelVoyage, $request, $travelDates);
                }
                break;

            case 's-logistics':
                if (! $request->exists('logistics_meta')) {
                    break;
                }
                $laravelVoyage = $laravelVoyage ?: $this->resolveOrCreateLaravelVoyage($wpPostId);
                $this->syncVoyageLogisticsMeta($laravelVoyage, $request);
                break;
        }
    }

    private function extractWpPayloadForV2Step(string $step, Request $request): array
    {
        $fieldsByStep = [
            's-general' => ['title', 'slug', 'content', 'excerpt', 'post_status', 'duration_text', 'destination', 'is_featured'],
            's-pricing' => ['adult_price', 'child_price', 'min_price', 'base_price', 'sale_price', 'infant_price', 'commission_adulte', 'commission_adulte_type', 'commission_enfant', 'commission_enfant_type', 'discount', 'discount_type', 'discount_by_people_type', 'calculator_discount_by_people_type', 'min_people', 'max_people'],
            's-location' => ['locations', 'address', 'contact_email', 'phone', 'fax', 'website', 'map_lat', 'map_lng', 'map_zoom', 'map_type'],
            's-media' => ['thumbnail_id', 'hero_image_id', 'hero_gallery_ids', 'gallery_ids', 'video', 'st_google_map', 'hero_use_as_thumbnail'],
            's-information' => ['tours_include', 'tours_exclude', 'tours_highlight', 'tours_faq', 'tours_program_style'],
            's-taxonomies' => ['st_tour_type', 'durations', 'language', 'languages'],
            's-availability' => ['tours_booking_period', 'st_booking_option_type', 'check_in', 'check_out', 'st_allow_cancel', 'st_cancel_percent', 'st_cancel_number_day', 'ical_url'],
        ];

        $fields = $fieldsByStep[$step] ?? [];
        $payload = [];
        foreach ($fields as $field) {
            if (! $request->exists($field)) {
                continue;
            }
            $payload[$field] = $request->input($field);
        }

        if (array_key_exists('slug', $payload) && trim((string) $payload['slug']) === '') {
            $title = trim((string) ($payload['title'] ?? $request->input('title', '')));
            if ($title !== '') {
                $payload['slug'] = Str::slug($title);
            } else {
                unset($payload['slug']);
            }
        }

        return $payload;
    }

    private function extractLaravelPayloadForV2Step(string $step, Request $request): array
    {
        $fieldsByStep = [
            's-general' => ['title', 'slug', 'destination', 'duration_text', 'is_group_deal'],
            's-location' => ['destination'],
            's-taxonomies' => ['voyage_theme_ids'],
        ];

        $fields = $fieldsByStep[$step] ?? [];
        $payload = [];
        foreach ($fields as $field) {
            if ($request->exists($field)) {
                $payload[$field] = $request->input($field);
            }
        }

        if ($step === 's-general' && array_key_exists('slug', $payload) && trim((string) $payload['slug']) === '') {
            $title = trim((string) $request->input('title', ''));
            if ($title !== '') {
                $payload['slug'] = Str::slug($title);
            } else {
                unset($payload['slug']);
            }
        }

        return $payload;
    }

    private function resolveOrCreateLaravelVoyage(int $wpPostId): Voyage
    {
        return Voyage::firstOrCreate(
            ['wp_post_id' => $wpPostId],
            ['name' => optional($this->repository->getPost($wpPostId))->post_title ?? 'Tour', 'slug' => 'tour-' . $wpPostId]
        );
    }

    private function resolveLastDayNumberForV2(int $wpPostId, Request $request): int
    {
        $fromRequest = (int) $request->input('duration_day', 0);
        if ($fromRequest > 0) {
            return $fromRequest;
        }

        try {
            $program = $this->programJsonService->getProgram($wpPostId);
            $count = count($program['program_days'] ?? []);
            if ($count > 0) {
                return $count;
            }
        } catch (\Throwable $e) {
            // ignore and fallback below
        }

        $post = $this->repository->getPost($wpPostId);
        $metaDuration = $this->parseDurationDays($post?->getMeta('duration_day'));

        return max(1, $metaDuration);
    }

    private function syncVoyageLogisticsMeta(Voyage $voyage, Request $request): void
    {
        $payload = $request->input('logistics_meta', []);
        if (! is_array($payload)) {
            $payload = [];
        }

        $voyage->logistics_meta = $payload;
        $voyage->save();
    }

    private function buildV2StepStates(int $wpPostId): array
    {
        $post = $this->repository->getPost($wpPostId);
        if (! $post) {
            return collect(self::V2_STEPS)->mapWithKeys(fn ($stepId) => [$stepId => 'incomplete'])->all();
        }

        $laravelVoyage = Voyage::query()->where('wp_post_id', $wpPostId)->first();
        $multiLocations = $this->repository->parseMultiLocation($post->getMeta('multi_location'));
        $hasTaxonomies = collect($this->getPostTaxonomies($wpPostId))->flatten()->isNotEmpty();
        $hasThemes = $laravelVoyage && Schema::hasTable('voyage_voyage_theme') && $laravelVoyage->themes()->exists();
        $hasFlightOption = false;
        if ($laravelVoyage && Schema::hasTable('voyage_flight_options')) {
            try {
                $hasFlightOption = DB::table('voyage_flight_options')
                    ->where('voyage_id', $laravelVoyage->id)
                    ->exists();
            } catch (\Throwable $e) {
                Log::warning('VoyageController@buildV2StepStates flight option check failed', [
                    'voyage_id' => $laravelVoyage->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $states = [
            's-general' => trim((string) $post->post_title) !== '',
            's-pricing' => collect(['adult_price', 'base_price', 'min_price', 'sale_price'])
                ->contains(fn ($key) => trim((string) $post->getMeta($key)) !== ''),
            's-location' => ! empty($multiLocations),
            's-media' => trim((string) $post->getMeta('_thumbnail_id')) !== ''
                || trim((string) $post->getMeta('_tour_hero_image_id')) !== ''
                || trim((string) $post->getMeta('_tour_hero_gallery_ids')) !== ''
                || trim((string) $post->getMeta('gallery')) !== '',
            's-programme' => $this->safeV2StateCheck('s-programme', fn (): bool => TourDay::query()
                ->where('tour_id', $wpPostId)
                ->where(function ($query) {
                    $query->whereNotNull('day_title')->where('day_title', '!=', '')
                        ->orWhere(function ($q) {
                            $q->whereNotNull('title')->where('title', '!=', '');
                        })
                        ->orWhere(function ($q) {
                            $q->whereNotNull('description')->where('description', '!=', '');
                        });
                })
                ->exists()),
            's-information' => trim((string) $post->getMeta('tours_include')) !== ''
                && trim((string) $post->getMeta('tours_exclude')) !== '',
            's-taxonomies' => $hasTaxonomies || $hasThemes,
            's-flights' => $hasFlightOption,
            's-hotels' => $this->safeV2StateCheck('s-hotels', fn (): bool => TourHotel::query()
                ->where('tour_id', $wpPostId)
                ->whereNotNull('hotel_name')
                ->where('hotel_name', '!=', '')
                ->exists()),
            's-transfers' => $this->safeV2StateCheck('s-transfers', fn (): bool => TourTransfer::query()
                ->where('tour_id', $wpPostId)
                ->whereNotNull('from_label')
                ->where('from_label', '!=', '')
                ->whereNotNull('to_label')
                ->where('to_label', '!=', '')
                ->exists()),
            's-activities' => $laravelVoyage
                ? $this->safeV2StateCheck('s-activities', fn (): bool => TravelDayItem::query()
                    ->where('voyage_id', $laravelVoyage->id)
                    ->where('type', 'activity')
                    ->exists())
                : false,
            's-extras' => $laravelVoyage
                ? $this->safeV2StateCheck('s-extras', fn (): bool => VoyageExtra::query()
                    ->where('voyage_id', $laravelVoyage->id)
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->exists())
                : false,
            's-availability' => $this->safeV2StateCheck('s-availability', fn (): bool => TravelDate::query()
                ->where('travel_id', $wpPostId)
                ->where('is_active', true)
                ->whereNotNull('date')
                ->whereNotNull('seats')
                ->exists()),
            's-logistics' => $laravelVoyage ? $this->hasAnyNonEmptyScalar($laravelVoyage->logistics_meta ?? []) : false,
        ];

        return collect(self::V2_STEPS)
            ->mapWithKeys(fn ($stepId) => [$stepId => ! empty($states[$stepId]) ? 'complete' : 'incomplete'])
            ->all();
    }

    private function safeBuildV2StepStates(int $wpPostId, ?string $justSavedStep = null): array
    {
        try {
            return $this->buildV2StepStates($wpPostId);
        } catch (\Throwable $e) {
            Log::warning('VoyageController@safeBuildV2StepStates failed', [
                'tour_id' => $wpPostId,
                'step' => $justSavedStep,
                'error' => $e->getMessage(),
            ]);

            $fallback = collect(self::V2_STEPS)
                ->mapWithKeys(fn (string $stepId): array => [$stepId => 'incomplete'])
                ->all();

            if ($justSavedStep !== null && in_array($justSavedStep, self::V2_STEPS, true)) {
                $fallback[$justSavedStep] = 'complete';
            }

            return $fallback;
        }
    }

    private function safeV2StateCheck(string $step, callable $resolver): bool
    {
        try {
            return (bool) $resolver();
        } catch (\Throwable $e) {
            Log::warning('VoyageController@buildV2StepStates state check failed', [
                'step' => $step,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Store new tour in WordPress.
     */
    public function store(StoreWpTourRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // G?n?rer slug si vide
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Convertir gallery CSV en array
        if (!empty($validated['gallery_ids'])) {
            $validated['gallery_ids'] = array_filter(array_map('trim', explode(',', $validated['gallery_ids'])));
        }

        try {
            $tour = $this->repository->createTour($validated);
            $laravelVoyage = $this->syncLaravelVoyageFromRequest((int) $tour->ID, $validated);
            $this->syncVoyageLogisticsMeta($laravelVoyage, $request);
            
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

            if ($request->has('flights')) {
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
                ->withErrors(['error' => 'Erreur lors de la création : ' . $e->getMessage()]);
        }
    }

    /**
     * Show edit form.
     */
    public function edit(int $id): View
    {
        $wpPost = WpPost::tours()->where('ID', $id)->firstOrFail();
        
        // Cr?er un objet compatible avec les vues existantes
        $voyage = $wpPost;
        $voyage->name = $wpPost->post_title;
        $voyage->slug = $wpPost->post_name;
        $voyage->description = $wpPost->post_content;
        $voyage->accroche = $wpPost->post_excerpt;
        $voyage->updated_at = $wpPost->post_modified;
        $voyage->created_at = $wpPost->post_date;
        $voyage->status = $wpPost->post_status;
        
        // Charger TOUTES les metas Traveler (lecture compl?te)
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
            'commission_adulte' => $wpPost->getMeta('commission_adulte'),
            'commission_adulte_type' => $wpPost->getMeta('commission_adulte_type'),
            'commission_enfant' => $wpPost->getMeta('commission_enfant'),
            'commission_enfant_type' => $wpPost->getMeta('commission_enfant_type'),
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
            'is_meta_payment_gateway_st_cashplus' => $wpPost->getMeta('is_meta_payment_gateway_st_cashplus'),
            'is_meta_payment_gateway_st_wafacash' => $wpPost->getMeta('is_meta_payment_gateway_st_wafacash'),
            'is_meta_payment_gateway_st_bank_transfer' => $wpPost->getMeta('is_meta_payment_gateway_st_bank_transfer'),
            'is_meta_payment_gateway_st_cash_transfer' => $wpPost->getMeta('is_meta_payment_gateway_st_cash_transfer'),
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
        if (!empty($meta['gallery'])) {
            $gallery_csv = is_array($meta['gallery']) ? implode(',', $meta['gallery']) : $meta['gallery'];
        }
        
        // Charger les taxonomies disponibles
        $availableTaxonomies = $this->getAvailableTaxonomies();
        
        // Charger les taxonomies assign?es ? ce tour
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

        // Programme par jours (Laravel: aj_tour_days + activit?s). The Blade loop iterates over
        // loadProgram() (WP aj_tour_days). If duration_day meta is stale (e.g. 4) but travel_program_days
        // has 7 rows, ensureDaysExist() only used meta and WP stayed at 4 days ?â‚¬? the UI showed 4 cards.
        $laravelVoyage = Voyage::firstOrCreate(
            ['wp_post_id' => $id],
            ['name' => $wpPost->post_title ?? 'Tour', 'slug' => 'tour-' . $id]
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
                ->orderBy('day_number')
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
        // Cl? par index du jour (0, 1, 2...) pour correspondre ? $dayIndex dans la vue (programme_days)
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
                if (!empty($programDayIds) && \Illuminate\Support\Facades\Schema::connection('mysql')->hasTable('program_day_transfers')) {
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
        if (!empty($oldProgrammeDays)) {
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
        $heroId = !empty($meta['hero_image_id']) ? (int) $meta['hero_image_id'] : (!empty($meta['thumbnail_id']) ? (int) $meta['thumbnail_id'] : null);
        if ($heroId) {
            $heroImageUrl = WpHeroImageService::getAttachmentUrl($heroId);
        }

        // H?tel + Transferts (aj_tour_hotels, aj_tour_transfers) ?â‚¬? multi-row support
        $tourHotels = TourHotel::getAllForTour($id)->load([
            'rooms.dateAvailabilities' => fn ($query) => $query->orderBy('travel_date_id')->orderBy('id'),
        ]);
        $tourHotel = $tourHotels->first();
        // Liste d?â‚¬â„¢h?tels d?â‚¬â„¢autres voyages pour ? Choisir un h?tel existant ? (copie des donn?es)
        $otherTourHotelsForCopy = $this->getHotelsFromWordPress();
        $otherTourTitles = [];
        $transfers = TourTransfer::getForTour($id);
        $transferArrivals = $transfers['arrival'];
        $transferDepartures = $transfers['departure'];
        $transferArrival = $transferArrivals->first();
        $transferDeparture = $transferDepartures->first();
        // Valeurs sugg?r?es : transfert aller = a?roport d'arriv?e (vol aller to_city) ?â€ ? h?tel ; transfert retour = h?tel ?â€ ? a?roport de d?part (vol retour from_city)
        $suggestedArrivalFrom = $outboundFlight ? trim($outboundFlight->to_city ?? $outboundFlight->to_label ?? '') : '';
        $suggestedArrivalTo = $tourHotel ? trim($tourHotel->hotel_name ?? '') : '';
        $suggestedDepartureFrom = $tourHotel ? trim($tourHotel->hotel_name ?? '') : '';
        $suggestedDepartureTo = $inboundFlight ? trim($inboundFlight->from_city ?? $inboundFlight->from_label ?? '') : '';

        $tourHotelImageUrl = $tourHotel && $tourHotel->image_id ? WpHeroImageService::getAttachmentUrl((int) $tourHotel->image_id) : null;
        $transferArrivalImageUrl = $transferArrival && $transferArrival->image_id ? WpHeroImageService::getAttachmentUrl((int) $transferArrival->image_id) : null;
        $transferDepartureImageUrl = $transferDeparture && $transferDeparture->image_id ? WpHeroImageService::getAttachmentUrl((int) $transferDeparture->image_id) : null;

        // Lieux de depart : source Laravel (voyage_departure_places) pour affichage et edition dans l'etape Disponibilites
        $departurePlaces = $laravelVoyage
            ? VoyageDeparturePlace::where('voyage_id', $laravelVoyage->id)->orderBy('sort_order')->orderBy('id')->get()
            : collect();
        // Vols associ?s par lieu (source unique : aj_tour_flights.departure_place_id) pour l'onglet Lieux de d?part en lecture seule
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

        // Total places calcul? ? partir des chambres (affich? en lecture seule dans param?tres g?n?raux)
        $totalPlacesVoyage = $this->computeTourTotalPlacesFromRooms($id);

        $voyageExtras = ($laravelVoyage && $this->voyageExtrasTableAvailable())
            ? VoyageExtra::query()->where('voyage_id', $laravelVoyage->id)->orderBy('sort_order')->orderBy('id')->get()
            : collect();
        $allVoyageThemes = $this->loadVoyageThemesForEdit();

        \Log::info('EDIT PROGRAM DAYS COUNT', [
            'voyage_id' => $laravelVoyage->id,
            'tour_id' => $id,
            'count' => $programDays->count(),
            'day_numbers' => $programDays->pluck('day.day_number')->filter()->values()->toArray(),
        ]);

        return view('admin.circuits.voyages.edit', compact('voyage', 'meta', 'gallery_csv', 'availableTaxonomies', 'assignedTaxonomies', 'locationsTree', 'selectedLocationIds', 'worldCountries', 'countryCitiesData', 'mergedCitiesByCode', 'programDays', 'activitiesCatalog', 'tourActivities', 'airlines', 'laravelVoyage', 'outboundFlight', 'inboundFlight', 'flightOptionsByType', 'flightOptionsWithIndex', 'nextFlightOptionIndex', 'lastDayNumber', 'heroImageUrl', 'tourHotel', 'tourHotels', 'otherTourHotelsForCopy', 'otherTourTitles', 'transferArrival', 'transferDeparture', 'transferArrivals', 'transferDepartures', 'suggestedArrivalFrom', 'suggestedArrivalTo', 'suggestedDepartureFrom', 'suggestedDepartureTo', 'tourHotelImageUrl', 'transferArrivalImageUrl', 'transferDepartureImageUrl', 'departurePlaces', 'departurePlaceFlightsFromTour', 'travelDates', 'programJson', 'programApiUrl', 'programDayHotelsTransfers', 'totalPlacesVoyage', 'voyageExtras', 'allVoyageThemes'));
    }

    /**
     * Source des h?tels "Ajouter comme nouveau s?jour" depuis WordPress (post_type=st_hotel).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function getHotelsFromWordPress(): Collection
    {
        try {
            $postsTable = (new \App\Models\WpPost())->getTable();
            $hotelsTable = (new \App\Models\StHotel())->getTable();
            $postmetaTable = (new \App\Models\WpPostmeta())->getTable();

            $metaSub = DB::table($postmetaTable . ' as pm')
                ->select(
                    'pm.post_id',
                    DB::raw("MAX(CASE WHEN pm.meta_key = 'address' THEN pm.meta_value END) as meta_address"),
                    DB::raw("MAX(CASE WHEN pm.meta_key = 'location' THEN pm.meta_value END) as meta_location"),
                    DB::raw("MAX(CASE WHEN pm.meta_key = 'hotel_star' THEN pm.meta_value END) as meta_stars"),
                    DB::raw("MAX(CASE WHEN pm.meta_key = '_thumbnail_id' THEN pm.meta_value END) as meta_thumbnail_id")
                )
                ->whereIn('pm.meta_key', ['address', 'location', 'hotel_star', '_thumbnail_id'])
                ->groupBy('pm.post_id');

            $rows = \App\Models\WpPost::query()
                ->from($postsTable . ' as p')
                ->leftJoin($hotelsTable . ' as sh', 'sh.post_id', '=', 'p.ID')
                ->leftJoinSub($metaSub, 'pm', function ($join) {
                    $join->on('pm.post_id', '=', 'p.ID');
                })
                ->select(
                    'p.ID as wp_post_id',
                    'p.post_title',
                    'p.post_status',
                    DB::raw("COALESCE(NULLIF(sh.address, ''), NULLIF(pm.meta_address, ''), '') as address"),
                    DB::raw("COALESCE(NULLIF(pm.meta_location, ''), '') as location"),
                    DB::raw("COALESCE(NULLIF(sh.hotel_star, ''), NULLIF(pm.meta_stars, ''), '') as stars"),
                    DB::raw("pm.meta_thumbnail_id as thumbnail_id")
                )
                ->where('p.post_type', 'st_hotel')
                ->whereIn('p.post_status', ['publish', 'draft', 'private', 'pending'])
                ->orderBy('p.post_title')
                ->orderBy('p.ID')
                ->get();

            $wpSiteUrl = rtrim((string) config('wordpress.site_url', ''), '/');

            return $rows->map(function ($row) use ($wpSiteUrl) {
                $starsRaw = trim((string) ($row->stars ?? ''));
                $stars = null;
                if ($starsRaw !== '') {
                    $stars = (int) round((float) str_replace(',', '.', $starsRaw));
                    $stars = max(0, min(5, $stars));
                }

                $address = trim((string) ($row->address ?? ''));
                $location = trim((string) ($row->location ?? ''));
                $destinationSource = $location !== '' ? $location : $address;
                $destination = '';
                if ($destinationSource !== '') {
                    $segments = preg_split('/[,|-]/', $destinationSource);
                    $destination = trim((string) ($segments[0] ?? $destinationSource));
                }

                $imageId = (int) ($row->thumbnail_id ?? 0);
                $imageUrl = null;
                if ($imageId > 0) {
                    try {
                        $imageUrl = WpHeroImageService::getAttachmentUrl($imageId);
                    } catch (\Throwable $e) {
                        $imageUrl = null;
                    }
                }
                $wpUrl = $wpSiteUrl !== '' ? ($wpSiteUrl . '/?post_type=st_hotel&p=' . (int) ($row->wp_post_id ?? 0)) : null;

                return (object) [
                    'wp_post_id' => (int) ($row->wp_post_id ?? 0),
                    'hotel_name' => trim((string) ($row->post_title ?? '')),
                    'address' => $address !== '' ? $address : null,
                    'location' => $location !== '' ? $location : null,
                    'destination' => $destination !== '' ? $destination : null,
                    'stars' => $stars,
                    'image_id' => $imageId > 0 ? $imageId : null,
                    'image_url' => ! empty($imageUrl) ? (string) $imageUrl : null,
                    'wp_url' => $wpUrl,
                    'post_status' => (string) ($row->post_status ?? ''),
                ];
            })->values();
        } catch (\Throwable $e) {
            Log::warning('VoyageController@getHotelsFromWordPress failed', ['error' => $e->getMessage()]);

            return collect();
        }
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
        if (!empty($items)) {
            $this->voyageFlightOptionService->syncOptions($voyageId, $items, $lastDayNumber);
        }
    }

    /**
     * Associer les pays du monde (config) aux locations WP (arbre) et produire les donn?es pour le select + villes.
     *
     * @param array $worldCountries [ code => nom ]
     * @param array $locationsTree  [ [ 'id', 'title', 'children' => [...] ], ... ]
     * @return array [ code => [ 'id' => wpId, 'title' => nom, 'cities' => [ [ 'id', 'title' ], ... ] ], ... ]
     */
    private function buildCountryCitiesData(array $worldCountries, array $locationsTree): array
    {
        $normalize = function (string $s): string {
            $s = mb_strtolower($s, 'UTF-8');
            $accents = ['?'=>'a','?'=>'a','?'=>'a','?'=>'a','?'=>'a','?'=>'a','?'=>'ae','?'=>'c','?'=>'e','?'=>'e','?'=>'e','?'=>'e','?'=>'i','?'=>'i','?'=>'i','?'=>'i','?'=>'n','?'=>'o','?'=>'o','?'=>'o','?'=>'o','?'=>'o','?'=>'u','?'=>'u','?'=>'u','?'=>'u','?'=>'y','?'=>'y','?'=>'oe'];
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
     * Construire la liste fusionn?e Pays ?â€ ? Villes (catalogue + WP) pour l?â‚¬â„¢UI.
     * Chaque ville a : id (WP ou null), title, needsCreate (true si pas encore en WP).
     *
     * @param array $worldCountries [ code => nom ]
     * @param array $worldCities    [ code => [ 'Ville1', 'Ville2', ... ] ]
     * @param array $countryCitiesData [ code => [ 'id', 'title', 'cities' => [ [ 'id', 'title' ], ... ] ] ]
     * @return array [ code => [ [ 'id' => int|null, 'title' => string, 'needsCreate' => bool ], ... ] ]
     */
    private function buildMergedCitiesByCode(array $worldCountries, array $worldCities, array $countryCitiesData): array
    {
        $normalize = function (string $s): string {
            $s = mb_strtolower($s, 'UTF-8');
            $accents = ['?'=>'a','?'=>'a','?'=>'a','?'=>'a','?'=>'a','?'=>'a','?'=>'ae','?'=>'c','?'=>'e','?'=>'e','?'=>'e','?'=>'e','?'=>'i','?'=>'i','?'=>'i','?'=>'i','?'=>'n','?'=>'o','?'=>'o','?'=>'o','?'=>'o','?'=>'o','?'=>'u','?'=>'u','?'=>'u','?'=>'u','?'=>'y','?'=>'y','?'=>'oe'];
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

            // D?â‚¬â„¢abord les villes du catalogue world_cities
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
                if (!isset($seenNorm[$norm])) {
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
     *
     * @return int[] IDs des enregistrements TourHotel créés, dans l'ordre d'affichage (pour sync des chambres).
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
            $imagePath = trim((string) ($raw['image_path'] ?? ''));
            $hasHotelPayload = $hotelName !== ''
                || $address !== ''
                || $mealPlan !== ''
                || $notes !== ''
                || ! empty($raw['image_id'])
                || $imagePath !== ''
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
                'image_path' => $imagePath !== '' ? $imagePath : null,
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
        if (!is_array($tourHotelsInput)) {
            return [];
        }
        $tourHotelsInput = array_values($tourHotelsInput);
        $roomsByTourHotelId = [];
        $roomsByIndex = [];
        foreach ($tourHotelsInput as $idx => $maybeTourHotel) {
            if (!is_array($maybeTourHotel)) {
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
            if (!is_array($roomsInput)) {
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
                if (!is_array($r)) {
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
     * @see TourPlacesCalculator Logique unique partag?e avec le formulaire (JS).
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
        
        // Nouveau format unifi? : tour_transfers[]
        if ($request->has('tour_transfers') && is_array($request->input('tour_transfers'))) {
            foreach ($request->input('tour_transfers') as $transfer) {
                if (is_array($transfer) && (isset($transfer['from_label']) || isset($transfer['to_label']) || isset($transfer['pickup_time']))) {
                    $transfers[] = $transfer;
                }
            }
        }
        
        // Ancien format : tour_transfer_arrivals[] et tour_transfer_departures[] (compatibilit?)
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
        
        // Si nouveau format utilis?, ignorer l'ancien format
        if (!empty($transfers)) {
            TourTransfer::where('tour_id', $tourId)->delete();
            $sortOrder = 0;
            foreach ($transfers as $transfer) {
                $dayNumber = isset($transfer['day_number']) && $transfer['day_number'] !== '' ? max(1, (int) $transfer['day_number']) : 1;
                // Par d?faut, on utilise 'arrival' comme direction (peut ?tre chang? plus tard si n?cessaire)
                // Pour l'instant, on garde la compatibilit? avec le mod?le qui n?cessite une direction
                TourTransfer::create([
                    'tour_id' => $tourId,
                    'direction' => TourTransfer::DIRECTION_ARRIVAL, // Par d?faut, peut ?tre adapt? selon besoin
                    'day_number' => $dayNumber,
                    'sort_order' => $sortOrder++,
                    'is_optional' => !empty($transfer['is_optional']) ? 1 : 0,
                    'from_label' => $transfer['from_label'] ?? null,
                    'to_label' => $transfer['to_label'] ?? null,
                    'pickup_time' => $transfer['pickup_time'] ?? null,
                    'dropoff_time' => $transfer['dropoff_time'] ?? null,
                    'vehicle_type' => $transfer['vehicle_type'] ?? null,
                    'notes' => $transfer['notes'] ?? null,
                    'image_id' => isset($transfer['image_id']) && $transfer['image_id'] !== '' ? (int) $transfer['image_id'] : null,
                    'image_path' => isset($transfer['image_path']) && trim((string) $transfer['image_path']) !== '' ? trim((string) $transfer['image_path']) : null,
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
                    'is_optional' => !empty($arr['is_optional']) ? 1 : 0,
                    'from_label' => $arr['from_label'] ?? null,
                    'to_label' => $arr['to_label'] ?? null,
                    'pickup_time' => $arr['pickup_time'] ?? null,
                    'dropoff_time' => $arr['dropoff_time'] ?? null,
                    'vehicle_type' => $arr['vehicle_type'] ?? null,
                    'notes' => $arr['notes'] ?? null,
                    'image_id' => isset($arr['image_id']) && $arr['image_id'] !== '' ? (int) $arr['image_id'] : null,
                    'image_path' => isset($arr['image_path']) && trim((string) $arr['image_path']) !== '' ? trim((string) $arr['image_path']) : null,
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
                    'is_optional' => !empty($dep['is_optional']) ? 1 : 0,
                    'from_label' => $dep['from_label'] ?? null,
                    'to_label' => $dep['to_label'] ?? null,
                    'pickup_time' => $dep['pickup_time'] ?? null,
                    'dropoff_time' => $dep['dropoff_time'] ?? null,
                    'vehicle_type' => $dep['vehicle_type'] ?? null,
                    'notes' => $dep['notes'] ?? null,
                    'image_id' => isset($dep['image_id']) && $dep['image_id'] !== '' ? (int) $dep['image_id'] : null,
                    'image_path' => isset($dep['image_path']) && trim((string) $dep['image_path']) !== '' ? trim((string) $dep['image_path']) : null,
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
        if (!$voyage) {
            return;
        }

        $places = $request->input('departure_places', []);
        if (!is_array($places)) {
            $places = [];
        }

        $keptIds = [];
        $sortOrder = 0;
        foreach ($places as $placeIndex => $placeData) {
            if (!is_array($placeData)) {
                continue;
            }
            $placeName = trim($placeData['name'] ?? '');
            if ($placeName === '') {
                continue;
            }
            $placeId = isset($placeData['id']) && $placeData['id'] !== '' ? (int) $placeData['id'] : null;
            $data = [
                'name' => $placeName,
                'code' => !empty($placeData['code']) ? trim($placeData['code']) : null,
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
        if (!empty($idsToDelete)) {
            \App\Models\VoyageFlightOption::where('voyage_id', $voyage->id)->whereIn('departure_place_id', $idsToDelete)->update(['departure_place_id' => null]);
            VoyageDeparturePlace::where('voyage_id', $voyage->id)->whereIn('id', $idsToDelete)->delete();
        }
    }

    /**
     * Extras r?servation (workspace) : une ligne par option, li?e au voyage Laravel.
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
            $model = new VoyageExtra();
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
        if (!is_array($dates)) {
            return collect();
        }
        $existingDates = TravelDate::where('travel_id', $tourId)->orderBy('date')->orderBy('id')->get();
        $existingById = $existingDates->keyBy('id');
        $existingByDate = $existingDates->keyBy(fn (TravelDate $travelDate) => optional($travelDate->date)->format('Y-m-d'));
        /** @var array<string, TravelDate> M?me requ?te : ?vite plusieurs lignes aj_travel_dates pour la m?me date (cl? unique travel_id+date). */
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
     * Compatibilit? : 1 date WP (aj_travel_dates) = 1 d?part Laravel (departures).
     * Stocke wp_travel_date_id pour faire le lien avec les r?servations existantes (travel_date_id).
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
            $departureCapacity = max(0, (int) ($departure->total_capacity ?? 0));
            $fallbackRows = $this->buildDefaultDepartureRoomAllocationsFromTourHotels(
                (int) ($voyage->wp_post_id ?? 0),
                $departureCapacity
            );
            if ($fallbackRows === []) {
                $fallbackRows = $this->buildDefaultDepartureRoomAllocations($departureCapacity);
            }

            if (is_array($postedRow)) {
                $rows = $this->normalizePostedDepartureAllocationRooms($postedRow['rooms'] ?? [], $hotelIdsOrdered);
                $this->replaceDepartureRoomAllocations($departure, $rows !== [] ? $rows : $fallbackRows);
                continue;
            }

            // Keep allocations aligned with all hotel stays when no explicit payload row is posted.
            $this->replaceDepartureRoomAllocations($departure, $fallbackRows);
        }
    }

    private function buildDefaultDepartureRoomAllocationsFromTourHotels(int $wpTourId, int $capacity): array
    {
        if ($wpTourId <= 0) {
            return [];
        }

        $capacity = max(0, $capacity);
        $rows = [];
        $sortOrder = 0;
        $tourHotels = TourHotel::getAllForTour($wpTourId)->load('rooms');

        foreach ($tourHotels as $hotel) {
            $hotelRows = [];
            $coveredSeats = 0;

            foreach ($hotel->rooms as $room) {
                if (! (bool) ($room->is_active ?? true)) {
                    continue;
                }

                $roomType = trim((string) ($room->room_type ?? ''));
                if ($roomType === '') {
                    continue;
                }

                $quantity = max(0, (int) ($room->room_count ?? 0));
                if ($quantity <= 0) {
                    continue;
                }

                $capacityPerRoom = TourPlacesCalculator::effectiveCapacity(
                    (int) ($room->capacity_total ?? 0),
                    (int) ($room->capacity_adults ?? 0),
                    (int) ($room->capacity_children ?? 0),
                );
                if ($capacityPerRoom <= 0) {
                    continue;
                }

                $coveredSeats += ($quantity * $capacityPerRoom);
                $hotelRows[] = [
                    'hotel_id' => (int) $hotel->id,
                    'room_type' => $roomType,
                    'quantity' => $quantity,
                    'capacity_per_room' => $capacityPerRoom,
                    'supplement' => max(0, (float) ($room->supplement ?? 0)),
                    'sort_order' => 0,
                ];
            }

            if ($hotelRows === []) {
                $rows = array_merge(
                    $rows,
                    $this->buildDefaultDepartureRoomAllocationsForHotel((int) $hotel->id, $capacity, $sortOrder)
                );
                continue;
            }

            foreach ($hotelRows as $hotelRow) {
                $hotelRow['sort_order'] = $sortOrder++;
                $rows[] = $hotelRow;
            }

            if ($capacity > $coveredSeats) {
                $rows = array_merge(
                    $rows,
                    $this->buildDefaultDepartureRoomAllocationsForHotel((int) $hotel->id, $capacity - $coveredSeats, $sortOrder)
                );
            }
        }

        return $rows;
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
            if ($hotelId === null && count($hotelIdsOrdered) === 1) {
                $singleHotelId = reset($hotelIdsOrdered);
                $hotelId = $singleHotelId !== false ? (int) $singleHotelId : null;
            }

            $normalized[] = [
                'hotel_id' => $hotelId,
                'room_type' => $roomType,
                'quantity' => max(0, (int) ($row['quantity'] ?? 0)),
                'capacity_per_room' => max(1, (int) ($row['capacity_per_room'] ?? 1)),
                'supplement' => max(0, (float) ($row['supplement'] ?? 0)),
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
                'supplement' => max(0, (float) ($row['supplement'] ?? 0)),
                'sort_order' => max(0, (int) ($row['sort_order'] ?? 0)),
            ]);
        }
    }

    private function buildDefaultDepartureRoomAllocations(int $capacity): array
    {
        $sortOrder = 0;

        return $this->buildDefaultDepartureRoomAllocationsForHotel(null, $capacity, $sortOrder);
    }

    private function buildDefaultDepartureRoomAllocationsForHotel(?int $hotelId, int $capacity, int &$sortOrder): array
    {
        $capacity = max(0, $capacity);
        if ($capacity === 0) {
            return [];
        }

        $rows = [];
        $doubleQty = intdiv($capacity, 2);
        if ($doubleQty > 0) {
            $rows[] = [
                'hotel_id' => $hotelId,
                'room_type' => 'Double',
                'quantity' => $doubleQty,
                'capacity_per_room' => 2,
                'supplement' => 0,
                'sort_order' => $sortOrder++,
            ];
        }

        if ($capacity % 2 !== 0) {
            $rows[] = [
                'hotel_id' => $hotelId,
                'room_type' => 'Single',
                'quantity' => 1,
                'capacity_per_room' => 1,
                'supplement' => 0,
                'sort_order' => $sortOrder++,
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
                    'error' => $e->getMessage()
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
                    'error' => $e->getMessage()
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

        // Option "Utiliser l'image principale comme image ? la une WP"
        if (!empty($validated['hero_use_as_thumbnail']) && !empty($validated['hero_image_id'])) {
            $validated['thumbnail_id'] = $validated['hero_image_id'];
        }

        // Convertir gallery CSV en array
        if (!empty($validated['gallery_ids'])) {
            $validated['gallery_ids'] = array_filter(array_map('trim', explode(',', $validated['gallery_ids'])));
        }

        try {
            $this->repository->updateTour($id, $validated);

            // Programme par jours uniquement (aj_tour_days + aj_tour_day_activities). Plus d'?dition tours_program.
            if ($request->has('programme_days')) {
                try {
                    $voyage = Voyage::firstOrCreate(
                        ['wp_post_id' => $id],
                        ['name' => optional($this->repository->getPost($id))->post_title ?? 'Tour', 'slug' => 'tour-' . $id]
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

            $laravelVoyage = $this->syncLaravelVoyageFromRequest($id, $validated);

            $activitiesPayload = $this->normalizeV2ActivitiesPayload($request->input('tour_activities', []));
            $this->syncActivities($laravelVoyage, $activitiesPayload);
            $this->syncActivitiesTabToWpProgramme($id, $activitiesPayload);
            $lastDayNumber = 1;
            try {
                $program = $this->programJsonService->getProgram($id);
                $lastDayNumber = max(1, count($program['program_days'] ?? []));
            } catch (\Throwable $e) {
                // keep 1
            }
            
            // Log TOUTES les cl?s de la requ?te pour diagnostic
            \Log::info('VoyageController@update - Request keys received', [
                'tour_id' => $id,
                'has_flight_options' => $request->has('flight_options'),
                'has_flights' => $request->has('flights'),
                'all_keys' => array_keys($request->all()),
                'flight_options_count' => $request->has('flight_options') ? count($request->input('flight_options', [])) : 0,
            ]);

            // Diagnostic chambres: v?rifier si le payload contient bien tour_hotels[*].rooms
            try {
                $tourHotels = $request->input('tour_hotels', []);
                $tourHotels = is_array($tourHotels) ? $tourHotels : [];
                $roomsCounts = [];
                foreach ($tourHotels as $hi => $hotelRow) {
                    if (!is_array($hotelRow)) {
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
            
            $flightOptionsInput = $request->input('flight_options');
            $hasFlightOptions = is_array($flightOptionsInput) && !empty($flightOptionsInput);

            if (empty($request->input('flight_options')) && !$request->has('flights')) {
                try {
                    $this->voyageFlightOptionService->syncOptions($laravelVoyage->id, [], $lastDayNumber);
                    if ($laravelVoyage->wp_post_id) {
                        $this->voyageFlightOptionService->syncOptionsToWp($laravelVoyage->id, (int) $laravelVoyage->wp_post_id, $lastDayNumber);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('VoyageController@update clear flights (empty flight_options) failed', ['tour_id' => $id, 'message' => $e->getMessage()]);
                }
            } elseif ($hasFlightOptions) {
                try {
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

            // H?tels puis chambres (IDs nouveaux apr?s delete/create) ?â‚¬? un seul sync h?tels
            $hotelIdsOrdered = $this->syncTourHotels($id, $request);
            $this->syncTourTransfers($id, $request, $lastDayNumber);

            // Synchroniser les lieux de d?part et les dates disponibles
            $this->syncDeparturePlaces($id, $request);
            $this->syncTravelDates($id, $request);
            // V?rit? terrain apr?s ?criture WP : la collection retourn?e par syncTravelDates peut ?tre vide
            // si le POST ne repasse pas travel_dates (ex. soumission partielle), alors que aj_travel_dates contient d?j? des lignes.
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
            $this->syncVoyageLogisticsMeta($laravelVoyage, $request);
            // Toujours synchroniser les vols Laravel ?â€ ? WP apr?s chaque enregistrement (pour que le plugin affiche les vols)
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

            return redirect()
                ->route('admin.circuits.voyages.edit', $id)
                ->with('success', 'Tour mis ? jour avec succ?s dans WordPress ! Modifications visibles imm?diatement.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Erreur lors de la mise ? jour : ' . $e->getMessage()]);
        }
    }

    /**
     * Keep Laravel voyage row synced with the WP tour and admin flags.
     *
     * @param  array<string,mixed>  $validated
     */
    protected function syncLaravelVoyageFromRequest(int $wpPostId, array $validated): Voyage
    {
        $wpPost = $this->repository->getPost($wpPostId);
        $voyage = Voyage::firstOrNew(['wp_post_id' => $wpPostId]);
        $fallbackTitle = trim((string) ($wpPost->post_title ?? ('Tour ' . $wpPostId)));
        $titleFromInput = array_key_exists('title', $validated)
            ? trim((string) ($validated['title'] ?? ''))
            : null;

        $fill = [
            'name' => $titleFromInput !== null
                ? ($titleFromInput !== '' ? $titleFromInput : $fallbackTitle)
                : ($voyage->name ?: $fallbackTitle),
        ];

        $shouldSyncSlug = array_key_exists('slug', $validated) || ! $voyage->exists || empty($voyage->slug);
        if ($shouldSyncSlug) {
            $slugInput = array_key_exists('slug', $validated)
                ? trim((string) ($validated['slug'] ?? ''))
                : '';
            $baseSlug = $slugInput !== ''
                ? $slugInput
                : ($voyage->slug ?: ('tour-' . $wpPostId));
            $fill['slug'] = $this->ensureUniqueVoyageSlug($baseSlug, $voyage->exists ? (int) $voyage->id : null);
        }

        if (array_key_exists('destination', $validated)) {
            $destination = trim((string) ($validated['destination'] ?? ''));
            $fill['destination'] = $destination !== '' ? $destination : null;
        }
        if (array_key_exists('duration_text', $validated)) {
            $durationText = trim((string) ($validated['duration_text'] ?? ''));
            $fill['duration_text'] = $durationText !== '' ? $durationText : null;
        }
        if (array_key_exists('is_group_deal', $validated)) {
            $fill['is_group_deal'] = (bool) ($validated['is_group_deal'] ?? false);
        }

        $voyage->fill($fill);
        $voyage->save();

        if (Schema::hasTable('voyage_voyage_theme') && array_key_exists('voyage_theme_ids', $validated)) {
            $themeIds = collect($validated['voyage_theme_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $voyage->themes()->sync($themeIds);
        }

        return $voyage;
    }

    /**
     * Charge les th?mes Laravel pour l'onglet taxonomies.
     * Si la table est vide, initialise une seule fois les th?mes par d?faut.
     */
    protected function loadVoyageThemesForEdit(): Collection
    {
        if (! Schema::hasTable('voyage_themes')) {
            return collect();
        }

        $themes = VoyageTheme::query()
            ->ordered()
            ->get(['id', 'name', 'slug', 'is_active', 'sort_order']);

        if ($themes->isNotEmpty()) {
            return $themes;
        }

        foreach (VoyageThemeSeeder::defaultThemes() as $row) {
            VoyageTheme::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'is_active' => true,
                    'sort_order' => (int) $row['sort_order'],
                ]
            );
        }

        return VoyageTheme::query()
            ->ordered()
            ->get(['id', 'name', 'slug', 'is_active', 'sort_order']);
    }

    /**
     * Ensure Laravel voyage slug is unique before saving.
     */
    protected function ensureUniqueVoyageSlug(string $baseSlug, ?int $excludeId = null): string
    {
        $slug = Str::slug($baseSlug);
        if ($slug === '') {
            $slug = 'voyage';
        }

        $candidate = $slug;
        $suffix = 2;

        while (Voyage::query()
            ->where('slug', $candidate)
            ->when($excludeId, function ($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId);
            })
            ->exists()) {
            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
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
        if (!is_array($programmeDays)) {
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

        if (!empty($orderedDayIds)) {
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
            $dayTitle = isset($dayRow['day_title']) ? trim((string) $dayRow['day_title']) : '';
            $plainDescription = isset($dayRow['description']) ? trim((string) $dayRow['description']) : '';
            $notes = isset($dayRow['notes']) ? trim((string) $dayRow['notes']) : '';
            $contentHtml = isset($dayRow['content_html']) ? trim((string) $dayRow['content_html']) : '';
            $wpProgramNotes = $contentHtml !== '' ? $contentHtml : $notes;
            $this->programService->updateDay($dayId, [
                'mode' => $dayRow['mode'] ?? 'program',
                'day_title' => $dayTitle !== '' ? $dayTitle : null,
                'notes' => $wpProgramNotes !== '' ? $wpProgramNotes : null,
                'title' => $dayTitle !== '' ? $dayTitle : ($dayRow['title'] ?? null),
                'description' => $plainDescription !== '' ? $plainDescription : ($contentHtml !== '' ? trim(strip_tags($contentHtml)) : null),
            ]);

            $this->syncTravelProgramDayContent($tourId, $dayNumber, is_array($dayRow) ? $dayRow : []);

            $activities = $dayRow['activities'] ?? [];
            if (!is_array($activities)) {
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
                $dayScope = ($row['day_scope'] ?? 'fixed') === 'open' ? 'open' : 'fixed';

                if ($dayActivityId > 0) {
                    $this->programService->updateDayActivity($dayActivityId, [
                        'is_mandatory' => $isMandatory,
                        'is_included' => $isIncluded,
                        'day_scope' => $dayScope,
                        'custom_title' => $row['custom_title'] ?? null,
                        'custom_description' => $row['custom_description'] ?? null,
                        'sort_order' => $k,
                    ]);
                    $submittedDayActivityIds[] = $dayActivityId;
                } else {
                    $newDa = $this->programService->addActivityToDay($dayId, $activityId, [
                        'sort_order' => $k,
                        'is_included' => $isIncluded,
                        'day_scope' => $dayScope,
                        'is_mandatory' => $isMandatory,
                        'custom_title' => $row['custom_title'] ?? null,
                        'custom_description' => $row['custom_description'] ?? null,
                    ]);
                    $submittedDayActivityIds[] = $newDa->id;
                }
            }

            // Sync hotel & transfers for this day (TravelProgramDay, r?solu depuis TourDay)
            $this->syncDayHotelsAndTransfers($tourId, $dayId, is_array($dayRow) ? $dayRow : []);
        }

        $voyage = Voyage::where('wp_post_id', $tourId)->first();
        if ($voyage) {
            TravelProgramDay::where('voyage_id', $voyage->id)
                ->when(!empty($submittedDayNumbers), function ($query) use ($submittedDayNumbers) {
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

        $this->syncLegacyWpToursProgramMeta($tourId);
    }

    protected function syncLegacyWpToursProgramMeta(int $tourId): void
    {
        $voyage = Voyage::query()
            ->where('wp_post_id', $tourId)
            ->with(['programDays' => fn ($query) => $query->orderBy('day_number'), 'programDays.dayItems'])
            ->first();

        if (!$voyage || $voyage->programDays->isEmpty()) {
            return;
        }

        $items = $voyage->programDays->map(function (TravelProgramDay $day): array {
            $contentHtml = trim((string) ($day->content_html ?? ''));
            $plainDescription = trim((string) ($day->description ?? ''));

            return [
                'title' => $day->title ?: ('Jour ' . $day->day_number),
                'content' => $contentHtml !== '' ? $contentHtml : $plainDescription,
                'desc' => $contentHtml !== '' ? $contentHtml : $plainDescription,
                'description' => $plainDescription,
            ];
        })->values()->all();

        WpPost::tours()
            ->findOrFail($tourId)
            ->setMeta('tours_program', serialize($items));
    }

    /**
     * Sync inline "Activit?s" tab rows for a voyage (save-global strategy).
     * Stores rows in travel_day_items with type=activity and source=voyage_activities_tab.
     */
    protected function syncActivities(Voyage $voyage, array $payload): void
    {
        try {
        $payload = $this->normalizeV2ActivitiesPayload($payload);

        $keptIds = [];
        $sortOrder = 0;
        $usedExistingIds = [];

        $wpTourId = (int) ($voyage->wp_post_id ?? 0);
        $validDayNumbers = [];
        if ($wpTourId > 0) {
            $validDayNumbers = TourDay::query()
                ->where('tour_id', $wpTourId)
                ->orderBy('day_number')
                ->pluck('day_number')
                ->map(fn ($dayNumber) => (int) $dayNumber)
                ->filter(fn (int $dayNumber) => $dayNumber > 0)
                ->unique()
                ->values()
                ->all();
        }
        if (empty($validDayNumbers)) {
            $validDayNumbers = [1];
        }

        $existingManagedItems = TravelDayItem::query()
            ->where('voyage_id', $voyage->id)
            ->where('type', 'activity')
            ->get();

        $managedPool = $existingManagedItems->filter(function (TravelDayItem $item): bool {
            $meta = $item->meta_json;
            if (is_string($meta)) {
                $decoded = json_decode($meta, true);
                $meta = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
            }

            return is_array($meta) && ($meta['source'] ?? null) === 'voyage_activities_tab';
        })->values();

        $activityIds = collect($payload)
            ->map(fn ($row) => is_array($row) ? (int) ($row['activity_id'] ?? 0) : 0)
            ->filter(fn (int $activityId) => $activityId > 0)
            ->unique()
            ->values()
            ->all();
        $activityTitleMap = collect();
        if (! empty($activityIds)) {
            try {
                $activityTitleMap = Activity::query()
                    ->whereIn('id', $activityIds)
                    ->pluck('title', 'id');
            } catch (\Throwable $e) {
                Log::warning('VoyageController@syncActivities activity catalog lookup failed', [
                    'voyage_id' => $voyage->id,
                    'tour_id' => $voyage->wp_post_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $minDayNumber = (int) min($validDayNumbers);
        $maxDayNumber = (int) max($validDayNumbers);
        $normalizeDayNumber = static function ($rawDayNumber) use ($validDayNumbers, $minDayNumber, $maxDayNumber): int {
            $dayNumber = (int) $rawDayNumber;
            if ($dayNumber <= 0) {
                return $minDayNumber;
            }
            if (in_array($dayNumber, $validDayNumbers, true)) {
                return $dayNumber;
            }
            if ($dayNumber < $minDayNumber) {
                return $minDayNumber;
            }
            if ($dayNumber > $maxDayNumber) {
                return $maxDayNumber;
            }
            foreach ($validDayNumbers as $validDayNumber) {
                if ($dayNumber <= (int) $validDayNumber) {
                    return (int) $validDayNumber;
                }
            }
            return $maxDayNumber;
        };

        foreach ($payload as $row) {
            if (!is_array($row)) {
                continue;
            }

            $activityId = (int) ($row['activity_id'] ?? 0);
            if ($activityId <= 0) {
                continue;
            }

            $catalogTitle = trim((string) ($activityTitleMap->get($activityId) ?? ''));
            $activityTitle = trim((string) ($row['activity_title'] ?? ''));
            $activityType = trim((string) ($row['activity_type'] ?? ''));
            if ($activityTitle !== '' || $activityType !== '') {
                try {
                    $activityUpdate = [];
                    if ($activityTitle !== '') {
                        $activityUpdate['title'] = $activityTitle;
                        $catalogTitle = $activityTitle;
                    }
                    if ($activityType !== '') {
                        $activityUpdate['activity_type'] = $activityType;
                    }
                    if ($activityUpdate !== []) {
                        Activity::query()->where('id', $activityId)->update($activityUpdate);
                    }
                } catch (\Throwable $e) {
                    Log::warning('VoyageController@syncActivities catalog activity update failed', [
                        'activity_id' => $activityId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            $title = trim((string) ($row['display_title'] ?? $row['title'] ?? ''));
            $included = $this->normalizeCheckboxValue($row['included'] ?? 1, 1);
            $status = strtolower(trim((string) ($row['status'] ?? ($included ? 'included' : 'optional'))));
            if (! in_array($status, ['included', 'optional', 'proposition'], true)) {
                $status = $included ? 'included' : 'optional';
            }
            $included = $status === 'included' ? 1 : 0;
            $visibilityMode = strtolower(trim((string) ($row['visibility_mode'] ?? 'single_day')));
            if (! in_array($visibilityMode, ['single_day', 'multiple_days', 'all_days'], true)) {
                $visibilityMode = (($row['day_scope'] ?? 'fixed') === 'open') ? 'all_days' : 'single_day';
            }

            $rawDays = $row['days'] ?? [];
            if (! is_array($rawDays)) {
                $rawDays = [];
            }
            $days = collect($rawDays)
                ->map(fn ($day) => $normalizeDayNumber($day))
                ->filter(fn (int $day) => $day > 0)
                ->unique()
                ->values()
                ->all();

            if ($visibilityMode === 'all_days') {
                $targetDayNumbers = $validDayNumbers;
            } elseif ($visibilityMode === 'multiple_days') {
                $targetDayNumbers = $days !== [] ? $days : [$normalizeDayNumber($row['day_number'] ?? 0)];
            } else {
                $targetDayNumbers = [$normalizeDayNumber($days[0] ?? ($row['day_number'] ?? 0))];
            }

            $targetDayNumbers = array_values(array_unique(array_map('intval', $targetDayNumbers)));
            $dayScope = $visibilityMode === 'all_days' ? 'open' : 'fixed';
            $pricingType = ($row['pricing_type'] ?? 'per_person') === 'fixed' ? 'fixed' : 'per_person';
            $unitPrice = (float) ($row['custom_price'] ?? $row['unit_price'] ?? 0);
            $unitPrice = $unitPrice < 0 ? 0 : round($unitPrice, 2);
            $childPrice = (float) ($row['child_price'] ?? 0);
            $childPrice = $childPrice < 0 ? 0 : round($childPrice, 2);
            $quantity = (int) ($row['quantity'] ?? 1);
            $quantity = $quantity < 1 ? 1 : $quantity;
            $description = trim((string) ($row['description'] ?? ''));
            $startTime = trim((string) ($row['start_time'] ?? ''));
            $endTime = trim((string) ($row['end_time'] ?? ''));
            $startTime = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $startTime) ? $startTime : null;
            $endTime = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $endTime) ? $endTime : null;
            $rowSortOrder = isset($row['sort_order']) && $row['sort_order'] !== ''
                ? max(0, (int) $row['sort_order'])
                : $sortOrder;
            $itemId = (int) ($row['id'] ?? 0);
            $groupUuid = trim((string) ($row['group_uuid'] ?? ''));
            if ($groupUuid === '') {
                $groupUuid = (string) Str::uuid();
            }

            foreach ($targetDayNumbers as $targetIndex => $dayNumber) {
                $itemData = [
                    'voyage_id' => $voyage->id,
                    'day_number' => $dayNumber,
                    'start_day' => $dayNumber,
                    'end_day' => $dayNumber,
                    'nights' => 0,
                    'type' => 'activity',
                    'title' => $title !== '' ? $title : ($catalogTitle !== '' ? $catalogTitle : ('Activite #' . $activityId)),
                    'details' => $description !== '' ? $description : null,
                    'included' => $included,
                    'price_delta_per_person' => $pricingType === 'per_person' ? (int) round($unitPrice * 100) : 0,
                    'options_json' => [
                        'activity_id' => $activityId,
                        'status' => $status,
                        'activity_title' => $activityTitle !== '' ? $activityTitle : $catalogTitle,
                        'activity_type' => $activityType !== '' ? $activityType : null,
                        'visibility_mode' => $visibilityMode,
                        'days' => $targetDayNumbers,
                        'pricing_type' => $pricingType,
                        'unit_price' => $unitPrice,
                        'custom_price' => $unitPrice,
                        'child_price' => $childPrice,
                        'quantity' => $quantity,
                        'description' => $description,
                        'day_number' => $dayNumber,
                        'included' => $included,
                        'day_scope' => $dayScope,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ],
                    'meta_json' => [
                        'source' => 'voyage_activities_tab',
                        'group_uuid' => $groupUuid,
                    ],
                    'sort_order' => $rowSortOrder + $targetIndex,
                ];

                $existing = null;
                if ($targetIndex === 0 && $itemId > 0) {
                    $existing = $managedPool
                        ->first(fn (TravelDayItem $item): bool => $item->id === $itemId && ! in_array($item->id, $usedExistingIds, true));
                }

                if (! $existing) {
                    $existing = $managedPool->first(function (TravelDayItem $item) use ($groupUuid, $dayNumber, $usedExistingIds): bool {
                        if (in_array($item->id, $usedExistingIds, true)) {
                            return false;
                        }

                        $meta = $item->meta_json;
                        if (is_string($meta)) {
                            $decoded = json_decode($meta, true);
                            $meta = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
                        }

                        if (! is_array($meta) || ($meta['source'] ?? null) !== 'voyage_activities_tab') {
                            return false;
                        }

                        return (string) ($meta['group_uuid'] ?? '') === $groupUuid
                            && (int) ($item->day_number ?? 0) === (int) $dayNumber;
                    });
                }

                if ($existing) {
                    $existing->fill($itemData);
                    $existing->save();
                    $keptIds[] = $existing->id;
                    $usedExistingIds[] = $existing->id;
                } else {
                    $new = TravelDayItem::create($itemData);
                    $keptIds[] = $new->id;
                    $usedExistingIds[] = $new->id;
                }
            }

            $sortOrder += count($targetDayNumbers);
        }

        TravelDayItem::query()
            ->where('voyage_id', $voyage->id)
            ->where('type', 'activity')
            ->when(!empty($keptIds), fn ($query) => $query->whereNotIn('id', $keptIds))
            ->get(['id', 'meta_json'])
            ->each(function (TravelDayItem $item): void {
                $meta = $item->meta_json;
                if (is_string($meta)) {
                    $decoded = json_decode($meta, true);
                    $meta = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
                }
                if (!is_array($meta) || ($meta['source'] ?? null) !== 'voyage_activities_tab') {
                    return;
                }
                $item->delete();
            });
        } catch (\Throwable $e) {
            Log::error('Activities Save Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => [
                    'voyage_id' => $voyage->id ?? null,
                    'wp_post_id' => $voyage->wp_post_id ?? null,
                    'tour_activities' => $payload,
                ],
            ]);

            throw $e;
        }
    }

    /**
     * Mirror Activities-tab rows into wp.aj_tour_day_activities so front V1 uses the same option-client logic.
     */
    protected function syncActivitiesTabToWpProgramme(int $tourId, array $payload): void
    {
        $payload = $this->normalizeV2ActivitiesPayload($payload);
        if ($tourId <= 0) {
            return;
        }

        $days = TourDay::query()
            ->where('tour_id', $tourId)
            ->orderBy('day_number')
            ->get();

        if ($days->isEmpty()) {
            $this->programService->addDay($tourId);
            $days = TourDay::query()
                ->where('tour_id', $tourId)
                ->orderBy('day_number')
                ->get();
        }

        if ($days->isEmpty()) {
            return;
        }

        $daysByNumber = $days->keyBy(fn (TourDay $day) => (int) $day->day_number);
        $firstDay = $days->first();
        $maxDayNumber = (int) $days->max('day_number');
        $minDayNumber = (int) $days->min('day_number');
        $keptWpDayActivityIds = [];
        $resolveDay = function ($rawDayNumber) use ($daysByNumber, $firstDay, $minDayNumber, $maxDayNumber): ?TourDay {
            $dayNumber = (int) $rawDayNumber;
            if ($dayNumber <= 0) {
                $dayNumber = (int) ($firstDay?->day_number ?? 1);
            }
            if ($dayNumber < $minDayNumber) {
                $dayNumber = $minDayNumber;
            }
            if ($dayNumber > $maxDayNumber) {
                $dayNumber = $maxDayNumber;
            }

            return $daysByNumber->get($dayNumber) ?: $firstDay;
        };

        $existingManaged = TourDayActivity::query()
            ->where('tour_id', $tourId)
            ->where('is_mandatory', 0)
            ->get();
        $usedExistingWpIds = [];

        $normalizeTime = static function ($raw): ?string {
            $value = trim((string) ($raw ?? ''));
            if ($value === '') {
                return null;
            }
            if (! preg_match('/^([01]\\d|2[0-3]):[0-5]\\d$/', $value)) {
                return null;
            }

            return $value . ':00';
        };

        foreach ($payload as $row) {
            if (!is_array($row)) {
                continue;
            }

            $activityId = (int) ($row['activity_id'] ?? 0);
            if ($activityId <= 0) {
                continue;
            }

            $isIncluded = $this->normalizeCheckboxValue($row['included'] ?? 1, 1);
            $status = strtolower(trim((string) ($row['status'] ?? ($isIncluded ? 'included' : 'optional'))));
            if (! in_array($status, ['included', 'optional', 'proposition'], true)) {
                $status = $isIncluded ? 'included' : 'optional';
            }
            $isIncluded = $status === 'included' ? 1 : 0;
            $visibilityMode = strtolower(trim((string) ($row['visibility_mode'] ?? 'single_day')));
            if (! in_array($visibilityMode, ['single_day', 'multiple_days', 'all_days'], true)) {
                $visibilityMode = (($row['day_scope'] ?? 'fixed') === 'open') ? 'all_days' : 'single_day';
            }
            $dayScope = $visibilityMode === 'all_days' ? 'open' : 'fixed';

            $customTitle = trim((string) ($row['display_title'] ?? $row['title'] ?? ''));
            $customDescription = trim((string) ($row['description'] ?? ''));
            $customPrice = array_key_exists('custom_price', $row) && $row['custom_price'] !== null && $row['custom_price'] !== ''
                ? (float) $row['custom_price']
                : (array_key_exists('unit_price', $row) && $row['unit_price'] !== null && $row['unit_price'] !== ''
                    ? (float) $row['unit_price']
                    : null);
            $startTime = $normalizeTime($row['start_time'] ?? null);
            $endTime = $normalizeTime($row['end_time'] ?? null);
            $rawDays = $row['days'] ?? [];
            if (! is_array($rawDays)) {
                $rawDays = [];
            }
            $resolvedDayNumbers = collect($rawDays)
                ->map(fn ($rawDay) => (int) $rawDay)
                ->filter(fn (int $day) => $day > 0)
                ->unique()
                ->values()
                ->all();
            if ($resolvedDayNumbers === []) {
                $resolvedDayNumbers = [(int) ($row['day_number'] ?? 1)];
            }
            $sortOrder = isset($row['sort_order']) && $row['sort_order'] !== '' ? max(0, (int) $row['sort_order']) : 0;

            $targetDays = $visibilityMode === 'all_days'
                ? $days
                : collect($resolvedDayNumbers)
                    ->map(fn (int $dayNumber) => $resolveDay($dayNumber))
                    ->filter()
                    ->values();
            if ($targetDays->isEmpty()) {
                $targetDays = collect([$resolveDay($row['day_number'] ?? 1)])->filter()->values();
            }

            foreach ($targetDays as $targetDay) {
                if (!$targetDay) {
                    continue;
                }

                $dayId = (int) $targetDay->id;
                $existing = $existingManaged->first(function (TourDayActivity $item) use ($tourId, $activityId, $dayId, $customTitle, $customDescription, $startTime, $endTime, $usedExistingWpIds): bool {
                    if (in_array((int) $item->id, $usedExistingWpIds, true)) {
                        return false;
                    }

                    return (int) $item->tour_id === $tourId
                        && (int) $item->activity_id === $activityId
                        && (int) $item->day_id === $dayId
                        && (string) ($item->custom_title ?? '') === (string) ($customTitle !== '' ? $customTitle : '')
                        && (string) ($item->custom_description ?? '') === (string) ($customDescription !== '' ? $customDescription : '')
                        && (string) ($item->start_time ?? '') === (string) ($startTime ?? '')
                        && (string) ($item->end_time ?? '') === (string) ($endTime ?? '');
                });

                $data = [
                    'day_id' => $dayId,
                    'sort_order' => $sortOrder,
                    'is_included' => $isIncluded,
                    'status' => $status,
                    'day_scope' => $dayScope,
                    'is_mandatory' => 0,
                    'is_editable' => 0,
                    'custom_title' => $customTitle !== '' ? $customTitle : null,
                    'custom_description' => $customDescription !== '' ? $customDescription : null,
                    'custom_price' => $customPrice,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ];

                if ($existing) {
                    $this->programService->updateDayActivity((int) $existing->id, $data);
                    $keptWpDayActivityIds[] = (int) $existing->id;
                    $usedExistingWpIds[] = (int) $existing->id;
                } else {
                    $created = $this->programService->addActivityToDay($dayId, $activityId, $data);
                    if ($created) {
                        $keptWpDayActivityIds[] = (int) $created->id;
                    }
                }
            }
        }

        TourDayActivity::query()
            ->where('tour_id', $tourId)
            ->where('is_mandatory', 0)
            ->where('is_editable', 0)
            ->get()
            ->each(function (TourDayActivity $dayActivity) use ($keptWpDayActivityIds): void {
                if (in_array((int) $dayActivity->id, $keptWpDayActivityIds, true)) {
                    return;
                }

                $this->programService->removeDayActivity((int) $dayActivity->id);
            });
    }

    /**
     * Sync hotel and transfers for a specific day.
     * - $tourId: WP tour id (wp_posts.ID)
     * - $dayId: TourDay.id (aj_tour_days) envoy? par le formulaire
     * - $dayRow: current programme_days[$i] request array
     * On r?sout TourDay -> day_number puis TravelProgramDay par voyage_id + day_number.
     */
    protected function syncDayHotelsAndTransfers(int $tourId, int $dayId, array $dayRow): void
    {
        $tourDay = TourDay::where('tour_id', $tourId)->where('id', $dayId)->first();
        if (!$tourDay) {
            return;
        }
        $voyage = Voyage::where('wp_post_id', $tourId)->first();
        if (!$voyage) {
            return;
        }
        $day = TravelProgramDay::where('voyage_id', $voyage->id)->where('day_number', (int) $tourDay->day_number)->first();
        if (!$day) {
            return;
        }

        // Syncer l'hôtel (0..1). Si hotel_id vide, lier au TourHotel créé pour ce jour (ex. ajout depuis le drawer).
        $hotelId = !empty($dayRow['hotel_id']) ? (int) $dayRow['hotel_id'] : null;
        if ($hotelId) {
            $hotel = TourHotel::find($hotelId);
            if ($hotel) {
                $day->update(['hotel_id' => $hotelId]);
            } else {
                $day->update(['hotel_id' => null]);
            }
        } else {
            // Chercher un h?tel o? le jour est dans la plage check-in -> check-out
            $dayNumber = (int) $tourDay->day_number;
            $hotelForDay = TourHotel::where('tour_id', $tourId)
                ->where(function($query) use ($dayNumber) {
                    // Nouveau format : check_in_day / check_out_day
                    $query->where(function($q) use ($dayNumber) {
                        $q->whereNotNull('check_in_day')
                          ->whereNotNull('check_out_day')
                          ->where('check_in_day', '<=', $dayNumber)
                          ->where('check_out_day', '>=', $dayNumber);
                    })
                    // Compatibilit? ancien format : day_number
                    ->orWhere(function($q) use ($dayNumber) {
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
        if (is_string($transferInput) && !empty($transferInput)) {
            // Format: "1,2,3" ou "1" ou ""
            $transferIds = array_filter(
                array_map('intval', array_map('trim', explode(',', $transferInput))),
                fn($id) => $id > 0
            );
        } elseif (is_array($transferInput)) {
            $transferIds = array_filter(
                array_map('intval', $transferInput),
                fn($id) => $id > 0
            );
        }

        // Valider que chaque transfert existe, puis syncer
        // Utiliser directement DB::connection('mysql') pour forcer la bonne connexion pour la table pivot
        // car la relation belongsToMany utilise la connexion du mod?le li? (TourTransfer sur 'wp')
        if (!empty($transferIds)) {
            $validIds = TourTransfer::whereIn('id', $transferIds)->pluck('id')->toArray();
            
            // Utiliser la connexion 'mysql' pour la table pivot
            $pivotTable = 'program_day_transfers';
            
            $programDayId = $day->id; // TravelProgramDay.id pour la table pivot
            // Supprimer les anciennes associations
            DB::connection('mysql')->table($pivotTable)
                ->where('program_day_id', $programDayId)
                ->delete();
            
            // Ins?rer les nouvelles associations
            if (!empty($validIds)) {
                $insertData = array_map(function($transferId) use ($programDayId) {
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
            ['name' => optional($this->repository->getPost($tourId))->post_title ?? 'Tour', 'slug' => 'tour-' . $tourId]
        );

        $dayTitle = trim((string) ($dayRow['day_title'] ?? $dayRow['title'] ?? ''));
        $description = trim((string) ($dayRow['description'] ?? ''));
        $notes = trim((string) ($dayRow['notes'] ?? ''));
        $contentHtml = trim((string) ($dayRow['content_html'] ?? ''));
        $dayType = (string) ($dayRow['day_type'] ?? 'visite');
        if (!array_key_exists($dayType, TravelProgramDay::DAY_TYPES)) {
            $dayType = 'visite';
        }

        $programDay = TravelProgramDay::firstOrNew([
            'voyage_id' => $voyage->id,
            'day_number' => $dayNumber,
        ]);

        $city = trim((string) ($dayRow['city'] ?? ''));

        $programDay->fill([
            'title' => $dayTitle !== '' ? $dayTitle : ('Jour ' . $dayNumber),
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
            if (!is_array($row)) {
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
            $dayType = (string) ($row['day_type'] ?? 'visite');
            if (!array_key_exists($dayType, TravelProgramDay::DAY_TYPES)) {
                $dayType = 'visite';
            }

            $day = (object) [
                'id' => (int) ($row['id'] ?? $row['day_id'] ?? 0),
                'day_number' => $dayNumber,
                'mode' => ($row['mode'] ?? 'program') === 'free' ? 'free' : 'program',
                'day_title' => $dayTitle !== '' ? $dayTitle : ('Jour ' . $dayNumber),
                'title' => $title !== '' ? $title : ($dayTitle !== '' ? $dayTitle : ('Jour ' . $dayNumber)),
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
                        'day_scope' => ($activityRow['day_scope'] ?? 'fixed') === 'open' ? 'open' : 'fixed',
                        'is_mandatory' => $this->normalizeCheckboxValue($activityRow['is_mandatory'] ?? 0, 0),
                        'is_editable' => true,
                        'custom_title' => (string) ($activityRow['custom_title'] ?? ''),
                        'custom_description' => (string) ($activityRow['custom_description'] ?? ''),
                        'activity' => (object) [
                            'title' => $catalogActivity->title ?? ('Activit? #' . $activityId),
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
                ->with('success', 'Jour ajout?.')
                ->withFragment('program-days');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Impossible d\'ajouter le jour : ' . $e->getMessage()]);
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
                ->with('success', 'Jour supprim?.')
                ->withFragment('program-days');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Impossible de supprimer le jour : ' . $e->getMessage()]);
        }
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
                ->with('success', 'Tour supprim? avec succ?s de WordPress !');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
        }
    }
}
