<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWpTourRequest;
use App\Http\Requests\UpdateWpTourRequest;
use App\Models\Wp\WpPost;
use App\Models\Wp\Activity;
use App\Models\Wp\TourDayActivity;
use App\Models\Voyage;
use App\Models\TravelProgramDay;
use App\Models\Airline;
use App\Models\TourHotel;
use App\Models\TourTransfer;
use App\Models\TravelDeparturePlace;
use App\Models\TravelDepartureFlight;
use App\Models\TravelDate;
use App\Services\VoyageFlightService;
use App\Services\VoyageFlightOptionService;
use App\Services\Wp\ProgramJsonService;
use App\Services\Wp\TourProgramService;
use App\Services\Wp\WpHeroImageService;
use App\Services\Wp\WpTourRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VoyageController extends Controller
{
    protected WpTourRepository $repository;

    protected TourProgramService $programService;

    protected VoyageFlightService $voyageFlightService;

    protected VoyageFlightOptionService $voyageFlightOptionService;

    protected ProgramJsonService $programJsonService;

    public function __construct(WpTourRepository $repository, TourProgramService $programService, VoyageFlightService $voyageFlightService, VoyageFlightOptionService $voyageFlightOptionService, ProgramJsonService $programJsonService)
    {
        $this->repository = $repository;
        $this->programService = $programService;
        $this->voyageFlightService = $voyageFlightService;
        $this->voyageFlightOptionService = $voyageFlightOptionService;
        $this->programJsonService = $programJsonService;
    }

    /**
     * Display listing of WordPress tours.
     */
    public function index(): View
    {
        $wpConnectionFailed = false;
        try {
            $tours = WpPost::tours()
                ->orderByDesc('ID')
                ->paginate(20);

            $tours->getCollection()->transform(function ($tour) {
                $tour->adult_price = $tour->getMeta('adult_price');
                $tour->duration_day = $tour->getMeta('duration_day');
                $tour->address = $tour->getMeta('address');
                $tour->child_price = $tour->getMeta('child_price');
                return $tour;
            });
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@index: WP connection failed', ['error' => $e->getMessage()]);
            $wpConnectionFailed = true;
            $tours = new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                20,
                \Illuminate\Pagination\Paginator::resolveCurrentPage(),
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
        }

        return view('admin.circuits.voyages.index', compact('tours', 'wpConnectionFailed'));
    }

    /**
     * Show single tour (détail).
     */
    public function show(int $id): View
    {
        $wpPost = WpPost::tours()->where('ID', $id)->firstOrFail();
        
        // Créer un objet compatible avec les vues existantes
        $voyage = $wpPost;
        $voyage->name = $wpPost->post_title; // Alias pour compatibilité
        $voyage->slug = $wpPost->post_name;
        $voyage->description = $wpPost->post_content;
        $voyage->updated_at = $wpPost->post_modified;
        $voyage->created_at = $wpPost->post_date;
        
        // Charger les metas
        $meta = [
            'adult_price' => $wpPost->getMeta('adult_price'),
            'child_price' => $wpPost->getMeta('child_price'),
            'duration_day' => $wpPost->getMeta('duration_day'),
            'address' => $wpPost->getMeta('address'),
            'min_price' => $wpPost->getMeta('min_price'),
            'min_people' => $wpPost->getMeta('min_people'),
            'thumbnail_id' => $wpPost->getMeta('_thumbnail_id'),
            'hero_image_id' => $wpPost->getMeta('_tour_hero_image_id'),
            'hero_gallery_ids' => $wpPost->getMeta('_tour_hero_gallery_ids'),
            'gallery' => $wpPost->getMeta('gallery'),
        ];

        // Programme par jours (aj_tour_days + activités) pour la timeline "Programme du circuit"
        $programDays = collect();
        try {
            $programDays = $this->programService->loadProgram((int) $id);
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@show loadProgram failed', ['tour_id' => $id, 'error' => $e->getMessage()]);
        }
        
        return view('admin.circuits.voyages.show', compact('voyage', 'meta', 'programDays'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        // Charger les locations pour le formulaire create
        $locationsTree = $this->repository->getLocationsTree();
        $selectedLocationIds = [];
        
        // Programme vide pour création
        $tourProgram = ['style' => 'style1', 'items' => []];
        try {
            $airlines = Airline::query()->orderBy('name')->get();
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@create: could not load airlines', ['error' => $e->getMessage()]);
            $airlines = collect();
        }

        return view('admin.circuits.voyages.create', compact('locationsTree', 'selectedLocationIds', 'tourProgram', 'airlines'));
    }

    /**
     * Store new tour in WordPress.
     */
    public function store(StoreWpTourRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Générer slug si vide
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Convertir gallery CSV en array
        if (!empty($validated['gallery_ids'])) {
            $validated['gallery_ids'] = array_filter(array_map('trim', explode(',', $validated['gallery_ids'])));
        }

        try {
            $tour = $this->repository->createTour($validated);
            
            // Save tour program if provided (PHP serialized)
            if ($request->has('tours_program')) {
                $programStyle = $request->input('tours_program_style', 'style1');
                $programItems = $request->input('tours_program', []);
                $this->repository->saveTourProgram($tour->ID, $programStyle, $programItems);
            }

            if ($request->has('flights')) {
                try {
                    $laravelVoyage = Voyage::firstOrCreate(
                        ['wp_post_id' => $tour->ID],
                        ['name' => $tour->post_title ?? 'Tour', 'slug' => 'tour-' . $tour->ID]
                    );
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
        
        // Créer un objet compatible avec les vues existantes
        $voyage = $wpPost;
        $voyage->name = $wpPost->post_title;
        $voyage->slug = $wpPost->post_name;
        $voyage->description = $wpPost->post_content;
        $voyage->accroche = $wpPost->post_excerpt;
        $voyage->updated_at = $wpPost->post_modified;
        $voyage->created_at = $wpPost->post_date;
        $voyage->status = $wpPost->post_status;
        
        // Charger TOUTES les metas Traveler (lecture complète)
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
        if (!empty($meta['gallery'])) {
            $gallery_csv = is_array($meta['gallery']) ? implode(',', $meta['gallery']) : $meta['gallery'];
        }
        
        // Charger les taxonomies disponibles
        $availableTaxonomies = $this->getAvailableTaxonomies();
        
        // Charger les taxonomies assignées à ce tour
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

        // Programme par jours (Laravel: aj_tour_days + activités). Nombre de jours = réel en base.
        $programDays = collect();
        $activitiesCatalog = collect();
        try {
            $this->programService->importWpToursProgramToDayNotesIfEmpty($id);
            $programDays = $this->programService->loadProgram($id);
            $activitiesCatalog = Activity::orderBy('title')->get();
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@edit: could not load program days', ['tour_id' => $id, 'error' => $e->getMessage()]);
        }

        $laravelVoyage = Voyage::where('wp_post_id', $id)->first();
        if (!$laravelVoyage) {
            $laravelVoyage = Voyage::firstOrCreate(
                ['wp_post_id' => $id],
                ['name' => $wpPost->post_title ?? 'Tour', 'slug' => 'tour-' . $id]
            );
        }

        // Charger les TravelProgramDay avec relations hotels/transfers pour le modal par jour
        $travelProgramDaysWithRelations = collect();
        $programDayHotelsTransfers = []; // Structure : [dayId => ['hotel_id' => x, 'transfer_ids' => [...]]]
        try {
            $travelProgramDaysWithRelations = $laravelVoyage->programDays()
                ->with(['hotel', 'transfers'])
                ->orderBy('day_number')
                ->get();
            foreach ($travelProgramDaysWithRelations as $pday) {
                $programDayHotelsTransfers[(string)$pday->id] = [
                    'hotel_id' => $pday->hotel_id,
                    'transfer_ids' => $pday->transfers()->pluck('id')->toArray(),
                ];
            }
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@edit: could not load travel program days with relations', ['voyage_id' => $laravelVoyage->id, 'error' => $e->getMessage()]);
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

        // Hôtel + Transferts (aj_tour_hotels, aj_tour_transfers) — multi-row support
        $tourHotels = TourHotel::getAllForTour($id);
        $tourHotel = $tourHotels->first();
        $transfers = TourTransfer::getForTour($id);
        $transferArrivals = $transfers['arrival'];
        $transferDepartures = $transfers['departure'];
        $transferArrival = $transferArrivals->first();
        $transferDeparture = $transferDepartures->first();
        // Valeurs suggérées : transfert aller = aéroport d'arrivée (vol aller to_city) → hôtel ; transfert retour = hôtel → aéroport de départ (vol retour from_city)
        $suggestedArrivalFrom = $outboundFlight ? trim($outboundFlight->to_city ?? $outboundFlight->to_label ?? '') : '';
        $suggestedArrivalTo = $tourHotel ? trim($tourHotel->hotel_name ?? '') : '';
        $suggestedDepartureFrom = $tourHotel ? trim($tourHotel->hotel_name ?? '') : '';
        $suggestedDepartureTo = $inboundFlight ? trim($inboundFlight->from_city ?? $inboundFlight->from_label ?? '') : '';

        $tourHotelImageUrl = $tourHotel && $tourHotel->image_id ? WpHeroImageService::getAttachmentUrl((int) $tourHotel->image_id) : null;
        $transferArrivalImageUrl = $transferArrival && $transferArrival->image_id ? WpHeroImageService::getAttachmentUrl((int) $transferArrival->image_id) : null;
        $transferDepartureImageUrl = $transferDeparture && $transferDeparture->image_id ? WpHeroImageService::getAttachmentUrl((int) $transferDeparture->image_id) : null;

        // Charger les lieux de départ et leurs vols
        $departurePlaces = TravelDeparturePlace::getActivePlacesForTour($id);
        
        // Charger les dates disponibles
        $travelDates = TravelDate::getActiveDatesForTour($id);

        $programJson = [];
        $programApiUrl = route('admin.circuits.voyages.program.save', ['id' => $id]);
        try {
            $programJson = $this->programJsonService->getProgram($id);
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@edit: getProgram failed', ['tour_id' => $id, 'error' => $e->getMessage()]);
        }

        return view('admin.circuits.voyages.edit', compact('voyage', 'meta', 'gallery_csv', 'availableTaxonomies', 'assignedTaxonomies', 'locationsTree', 'selectedLocationIds', 'worldCountries', 'countryCitiesData', 'mergedCitiesByCode', 'programDays', 'activitiesCatalog', 'airlines', 'laravelVoyage', 'outboundFlight', 'inboundFlight', 'flightOptionsByType', 'flightOptionsWithIndex', 'nextFlightOptionIndex', 'lastDayNumber', 'heroImageUrl', 'tourHotel', 'tourHotels', 'transferArrival', 'transferDeparture', 'transferArrivals', 'transferDepartures', 'suggestedArrivalFrom', 'suggestedArrivalTo', 'suggestedDepartureFrom', 'suggestedDepartureTo', 'tourHotelImageUrl', 'transferArrivalImageUrl', 'transferDepartureImageUrl', 'departurePlaces', 'travelDates', 'programJson', 'programApiUrl', 'programDayHotelsTransfers'));
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
     * Associer les pays du monde (config) aux locations WP (arbre) et produire les données pour le select + villes.
     *
     * @param array $worldCountries [ code => nom ]
     * @param array $locationsTree  [ [ 'id', 'title', 'children' => [...] ], ... ]
     * @return array [ code => [ 'id' => wpId, 'title' => nom, 'cities' => [ [ 'id', 'title' ], ... ] ], ... ]
     */
    private function buildCountryCitiesData(array $worldCountries, array $locationsTree): array
    {
        $normalize = function (string $s): string {
            $s = mb_strtolower($s, 'UTF-8');
            $accents = ['à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','æ'=>'ae','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ý'=>'y','ÿ'=>'y','œ'=>'oe'];
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
     * Construire la liste fusionnée Pays → Villes (catalogue + WP) pour l’UI.
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
            $accents = ['à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','æ'=>'ae','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ý'=>'y','ÿ'=>'y','œ'=>'oe'];
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

            // D’abord les villes du catalogue world_cities
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
     * Sync hotels for tour: replace all by request data (tour_hotels array or single tour_hotel).
     * Writes day_number, is_optional, sort_order for multi-day circuits.
     */
    private function syncTourHotels(int $tourId, \Illuminate\Http\Request $request): void
    {
        $items = [];
        if ($request->has('tour_hotels') && is_array($request->input('tour_hotels'))) {
            foreach ($request->input('tour_hotels') as $raw) {
                if (!is_array($raw)) {
                    continue;
                }
                $items[] = [
                    'day_number' => isset($raw['day_number']) && $raw['day_number'] !== '' ? max(1, (int) $raw['day_number']) : 1,
                    'is_optional' => !empty($raw['is_optional']) ? 1 : 0,
                    'hotel_name' => $raw['hotel_name'] ?? null,
                    'stars' => isset($raw['stars']) && $raw['stars'] !== '' ? (int) $raw['stars'] : null,
                    'address' => $raw['address'] ?? null,
                    'room_type' => $raw['room_type'] ?? null,
                    'meal_plan' => $raw['meal_plan'] ?? null,
                    'notes' => $raw['notes'] ?? null,
                    'image_id' => isset($raw['image_id']) && $raw['image_id'] !== '' ? (int) $raw['image_id'] : null,
                ];
            }
        } elseif ($request->has('tour_hotel')) {
            $raw = $request->input('tour_hotel', []);
            $items[] = [
                'day_number' => 1,
                'is_optional' => 0,
                'hotel_name' => $raw['hotel_name'] ?? null,
                'stars' => isset($raw['stars']) && $raw['stars'] !== '' ? (int) $raw['stars'] : null,
                'address' => $raw['address'] ?? null,
                'room_type' => $raw['room_type'] ?? null,
                'meal_plan' => $raw['meal_plan'] ?? null,
                'notes' => $raw['notes'] ?? null,
                'image_id' => isset($raw['image_id']) && $raw['image_id'] !== '' ? (int) $raw['image_id'] : null,
            ];
        }
        TourHotel::where('tour_id', $tourId)->delete();
        $sortOrder = 0;
        $contentKeys = ['hotel_name', 'stars', 'address', 'room_type', 'meal_plan', 'notes', 'image_id'];
        foreach ($items as $data) {
            $content = array_intersect_key($data, array_flip($contentKeys));
            if (array_filter($content, fn ($v) => $v !== null && $v !== '')) {
                TourHotel::create(array_merge($data, ['tour_id' => $tourId, 'sort_order' => $sortOrder++]));
            }
        }
    }

    /**
     * Sync transfers for tour: replace all by request (tour_transfer_arrivals / tour_transfer_departures or single).
     * Writes day_number, is_optional, sort_order. Default: arrival = day 1, departure = lastDayNumber.
     */
    private function syncTourTransfers(int $tourId, \Illuminate\Http\Request $request, int $lastDayNumber = 1): void
    {
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
            ]);
        }
    }

    /**
     * Sync departure places and their flights for tour.
     * Les lieux sans vols ne seront pas sauvegardés.
     */
    private function syncDeparturePlaces(int $tourId, \Illuminate\Http\Request $request): void
    {
        // Debug logging
        \Log::info('VoyageController@syncDeparturePlaces', [
            'tour_id' => $tourId,
            'request_data' => $request->input('departure_places', [])
        ]);

        $places = $request->input('departure_places', []);
        if (!is_array($places) || empty($places)) {
            \Log::info('VoyageController@syncDeparturePlaces: No places data', ['places' => $places]);
            // Si aucune donnée, supprimer tout
            $oldPlaceIds = TravelDeparturePlace::where('travel_id', $tourId)->pluck('id');
            if ($oldPlaceIds->isNotEmpty()) {
                TravelDepartureFlight::whereIn('departure_place_id', $oldPlaceIds)->delete();
            }
            TravelDeparturePlace::where('travel_id', $tourId)->delete();
            return;
        }

        // Supprimer les anciens lieux et leurs vols
        $oldPlaceIds = TravelDeparturePlace::where('travel_id', $tourId)->pluck('id');
        if ($oldPlaceIds->isNotEmpty()) {
            TravelDepartureFlight::whereIn('departure_place_id', $oldPlaceIds)->delete();
        }
        TravelDeparturePlace::where('travel_id', $tourId)->delete();

        $sortOrder = 0;
        $savedCount = 0;
        
        foreach ($places as $placeIndex => $placeData) {
            if (!is_array($placeData)) {
                \Log::warning('VoyageController@syncDeparturePlaces: Invalid place data', ['index' => $placeIndex, 'data' => $placeData]);
                continue;
            }

            // Vérifier qu'il y a au moins un vol pour ce lieu
            $flights = $placeData['flights'] ?? [];
            if (!is_array($flights) || empty($flights)) {
                \Log::info('VoyageController@syncDeparturePlaces: Place without flights skipped', ['place_name' => $placeData['name'] ?? 'unnamed']);
                continue; // Ignorer les lieux sans vols
            }

            // Vérifier qu'il y a au moins un vol avec des données
            $hasValidFlight = false;
            foreach ($flights as $flightIndex => $flight) {
                if (is_array($flight)) {
                    // Accepter le vol s'il a au moins un champ rempli
                    if (!empty($flight['airline']) || !empty($flight['flight_number']) || !empty($flight['from_airport']) || !empty($flight['to_airport'])) {
                        $hasValidFlight = true;
                        break;
                    }
                }
            }

            if (!$hasValidFlight) {
                \Log::info('VoyageController@syncDeparturePlaces: Place without valid flight data skipped', ['place_name' => $placeData['name'] ?? 'unnamed']);
                continue; // Ignorer si aucun vol valide
            }

            // Vérifier que le nom du lieu n'est pas vide
            $placeName = trim($placeData['name'] ?? '');
            if (empty($placeName)) {
                \Log::warning('VoyageController@syncDeparturePlaces: Place with empty name skipped', ['place_data' => $placeData]);
                continue;
            }

            // Créer le lieu
            try {
                $place = TravelDeparturePlace::create([
                    'travel_id' => $tourId,
                    'name' => $placeName,
                    'code' => !empty($placeData['code']) ? trim($placeData['code']) : null,
                    'is_active' => isset($placeData['is_active']) ? (bool) $placeData['is_active'] : true,
                    'sort_order' => $sortOrder++,
                ]);

                // Créer les vols pour ce lieu
                $flightSortOrder = 0;
                $flightCount = 0;
                foreach ($flights as $flightData) {
                    if (!is_array($flightData)) {
                        continue;
                    }

                    // Ne créer que les vols qui ont au moins un champ rempli
                    if (empty($flightData['airline']) && empty($flightData['flight_number']) && empty($flightData['from_airport']) && empty($flightData['to_airport'])) {
                        continue;
                    }

                    TravelDepartureFlight::create([
                        'departure_place_id' => $place->id,
                        'airline' => !empty($flightData['airline']) ? trim($flightData['airline']) : null,
                        'flight_number' => !empty($flightData['flight_number']) ? trim($flightData['flight_number']) : null,
                        'from_airport' => !empty($flightData['from_airport']) ? trim($flightData['from_airport']) : null,
                        'to_airport' => !empty($flightData['to_airport']) ? trim($flightData['to_airport']) : null,
                        'depart_time' => !empty($flightData['depart_time']) ? $flightData['depart_time'] : null,
                        'arrive_time' => !empty($flightData['arrive_time']) ? $flightData['arrive_time'] : null,
                        'notes' => !empty($flightData['notes']) ? trim($flightData['notes']) : null,
                        'sort_order' => $flightSortOrder++,
                    ]);
                    $flightCount++;
                }

                if ($flightCount > 0) {
                    $savedCount++;
                    \Log::info('VoyageController@syncDeparturePlaces: Place saved', [
                        'place_id' => $place->id,
                        'place_name' => $place->name,
                        'flight_count' => $flightCount
                    ]);
                } else {
                    // Supprimer le lieu si aucun vol n'a été créé
                    $place->delete();
                    \Log::warning('VoyageController@syncDeparturePlaces: Place deleted (no valid flights)', ['place_name' => $placeName]);
                }
            } catch (\Exception $e) {
                \Log::error('VoyageController@syncDeparturePlaces: Error saving place', [
                    'place_name' => $placeName,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        \Log::info('VoyageController@syncDeparturePlaces: Completed', [
            'tour_id' => $tourId,
            'places_saved' => $savedCount,
            'total_places_in_request' => count($places)
        ]);
    }

    /**
     * Sync travel dates for tour.
     */
    private function syncTravelDates(int $tourId, \Illuminate\Http\Request $request): void
    {
        $dates = $request->input('travel_dates', []);
        if (!is_array($dates)) {
            return;
        }

        // Supprimer les anciennes dates
        TravelDate::where('travel_id', $tourId)->delete();

        // Créer les nouvelles dates
        foreach ($dates as $dateData) {
            if (!is_array($dateData)) {
                continue;
            }

            $date = $dateData['date'] ?? null;
            if (empty($date)) {
                continue; // Ignorer si pas de date
            }

            TravelDate::create([
                'travel_id' => $tourId,
                'date' => $date,
                'is_active' => isset($dateData['is_active']) ? (bool) $dateData['is_active'] : true,
                'seats' => isset($dateData['seats']) && $dateData['seats'] !== '' ? (int) $dateData['seats'] : null,
                'price_override' => isset($dateData['price_override']) && $dateData['price_override'] !== '' ? $dateData['price_override'] : null,
            ]);
        }
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

    /**
     * Update tour in WordPress.
     */
    public function update(UpdateWpTourRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();

        // Option "Utiliser l'image principale comme image à la une WP"
        if (!empty($validated['hero_use_as_thumbnail']) && !empty($validated['hero_image_id'])) {
            $validated['thumbnail_id'] = $validated['hero_image_id'];
        }

        // Convertir gallery CSV en array
        if (!empty($validated['gallery_ids'])) {
            $validated['gallery_ids'] = array_filter(array_map('trim', explode(',', $validated['gallery_ids'])));
        }

        try {
            $this->repository->updateTour($id, $validated);

            // Programme par jours uniquement (aj_tour_days + aj_tour_day_activities). Plus d'édition tours_program.
            if ($request->has('programme_days')) {
                try {
                    $this->syncProgrammeDaysAndActivities($id, $request);
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
                ['name' => optional($this->repository->getPost($id))->post_title ?? 'Tour', 'slug' => 'tour-' . $id]
            );
            $lastDayNumber = 1;
            try {
                $program = $this->programJsonService->getProgram($id);
                $lastDayNumber = max(1, count($program['program_days'] ?? []));
            } catch (\Throwable $e) {
                // keep 1
            }
            if ($request->has('flight_options') && is_array($request->input('flight_options'))) {
                try {
                    $this->voyageFlightOptionService->syncOptions($laravelVoyage->id, $request->input('flight_options'), $lastDayNumber);
                } catch (\Throwable $e) {
                    \Log::error('VoyageController@update flight options failed', ['tour_id' => $id, 'message' => $e->getMessage()]);
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

            // Hôtel + Transferts (aj_tour_hotels, aj_tour_transfers) — multi-row par jour
            $this->syncTourHotels($id, $request);
            $this->syncTourTransfers($id, $request, $lastDayNumber);

            // Synchroniser les lieux de départ et les dates disponibles
            $this->syncDeparturePlaces($id, $request);
            $this->syncTravelDates($id, $request);

            // Toujours synchroniser les vols Laravel → WP après chaque enregistrement (pour que le plugin affiche les vols)
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
                ->with('success', 'Tour mis à jour avec succès dans WordPress ! Modifications visibles immédiatement.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Erreur lors de la mise à jour : ' . $e->getMessage()]);
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
    protected function syncProgrammeDaysAndActivities(int $tourId, UpdateWpTourRequest $request): void
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
        foreach ($programmeDays as $i => $dayRow) {
            $dayId = (int) ($orderedDayIds[$i] ?? 0);
            if ($dayId <= 0) {
                continue;
            }
            $dayTitle = isset($dayRow['day_title']) ? trim((string) $dayRow['day_title']) : null;
            $notes = isset($dayRow['notes']) ? trim((string) $dayRow['notes']) : null;
            $this->programService->updateDay($dayId, [
                'mode' => $dayRow['mode'] ?? 'program',
                'day_title' => $dayTitle !== '' ? $dayTitle : null,
                'notes' => $notes !== '' ? $notes : null,
                'title' => $dayRow['title'] ?? null,
                'description' => $dayRow['description'] ?? null,
            ]);

            $activities = $dayRow['activities'] ?? [];
            if (!is_array($activities)) {
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

            // Sync hotel & transfers for this day (per-day model)
            $this->syncDayHotelsAndTransfers($dayId, $dayRow);
        }

        $current = TourDayActivity::where('tour_id', $tourId)->get();
        foreach ($current as $da) {
            if (in_array($da->id, $submittedDayActivityIds)) {
                continue;
            }
            if ($da->is_mandatory) {
                continue;
            }
            $this->programService->removeDayActivity($da->id);
        }
    }

    /**
     * Sync hotel and transfers for a specific TravelProgramDay.
     * - $dayId: TravelProgramDay.id
     * - $dayRow: current programme_days[$i] request array
     */
    protected function syncDayHotelsAndTransfers(int $dayId, array $dayRow): void
    {
        // Récupérer le jour
        $day = TravelProgramDay::find($dayId);
        if (!$day) {
            return;
        }

        // Syncer l'hôtel (0..1)
        $hotelId = !empty($dayRow['hotel_id']) ? (int) $dayRow['hotel_id'] : null;
        if ($hotelId) {
            // Valider que l'hôtel existe
            $hotel = TourHotel::find($hotelId);
            if ($hotel) {
                $day->update(['hotel_id' => $hotelId]);
            } else {
                $day->update(['hotel_id' => null]);
            }
        } else {
            $day->update(['hotel_id' => null]);
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
        if (!empty($transferIds)) {
            $validIds = TourTransfer::whereIn('id', $transferIds)->pluck('id')->toArray();
            $day->transfers()->sync($validIds);
        } else {
            $day->transfers()->detach();
        }
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
                ->with('success', 'Jour supprimé.')
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
                ->with('success', 'Tour supprimé avec succès de WordPress !');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
        }
    }
}
