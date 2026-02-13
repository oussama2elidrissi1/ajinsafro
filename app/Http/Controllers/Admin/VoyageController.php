<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWpTourRequest;
use App\Http\Requests\UpdateWpTourRequest;
use App\Models\Wp\WpPost;
use App\Models\Wp\Activity;
use App\Models\Wp\TourDayActivity;
use App\Models\Voyage;
use App\Models\Airline;
use App\Models\TourHotel;
use App\Models\TourTransfer;
use App\Services\VoyageFlightService;
use App\Services\VoyageFlightOptionService;
use App\Services\Wp\ProgramJsonService;
use App\Services\Wp\TourProgramService;
use App\Services\Wp\WpHeroImageService;
use App\Services\Wp\WpTourRepository;
use Illuminate\Http\RedirectResponse;
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

        $programJson = [];
        $programApiUrl = route('admin.circuits.voyages.program.save', ['id' => $id]);
        try {
            $programJson = $this->programJsonService->getProgram($id);
        } catch (\Throwable $e) {
            \Log::warning('VoyageController@edit: getProgram failed', ['tour_id' => $id, 'error' => $e->getMessage()]);
        }

        return view('admin.circuits.voyages.edit', compact('voyage', 'meta', 'gallery_csv', 'availableTaxonomies', 'assignedTaxonomies', 'locationsTree', 'selectedLocationIds', 'programDays', 'activitiesCatalog', 'airlines', 'laravelVoyage', 'outboundFlight', 'inboundFlight', 'flightOptionsByType', 'flightOptionsWithIndex', 'nextFlightOptionIndex', 'lastDayNumber', 'heroImageUrl', 'tourHotel', 'tourHotels', 'transferArrival', 'transferDeparture', 'transferArrivals', 'transferDepartures', 'suggestedArrivalFrom', 'suggestedArrivalTo', 'suggestedDepartureFrom', 'suggestedDepartureTo', 'tourHotelImageUrl', 'transferArrivalImageUrl', 'transferDepartureImageUrl', 'programJson', 'programApiUrl'));
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
     * Sync hotels for tour: replace all by request data (tour_hotels array or single tour_hotel).
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
        foreach ($items as $data) {
            if (array_filter($data, fn ($v) => $v !== null && $v !== '')) {
                TourHotel::create(array_merge($data, ['tour_id' => $tourId, 'sort_order' => $sortOrder++]));
            }
        }
    }

    /**
     * Sync transfers for tour: replace all by request (tour_transfer_arrivals / tour_transfer_departures or single tour_transfer_arrival / tour_transfer_departure).
     */
    private function syncTourTransfers(int $tourId, \Illuminate\Http\Request $request): void
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
            TourTransfer::create([
                'tour_id' => $tourId,
                'direction' => TourTransfer::DIRECTION_ARRIVAL,
                'sort_order' => $sortOrder++,
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
            TourTransfer::create([
                'tour_id' => $tourId,
                'direction' => TourTransfer::DIRECTION_DEPARTURE,
                'sort_order' => $sortOrder++,
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

            // Hôtel + Transferts (aj_tour_hotels, aj_tour_transfers) — multi-row: replace all for this tour
            $this->syncTourHotels($id, $request);
            $this->syncTourTransfers($id, $request);

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
