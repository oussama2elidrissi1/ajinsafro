<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Departure;
use App\Models\Reservation;
use App\Models\TravelDate;
use App\Models\Voyage;
use App\Models\Wp\WpPost;
use App\Services\AdminWpTourCatalogQuery;
use App\Services\ReservationService;
use App\Services\Reservations\ReservationPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReservationsController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService,
        protected ReservationPricingService $reservationPricing
    ) {}

    private function getPartner(Request $request): \App\Models\Partner
    {
        return $request->user()->partner;
    }

    private function scopeReservations(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return Reservation::query()->where('partner_id', $this->getPartner($request)->id);
    }

    public function index(Request $request): View
    {
        $query = $this->scopeReservations($request)->with(['offer:id,name', 'creator:id,name,email', 'branch:id,name', 'partner:id,name']);
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        $reservations = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        return view('partner.v2.reservations.index', compact('reservations'));
    }

    public function create(Request $request): View
    {
        $partner = $this->getPartner($request);

        $voyageIdParam = $request->query('voyage_id');
        $tourIdParam = $request->query('tour_id');
        $requestedTourId = (int) ($voyageIdParam !== null ? $voyageIdParam : ($tourIdParam ?? 0));
        $travelDateId = (int) $request->query('travel_date_id', 0);
        $requestedDepartureId = (int) $request->query('departure_id', 0);

        $voyages = AdminWpTourCatalogQuery::reservableVoyages();
        $allowedVoyageIds = $partner->voyageAccess()->pluck('voyages.id')->all();
        if (! empty($allowedVoyageIds)) {
            $voyages = $voyages->whereIn('id', $allowedVoyageIds)->values();
        }

        // Compat: `tour_id` peut être un wp_post_id (WordPress) dans certains liens historiques.
        if ($requestedTourId > 0 && $voyageIdParam === null) {
            $voyageByWpId = Voyage::query()
                ->with(['extras' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
                ->where('wp_post_id', $requestedTourId)
                ->where('status', 'actif')
                ->orderByDesc('id')
                ->first();

            if ($voyageByWpId) {
                $requestedTourId = (int) $voyageByWpId->id;
                $voyages = $voyages->prepend($voyageByWpId)->unique('id')->values();
            }
        }

        $wpPostIds = $voyages->pluck('wp_post_id')->filter()->unique()->values()->all();
        $wpTitles = collect();
        if (! empty($wpPostIds)) {
            try {
                $wpTitles = WpPost::query()
                    ->whereIn('ID', $wpPostIds)
                    ->get(['ID', 'post_title'])
                    ->keyBy('ID');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $preselectedTourId = null;
        if ($requestedTourId > 0 && $voyages->contains('id', $requestedTourId)) {
            $preselectedTourId = $requestedTourId;
        }

        [$extrasByVoyage] = $this->partnerExtrasByVoyage($voyages);

        $selectedDeparture = null;
        if ($requestedDepartureId > 0) {
            $selectedDeparture = Departure::query()->where('id', $requestedDepartureId)->first();
        }

        $selectedTravelDate = null;
        if ($travelDateId > 0) {
            try {
                if (Schema::hasTable('travel_dates')) {
                    $selectedTravelDate = TravelDate::query()->where('is_active', true)->find($travelDateId);
                }
            } catch (\Throwable $e) {
                // ignore if DB connection doesn't have travel_dates (some partner deployments)
            }
        }

        return view('partner.v2.reservations.create-v2', [
            'voyages' => $voyages,
            'wpTitles' => $wpTitles,
            'selectedTravelDate' => $selectedTravelDate,
            'selectedDepartureId' => $selectedDeparture?->id,
            'travelDateId' => $travelDateId > 0 ? $travelDateId : null,
            'preselectedTourId' => $preselectedTourId,
            'extrasByVoyage' => $extrasByVoyage,
            'selectedUnitPrice' => null,
        ]);
    }

    public function voyageDepartures(Request $request): JsonResponse
    {
        $partner = $this->getPartner($request);
        $tourId = (int) $request->query('tour_id', 0);
        if ($tourId <= 0) {
            return response()->json(['departures' => []]);
        }

        $voyage = Voyage::find($tourId);
        if (! $voyage || $voyage->status !== 'actif') {
            return response()->json(['departures' => []]);
        }

        $allowedVoyageIds = $partner->voyageAccess()->pluck('voyages.id')->all();
        if (! empty($allowedVoyageIds) && ! in_array($voyage->id, $allowedVoyageIds, true)) {
            return response()->json(['departures' => []]);
        }

        $deps = Departure::query()
            ->where('voyage_id', $voyage->id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $travelDates = collect();
        try {
            if (Schema::hasTable('travel_dates')) {
                $travelDates = TravelDate::query()
                    ->whereIn('id', $deps->pluck('wp_travel_date_id')->filter()->unique()->values()->all())
                    ->get()
                    ->keyBy('id');
            }
        } catch (\Throwable $e) {
            $travelDates = collect();
        }

        return response()->json([
            'departures' => $deps->map(function (Departure $d) use ($travelDates, $voyage) {
                $travelDate = $d->wp_travel_date_id ? $travelDates->get((int) $d->wp_travel_date_id) : null;
                $resolvedPrice = $this->reservationPricing->resolveUnitPrice($voyage, $d, $travelDate);

                return [
                    'id' => $d->id,
                    'label' => ($d->start_date ? $d->start_date->format('d/m/Y') : '-')
                        .($d->end_date ? ' -> '.$d->end_date->format('d/m/Y') : ''),
                    'status' => $d->status,
                    'available_capacity' => (int) ($d->available_capacity ?? 0),
                    'base_price' => $d->base_price !== null ? (float) $d->base_price : null,
                    'sale_price' => $d->sale_price !== null ? (float) $d->sale_price : null,
                    'unit_price' => $resolvedPrice['unit_price'],
                    'wp_travel_date_id' => $d->wp_travel_date_id,
                ];
            })->values()->all(),
        ]);
    }

    public function departureHotelsRooms(Request $request): JsonResponse
    {
        try {
            $payload = $this->reservationPricing->previewDepartureSelection($request->all());

            return response()->json([
                'success' => (bool) ($payload['success'] ?? false),
                'mode' => (string) ($payload['mode'] ?? 'error'),
                'rooms_source' => $payload['rooms_source'] ?? null,
                'message' => $payload['message'] ?? null,
                'departure' => $payload['departure'] ?? null,
                'pricing' => $payload['pricing'] ?? null,
                'rooms' => $payload['rooms'] ?? [],
                'count' => is_array($payload['rooms'] ?? null) ? count($payload['rooms']) : 0,
            ], 200);
        } catch (ValidationException $e) {
            Log::warning('Partner reservation rooms endpoint validation failed', [
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'tour_id' => $request->input('tour_id'),
                'departure_id' => $request->input('departure_id'),
                'travel_date_id' => $request->input('travel_date_id'),
            ]);

            return response()->json([
                'success' => false,
                'mode' => 'error',
                'message' => 'Erreur de chargement des disponibilités du départ.',
                'debug' => config('app.debug') ? $e->errors() : null,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Partner reservation rooms endpoint failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->toArray(),
                'tour_id' => $request->input('tour_id'),
                'departure_id' => $request->input('departure_id'),
                'travel_date_id' => $request->input('travel_date_id'),
            ]);

            return response()->json([
                'success' => false,
                'mode' => 'error',
                'message' => 'Erreur de chargement des disponibilités du départ.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 200);
        }
    }

    /**
     * @param  Collection<int, Voyage>  $voyages
     * @return array{0: array<string, array<int, array<string, mixed>>>}
     */
    private function partnerExtrasByVoyage(Collection $voyages): array
    {
        $extrasByVoyage = [];

        foreach ($voyages as $voyage) {
            $resolved = $this->resolvePartnerVoyageExtras($voyage);
            /** @var Collection<int, mixed> $extras */
            $extras = $resolved['extras'];
            $voyageKey = (string) $voyage->id;

            $items = $extras
                ->values()
                ->map(fn ($extra) => [
                    'id' => (int) $extra->id,
                    'type' => 'voyage_extra',
                    'source_type' => 'voyage_extra',
                    'source_id' => (int) $extra->id,
                    'source_voyage_id' => (int) ($resolved['source_voyage_id'] ?? $voyage->id),
                    'name' => (string) $extra->name,
                    'description' => (string) ($extra->description ?? ''),
                    'price_adult' => (float) ($extra->price_adult ?? 0),
                    'price_child' => (float) ($extra->price_child ?? 0),
                    'extra_type' => (string) ($extra->extra_type ?? ''),
                    'icon' => (string) ($extra->icon ?? 'fa-plus-circle'),
                ])
                ->values()
                ->all();

            $extrasByVoyage[$voyageKey] = $items;
        }

        return [$extrasByVoyage];
    }

    /**
     * @return array{extras: Collection<int, mixed>, source: string, source_voyage_id: int|null}
     */
    private function resolvePartnerVoyageExtras(Voyage $voyage): array
    {
        $directExtras = $voyage->relationLoaded('extras')
            ? $voyage->extras->where('is_active', true)->values()
            : $voyage->extras()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        if ($directExtras->isNotEmpty()) {
            return [
                'extras' => $directExtras,
                'source' => 'direct_voyage_extras',
                'source_voyage_id' => (int) $voyage->id,
            ];
        }

        $wpPostId = (int) ($voyage->wp_post_id ?? 0);
        if ($wpPostId <= 0) {
            return [
                'extras' => $directExtras,
                'source' => 'none',
                'source_voyage_id' => null,
            ];
        }

        $fallbackVoyage = Voyage::query()
            ->with(['extras' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
            ->where('wp_post_id', $wpPostId)
            ->where('status', 'actif')
            ->where('id', '!=', (int) $voyage->id)
            ->whereHas('extras', fn ($q) => $q->where('is_active', true))
            ->orderByDesc('id')
            ->first();

        if ($fallbackVoyage && $fallbackVoyage->extras->isNotEmpty()) {
            return [
                'extras' => $fallbackVoyage->extras->values(),
                'source' => 'fallback_same_wp_post_id',
                'source_voyage_id' => (int) $fallbackVoyage->id,
            ];
        }

        return [
            'extras' => $directExtras,
            'source' => 'none',
            'source_voyage_id' => null,
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $partner = $this->getPartner($request);

        $rules = [
            'tour_id' => ['required', 'exists:voyages,id'],
            'departure_id' => ['nullable', 'exists:departures,id'],
            'travel_date_id' => ['nullable', 'integer'],
            'client_mode' => ['in:existing,new'],
            'client_external_id' => ['nullable', 'exists:clients,id'],
            'client_first_name' => ['required_without:client_external_id', 'nullable', 'string', 'max:100'],
            'client_last_name' => ['required_without:client_external_id', 'nullable', 'string', 'max:100'],
            'client_email' => ['nullable', 'email', 'max:190'],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'payment_type' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'passengers_count' => ['nullable', 'integer', 'min:1'],
            'extras_json' => ['nullable', 'string'],
            'room_allocations_json' => ['nullable', 'string'],
            'accommodation_mode' => ['nullable', 'in:rooms,places_only,blocked'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
        ];

        if (Schema::hasTable('travel_dates')) {
            $rules['travel_date_id'][] = 'exists:travel_dates,id';
        }

        $data = $request->validate($rules);

        if (($data['client_mode'] ?? null) === 'existing' && empty($data['client_external_id'])) {
            throw ValidationException::withMessages([
                'client_external_id' => ['Veuillez sélectionner un client existant.'],
            ]);
        }

        if (($request->has('extras_json') || $request->has('room_allocations_json')) && empty($data['departure_id'])) {
            throw ValidationException::withMessages([
                'departure_id' => ['Veuillez sélectionner un départ.'],
            ]);
        }

        if (! empty($data['departure_id'])) {
            $departure = Departure::find((int) $data['departure_id']);
            if (! $departure || (int) $departure->voyage_id !== (int) $data['tour_id']) {
                throw ValidationException::withMessages([
                    'departure_id' => ['Départ invalide pour ce voyage.'],
                ]);
            }
        }

        $allowedVoyageIds = $partner->voyageAccess()->pluck('voyages.id')->all();
        if (! empty($allowedVoyageIds) && ! in_array((int) $data['tour_id'], $allowedVoyageIds, true)) {
            abort(403);
        }
        $data['partner_id'] = $partner->id;
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['agent_id'] = $request->user()->id;
        $data['client_mode'] = $data['client_mode'] ?? ($data['client_external_id'] ? 'existing' : 'new');
        if (!empty($data['client_external_id'])) {
            $client = Client::where('partner_id', $partner->id)->findOrFail($data['client_external_id']);
            $data['client_first_name'] = $client->first_name;
            $data['client_last_name'] = $client->last_name;
            $data['client_email'] = $client->email;
            $data['client_phone'] = $client->phone;
        }
        $data['passengers'] = $this->mergeMainTravelerIntoPassengers($request, $data);
        $data['hotel_rooms'] = [];
        $data['room_allocations_payload'] = $this->extractRoomAllocationsPayloadFromRequest($request);
        $data['extras_payload'] = $this->extractExtrasPayloadFromRequest($request);
        $data['accommodation_mode'] = (string) ($data['accommodation_mode'] ?? 'rooms');
        $this->reservationService->create($data);
        return redirect()->route('partner.reservations.index')->with('success', 'Réservation créée.');
    }

    private function mergeMainTravelerIntoPassengers(Request $request, array $data): array
    {
        $passengers = is_array($request->input('passengers')) ? $request->input('passengers') : [];
        $mainTravelerType = (string) ($request->input('client_traveler_type') ?? 'adult');

        $main = [
            'first_name' => $data['client_first_name'] ?? null,
            'last_name' => $data['client_last_name'] ?? null,
            'email' => $data['client_email'] ?? null,
            'phone' => $data['client_phone'] ?? null,
            'gender' => $request->input('client_gender') ?? null,
            'type' => $mainTravelerType,
            'relationship_to_main' => 'main',
            'is_main' => true,
            'traveler_key' => 'main',
            'consumes_bed' => $mainTravelerType !== 'infant',
        ];

        $passengers = array_merge(['main' => $main], $passengers);

        foreach ($passengers as $key => $row) {
            if ($key === 'main') {
                continue;
            }
            if (! is_array($row)) {
                unset($passengers[$key]);
                continue;
            }
            $first = trim((string) ($row['first_name'] ?? ''));
            $last = trim((string) ($row['last_name'] ?? ''));
            if ($first === '' && $last === '') {
                unset($passengers[$key]);
            }
        }

        return $passengers;
    }

    private function extractExtrasPayloadFromRequest(Request $request): array
    {
        if (! $request->filled('extras_json')) {
            return [];
        }
        $decoded = json_decode((string) $request->input('extras_json'), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function extractRoomAllocationsPayloadFromRequest(Request $request): array
    {
        if (! $request->filled('room_allocations_json')) {
            return [];
        }
        $decoded = json_decode((string) $request->input('room_allocations_json'), true);
        return is_array($decoded) ? $decoded : [];
    }

    public function show(Request $request, Reservation $reservation): View|RedirectResponse
    {
        $partner = $this->getPartner($request);
        if ($reservation->partner_id !== $partner->id) {
            abort(403);
        }
        $reservation->load(['offer', 'partner', 'branch', 'creator']);
        return view('partner.reservations.show', compact('reservation'));
    }

    public function edit(Request $request, Reservation $reservation): View|RedirectResponse
    {
        $partner = $this->getPartner($request);
        if ($reservation->partner_id !== $partner->id) {
            abort(403);
        }
        $clients = Client::where('partner_id', $partner->id)->orderBy('full_name')->get(['id', 'client_code', 'full_name', 'email', 'phone']);
        $voyages = Voyage::orderBy('name')->get(['id', 'name']);
        $travelDates = TravelDate::where('is_active', true)->orderBy('date')->get();
        $reservation->load(['offer', 'passengers', 'reservationRooms', 'creator', 'branch', 'partner']);
        return view('partner.reservations.edit', compact('reservation', 'clients', 'voyages', 'travelDates'));
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        $partner = $this->getPartner($request);
        if ($reservation->partner_id !== $partner->id) {
            abort(403);
        }
        $data = $request->validate([
            'tour_id' => ['required', 'exists:voyages,id'],
            'travel_date_id' => ['nullable', 'exists:travel_dates,id'],
            'client_first_name' => ['nullable', 'string', 'max:100'],
            'client_last_name' => ['nullable', 'string', 'max:100'],
            'client_email' => ['nullable', 'email', 'max:190'],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'payment_type' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'status' => [
                'nullable',
                'string',
                Rule::in([
                    Reservation::STATUS_DRAFT,
                    Reservation::STATUS_PENDING,
                    Reservation::STATUS_OPTION,
                    Reservation::STATUS_CONFIRMED,
                    Reservation::STATUS_PARTIALLY_PAID,
                    Reservation::STATUS_PAID,
                    Reservation::STATUS_CANCELLED,
                    Reservation::STATUS_EXPIRED,
                    Reservation::STATUS_REFUNDED,
                    'EN_COURS',
                    'VALIDEE',
                    'ANNULEE',
                ]),
            ],
        ]);
        if (! empty($data['status'])) {
            $data['status'] = match ($data['status']) {
                'EN_COURS' => Reservation::STATUS_PENDING,
                'VALIDEE' => Reservation::STATUS_CONFIRMED,
                'ANNULEE' => Reservation::STATUS_CANCELLED,
                default => $data['status'],
            };
        }
        $data['partner_id'] = $partner->id;
        $data['updated_by'] = $request->user()->id;
        $data['passengers'] = $reservation->passengers->toArray();
        $data['hotel_rooms'] = [];
        $this->reservationService->update($reservation, $data);
        return redirect()->route('partner.reservations.index')->with('success', 'Réservation mise à jour.');
    }

    public function destroy(Request $request, Reservation $reservation): RedirectResponse
    {
        $partner = $this->getPartner($request);
        if ($reservation->partner_id !== $partner->id) {
            abort(403);
        }
        $this->reservationService->delete($reservation);
        return redirect()->route('partner.reservations.index')->with('success', 'Réservation supprimée.');
    }
}
