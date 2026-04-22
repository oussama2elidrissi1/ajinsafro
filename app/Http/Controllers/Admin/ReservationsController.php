<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Departure;
use App\Models\DepartureHotelRoom;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\ReservationExtra;
use App\Models\TourHotel;
use App\Models\TravelDate;
use App\Models\User;
use App\Models\Voyage;
use App\Models\Wp\WpPost;
use App\Services\BranchScopeService;
use App\Services\ReservationHubTableProfile;
use App\Services\ReservationListQueryService;
use App\Services\ReservationService;
use App\Services\Wp\WpHeroImageService;
use App\Services\Wp\WpTourRepository;
use App\Support\AdminReservationFlash;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReservationsController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService,
        protected WpTourRepository $wpTourRepository,
        protected BranchScopeService $branchScope,
        protected ReservationListQueryService $reservationListQuery,
        protected ReservationHubTableProfile $reservationHubTableProfile,
    ) {}

    /**
     * Hub unique : liste, filtres, stats alignées, actions (modals / tiroir).
     */
    public function index(Request $request): View
    {
        return $this->renderList($request);
    }

    /**
     * JSON debug : toutes les réservations du périmètre courant (mêmes filtres que le hub), max 500.
     * Uniquement si APP_DEBUG — pour tester l’affichage / la cohérence des données.
     */
    public function hubDebug(Request $request): JsonResponse
    {
        abort_unless(config('app.debug'), 404);
        abort_unless($request->user()->can('reservations.view'), 403);

        $base = $this->hubFilteredReservationBuilder($request);
        $hubStats = $this->reservationListQuery->aggregateStatusCounts(clone $base);

        $rows = (clone $base)
            ->with([
                'passengers:id,reservation_id,first_name,last_name',
                'offer:id,name,wp_post_id',
                'travelDate:id,date',
                'creator:id,name,email',
                'branch:id,name',
                'partner:id,name',
            ])
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $items = $rows->map(function (Reservation $r) {
            return [
                'id' => $r->id,
                'tour_id' => $r->tour_id,
                'tour_name' => $r->offer?->name,
                'tour_wp_post_id' => $r->offer?->wp_post_id,
                'wp_tour_post_id' => $r->wp_tour_post_id ?? null,
                'catalog_source_code' => $r->catalog_source_code ?? null,
                'voyage_flight_id' => $r->voyage_flight_id ?? null,
                'prestation_type' => $r->prestation_type,
                'travel_date_id' => $r->travel_date_id,
                'travel_date' => $r->travelDate?->date?->format('Y-m-d'),
                'status' => $r->status,
                'created_at' => $r->created_at?->toIso8601String(),
                'creator_name' => $r->creator?->name,
                'agency_name' => $r->agency_label,
                'client_snapshot' => trim(($r->client_first_name ?? '').' '.($r->client_last_name ?? '')),
                'passengers_count' => $r->passengers->count(),
                'passengers_preview' => $r->passengers->take(6)->map(fn ($p) => trim(($p->first_name ?? '').' '.($p->last_name ?? '')))->filter()->values()->all(),
            ];
        })->values()->all();

        return response()->json([
            'hub_stats' => $hubStats,
            'count' => count($items),
            'limit' => 500,
            'filters' => [
                'voyage_id' => $request->query('voyage_id', $request->query('tour_id')),
                'travel_date_id' => $request->query('travel_date_id'),
                'status' => $request->query('status'),
                'search' => $request->query('search'),
            ],
            'reservations' => $items,
        ]);
    }

    /**
     * JSON : stats + fragment HTML du tableau (mêmes filtres que le hub) pour rafraîchir sans recharger la page.
     */
    public function hubRefresh(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('reservations.view'), 403);

        $data = $this->hubListData($request);
        $highlightReservationId = (int) $request->query('highlight', 0);

        return response()->json([
            'hub_stats' => $data['hubStats'],
            'tbody_html' => view('admin.reservations.partials.hub-table-rows', [
                'reservations' => $data['reservations'],
                'highlightReservationId' => $highlightReservationId,
                'hubTableMode' => $data['hubTableMode'],
                'hubVoyageFiltered' => $data['hubVoyageFiltered'],
            ])->render(),
            'pagination_html' => $data['reservations']->links()->render(),
        ]);
    }

    /**
     * Anciennes URLs (/toutes, /en-attente, /paiements…) → hub avec query équivalente.
     */
    public function page(Request $request): RedirectResponse
    {
        $submenu = $request->route()->parameter('submenu');
        $query = $request->query();

        $status = match ($submenu) {
            'en-attente' => Reservation::STATUS_EN_COURS,
            'confirmees' => Reservation::STATUS_VALIDEE,
            'annulees' => Reservation::STATUS_ANNULEE,
            default => false,
        };
        if (is_string($status)) {
            $query['status'] = $status;
        } else {
            unset($query['status']);
        }

        return redirect()->route('admin.reservations.index', $query);
    }

    /**
     * JSON pour modals (détails + participants).
     * Accès complet (édition) : périmètre agence + portail.
     * Accès opérationnel partagé : lecture seule sans champs sensibles (autres agences, même voyage).
     */
    public function panel(Request $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();
        $fullAccess = $this->branchScope->userCanAccessReservation($user, $reservation);
        $operationalRead = ! $fullAccess && $this->branchScope->userCanViewReservationSharedOperational($user, $reservation);
        abort_unless($fullAccess || $operationalRead, 403, 'Accès non autorisé à cette réservation.');

        $reservation->load([
            'passengers',
            'client',
            'offer',
            'travelDate',
            'departure',
            'reservationRooms.departureHotelRoom',
            'branch',
            'partner',
            'creator',
            'createdBy',
        ]);

        $clientLabel = $reservation->client
            ? $reservation->client->full_name
            : trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? ''));

        $passengersFull = $reservation->passengers->map(fn ($p) => [
            'first_name' => $p->first_name,
            'last_name' => $p->last_name,
            'type' => $p->type,
            'birth_date' => $p->birth_date?->format('Y-m-d'),
            'document_type' => $p->document_type,
            'document_number' => $p->document_number,
        ])->values()->all();

        $passengersOperational = $reservation->passengers->map(fn ($p) => [
            'first_name' => $p->first_name,
            'last_name' => $p->last_name,
            'type' => $p->type,
        ])->values()->all();

        $payload = [
            'view_mode' => $fullAccess ? 'full' : 'operational_read_only',
            'id' => $reservation->id,
            'status' => $reservation->status,
            'created_at' => $reservation->created_at?->format('d/m/Y H:i'),
            'client_label' => $clientLabel !== '' ? $clientLabel : null,
            'client_code' => $reservation->client?->client_code,
            'tour_name' => $reservation->offer?->name,
            'tour_id' => $reservation->tour_id,
            'travel_date_id' => $reservation->travel_date_id,
            'travel_date_label' => $reservation->travelDate?->date
                ? $reservation->travelDate->date->format('d/m/Y')
                : null,
            'departure_id' => $reservation->departure_id,
            'departure_start' => $reservation->departure?->start_date?->format('Y-m-d'),
            'departure_end' => $reservation->departure?->end_date?->format('Y-m-d'),
            'departure_label' => $reservation->departure
                ? ($reservation->departure->start_date?->format('d/m/Y')
                    .($reservation->departure->end_date ? ' → '.$reservation->departure->end_date->format('d/m/Y') : ''))
                : null,
            'hotel_room_lines' => $reservation->reservationRooms->map(function ($rr) {
                $dhr = $rr->departureHotelRoom;

                return [
                    'room_type' => $dhr?->room_type,
                    'room_count' => $rr->room_count,
                    'departure_hotel_room_id' => $rr->departure_hotel_room_id,
                ];
            })->values()->all(),
            'prestation_type' => $reservation->prestation_type,
            'base_price' => $reservation->base_price,
            'paid_amount' => $reservation->paid_amount,
            'payment_type' => $reservation->payment_type,
            'branch' => $reservation->branch?->name,
            'agency' => $reservation->agency_label,
            'creator_name' => ($reservation->creator ?? $reservation->createdBy)?->name,
            'creator_email' => ($reservation->creator ?? $reservation->createdBy)?->email,
            'passengers' => $passengersFull,
        ];

        if (! $fullAccess) {
            $payload['client_code'] = null;
            $payload['hotel_room_lines'] = [];
            $payload['prestation_type'] = $reservation->prestation_type;
            $payload['base_price'] = null;
            $payload['paid_amount'] = null;
            $payload['payment_type'] = null;
            $payload['creator_name'] = null;
            $payload['creator_email'] = null;
            $payload['created_at'] = null;
            $payload['passengers'] = $passengersOperational;
        }

        return response()->json($payload);
    }

    /**
     * Formulaire de création de réservation.
     * Préremplissage depuis le calendrier / workspace : voyage_id ou tour_id, travel_date_id (optionnel).
     * Le voyage affiché vient du Voyage Laravel. Les libellés viennent de WordPress quand disponible.
     */
    public function create(Request $request): View
    {
        $requestedTourId = (int) $request->query('voyage_id', $request->query('tour_id', 0));
        $travelDateId = (int) $request->query('travel_date_id', 0);
        $requestedDepartureId = (int) $request->query('departure_id', 0);

        $clientsQuery = Client::query()->orderByDesc('id')->limit(200);
        $this->branchScope->scopeClients($clientsQuery, $request->user());
        $clients = $clientsQuery->get(['id', 'client_code', 'full_name', 'email', 'phone']);
        $voyages = Voyage::query()
            ->with(['extras' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'name', 'slug', 'wp_post_id']);
        if ($requestedTourId > 0 && $voyages->where('id', $requestedTourId)->isEmpty()) {
            $requestedVoyage = Voyage::query()
                ->with(['extras' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
                ->find($requestedTourId);
            if ($requestedVoyage) {
                $voyages = $voyages->prepend($requestedVoyage)->unique('id')->values();
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

        $selectedTravelDate = null;
        $travelDateIncoherent = false;
        if ($travelDateId > 0) {
            $selectedTravelDate = TravelDate::query()->where('is_active', true)->find($travelDateId);
            if ($selectedTravelDate && $requestedTourId > 0) {
                $voyageForTour = Voyage::find($requestedTourId);
                if (! $voyageForTour || (int) $selectedTravelDate->travel_id !== (int) $voyageForTour->wp_post_id) {
                    $selectedTravelDate = null;
                    $travelDateIncoherent = true;
                }
            }
        }

        $selectedDeparture = null;
        if ($requestedDepartureId > 0) {
            $selectedDeparture = Departure::query()->find($requestedDepartureId);
            if ($selectedDeparture && $requestedTourId > 0 && (int) $selectedDeparture->voyage_id !== $requestedTourId) {
                $selectedDeparture = null;
            }
        }
        if (! $selectedDeparture && $requestedTourId > 0 && $travelDateId > 0) {
            $selectedDeparture = Departure::query()
                ->where('voyage_id', $requestedTourId)
                ->where('wp_travel_date_id', $travelDateId)
                ->first();
        }

        if (config('app.debug')) {
            Log::debug('reservations.create.prefill', [
                'query_voyage_id' => $requestedTourId,
                'query_departure_id' => $requestedDepartureId,
                'query_travel_date_id' => $travelDateId,
                'selected_departure' => $selectedDeparture ? [
                    'id' => (int) $selectedDeparture->id,
                    'voyage_id' => (int) $selectedDeparture->voyage_id,
                    'wp_travel_date_id' => (int) ($selectedDeparture->wp_travel_date_id ?? 0),
                    'start_date' => optional($selectedDeparture->start_date)->format('Y-m-d'),
                    'status' => $selectedDeparture->status,
                    'rooms_count' => $selectedDeparture->rooms()->count(),
                    'allocations_count' => $selectedDeparture->roomAllocations()->count(),
                ] : null,
                'selected_travel_date' => $selectedTravelDate ? [
                    'id' => (int) $selectedTravelDate->id,
                    'travel_id' => (int) $selectedTravelDate->travel_id,
                    'date' => optional($selectedTravelDate->date)->format('Y-m-d'),
                    'is_active' => (bool) $selectedTravelDate->is_active,
                ] : null,
                'selected_voyage_extras' => $requestedTourId > 0
                    ? optional($voyages->firstWhere('id', $requestedTourId))->extras?->map(fn ($e) => [
                        'id' => (int) $e->id,
                        'name' => $e->name,
                        'price_adult' => (float) $e->price_adult,
                        'price_child' => (float) $e->price_child,
                        'extra_type' => $e->extra_type,
                    ])->values()->all()
                    : [],
            ]);
        }

        $extrasByVoyage = $voyages
            ->mapWithKeys(fn (Voyage $voyage) => [
                (string) $voyage->id => $voyage->extras
                    ->where('is_active', true)
                    ->values()
                    ->map(fn ($extra) => [
                        'id' => (int) $extra->id,
                        'name' => (string) $extra->name,
                        'description' => (string) ($extra->description ?? ''),
                        'price_adult' => (float) ($extra->price_adult ?? 0),
                        'price_child' => (float) ($extra->price_child ?? 0),
                        'extra_type' => (string) ($extra->extra_type ?? ''),
                        'icon' => (string) ($extra->icon ?? 'fa-plus-circle'),
                    ])->all(),
            ])
            ->all();

        $preselectedTourId = null;
        if ($requestedTourId > 0 && $voyages->contains('id', $requestedTourId)) {
            $preselectedTourId = $requestedTourId;
        }

        return view('admin.reservations.create', [
            'voyages' => $voyages,
            'wpTitles' => $wpTitles,
            'clients' => $clients,
            'selectedTravelDate' => $selectedTravelDate,
            'selectedDepartureId' => $selectedDeparture?->id,
            'travelDateId' => $travelDateId > 0 ? $travelDateId : null,
            'preselectedTourId' => $preselectedTourId,
            'travelDateIncoherent' => $travelDateIncoherent,
            'extrasByVoyage' => $extrasByVoyage,
        ]);
    }

    /**
     * API JSON : hôtels et chambres pour un voyage (tour_id = Voyage.id).
     */
    public function hotelsRooms(Request $request): JsonResponse
    {
        $tourId = (int) $request->query('tour_id', 0);
        if ($tourId <= 0) {
            return response()->json(['hotels' => [], 'currency' => 'DH']);
        }
        $voyage = Voyage::find($tourId);
        if (! $voyage || ! $voyage->wp_post_id) {
            return response()->json(['hotels' => [], 'currency' => 'DH']);
        }
        $wpTourId = (int) $voyage->wp_post_id;
        $hotels = TourHotel::getAllForTour($wpTourId)->load('rooms');
        $payload = [
            'hotels' => $hotels->map(function ($h) {
                return [
                    'id' => $h->id,
                    'hotel_name' => $h->hotel_name,
                    'check_in_day' => $h->check_in_day,
                    'check_out_day' => $h->check_out_day,
                    'rooms' => $h->rooms->where('is_active', true)->values()->map(function ($r) {
                        return [
                            'id' => $r->id,
                            'room_type' => $r->room_type,
                            'room_label' => $r->room_label,
                            'capacity_total' => (int) $r->capacity_total,
                            'capacity_adults' => (int) $r->capacity_adults,
                            'capacity_children' => (int) $r->capacity_children,
                            'supplement' => (float) $r->supplement,
                            'is_default' => (bool) $r->is_default,
                        ];
                    })->all(),
                ];
            })->all(),
            'currency' => $voyage->currency ?? 'DH',
        ];

        return response()->json($payload);
    }

    /**
     * Liste des départs Laravel pour un voyage (sélection réservation).
     */
    public function voyageDepartures(Request $request): JsonResponse
    {
        $tourId = (int) $request->query('tour_id', 0);
        if ($tourId <= 0) {
            return response()->json(['departures' => []]);
        }
        $voyage = Voyage::find($tourId);
        if (! $voyage) {
            return response()->json(['departures' => []]);
        }
        $deps = Departure::query()
            ->where('voyage_id', $voyage->id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        return response()->json([
            'departures' => $deps->map(fn (Departure $d) => [
                'id' => $d->id,
                'label' => ($d->start_date ? $d->start_date->format('d/m/Y') : '—')
                    .($d->end_date ? ' → '.$d->end_date->format('d/m/Y') : ''),
                'status' => $d->status,
                'available_capacity' => (int) ($d->available_capacity ?? 0),
                'wp_travel_date_id' => $d->wp_travel_date_id,
            ])->values()->all(),
        ]);
    }

    /**
     * Hôtels + chambres (stock départ) pour un départ donné.
     */
    public function departureHotelsRooms(Request $request): JsonResponse
    {
        $departureId = (int) $request->query('departure_id', 0);
        if ($departureId <= 0) {
            return response()->json(['hotels' => [], 'currency' => 'DH', 'departure_id' => null]);
        }
        $departure = Departure::query()
            ->with([
                'departureHotels' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
                'departureHotels.rooms' => fn ($q) => $q->orderBy('id'),
                'roomAllocations',
            ])
            ->find($departureId);
        if (! $departure) {
            return response()->json(['hotels' => [], 'currency' => 'DH', 'departure_id' => null]);
        }
        $voyage = Voyage::find($departure->voyage_id);
        $currency = $voyage?->currency_symbol ?? ($voyage?->currency ?? 'DH');

        $hotels = $departure->departureHotels->where('is_active', true)->values()->map(function ($dh) {
            return [
                'departure_hotel_id' => $dh->id,
                'hotel_name' => $dh->hotel_name ?: 'Hôtel',
                'rooms' => $dh->rooms->map(fn ($r) => [
                    'departure_hotel_room_id' => $r->id,
                    'room_type' => $r->room_type,
                    'capacity_total' => (int) $r->capacity_total,
                    'available_rooms' => (int) $r->available_rooms,
                    'available_places' => (int) $r->available_places,
                    'supplement' => (float) $r->supplement,
                    'status' => $r->status,
                ])->values()->all(),
            ];
        })->all();

        if (empty($hotels) && $departure->roomAllocations->isNotEmpty()) {
            $hotelNames = Hotel::query()
                ->whereIn('id', $departure->roomAllocations->pluck('hotel_id')->filter(fn ($id) => (int) $id > 0)->unique()->values())
                ->get(['id', 'name'])
                ->keyBy('id');

            $hotels = $departure->roomAllocations
                ->groupBy(fn ($allocation) => (int) ($allocation->hotel_id ?? 0))
                ->map(function ($allocations, $hotelId) use ($hotelNames) {
                    $hotelId = (int) $hotelId;
                    $hotelName = $hotelId > 0
                        ? (optional($hotelNames->get($hotelId))->name ?: 'Hôtel')
                        : 'Répartition du départ';

                    return [
                        'departure_hotel_id' => null,
                        'hotel_name' => $hotelName,
                        'rooms' => $allocations->map(function ($allocation) {
                            $qty = max(0, (int) ($allocation->quantity ?? 0));
                            $cap = max(1, (int) ($allocation->capacity_per_room ?? 1));

                            return [
                                'departure_hotel_room_id' => null,
                                'room_type' => $allocation->room_type,
                                'capacity_total' => $cap,
                                'available_rooms' => $qty,
                                'available_places' => $qty * $cap,
                                'supplement' => 0,
                                'status' => DepartureHotelRoom::STATUS_AVAILABLE,
                            ];
                        })->values()->all(),
                        'source' => 'allocations',
                    ];
                })
                ->values()
                ->all();
        }

        if (config('app.debug')) {
            Log::debug('reservations.departure_hotels_rooms', [
                'departure_id' => $departure->id,
                'voyage_id' => (int) $departure->voyage_id,
                'wp_travel_date_id' => (int) ($departure->wp_travel_date_id ?? 0),
                'departure_hotels_count' => $departure->departureHotels->count(),
                'departure_rooms_count' => $departure->rooms()->count(),
                'room_allocations_count' => $departure->roomAllocations->count(),
                'response_hotels_count' => count($hotels),
                'response_hotels' => $hotels,
            ]);
        }

        return response()->json([
            'departure_id' => $departure->id,
            'hotels' => $hotels,
            'currency' => is_string($currency) ? $currency : 'DH',
        ]);
    }

    /**
     * Enregistrement d'une réservation (avec chambres et validation capacité).
     */
    public function store(Request $request)
    {
        $this->mergeDepartureFromLegacyRequest($request);

        $data = $request->validate([
            'tour_id' => 'required|integer',
            'departure_id' => 'required|integer|exists:departures,id',
            'travel_date_id' => 'nullable|integer',
            'client_mode' => 'required|in:existing,new',
            'client_external_id' => 'required_if:client_mode,existing|nullable|integer|exists:clients,id',
            'client_first_name' => 'required_if:client_mode,new|nullable|string|max:100',
            'client_last_name' => 'required_if:client_mode,new|nullable|string|max:100',
            'client_phone' => 'nullable|string|max:50',
            'client_email' => 'nullable|email|max:190',
            'client_document_type' => 'nullable|string|max:50',
            'client_document_number' => 'nullable|string|max:100',
            'payment_type' => 'nullable|in:CASHPLUS,VIREMENT,ESPECE',
            'payment_receipt' => 'nullable|file|max:5120',
            'base_price' => 'nullable|numeric|min:0',
            'extras_json' => 'nullable|string',
            'hotel_rooms' => 'nullable|array',
            'hotel_rooms.*.departure_hotel_room_id' => 'nullable|integer',
            'hotel_rooms.*.tour_hotel_id' => 'nullable|integer',
            'hotel_rooms.*.tour_hotel_room_id' => 'nullable|integer',
            'hotel_rooms.*.room_count' => 'nullable|integer|min:0',
            'visa_ok' => 'nullable|boolean',
            'visa_notes' => 'nullable|string|max:2000',
            'visa_status' => 'nullable|in:not_required,pending,approved,rejected',
            'visa_document' => 'nullable|file|max:5120',
            'passengers' => 'nullable|array',
            'passengers.*.first_name' => 'nullable|string|max:100',
            'passengers.*.last_name' => 'nullable|string|max:100',
            'passengers.*.type' => 'nullable|in:adult,child,infant',
            'passengers.*.birth_date' => 'nullable|date',
            'passengers.*.document_type' => 'nullable|string|max:50',
            'passengers.*.document_number' => 'nullable|string|max:100',
        ]);
        $this->validateDepartureMatchesTour($data);

        if (array_key_exists('base_price', $data)) {
            $data['paid_amount'] = $data['base_price'];
            unset($data['base_price']);
        }

        $totalTravelers = $this->computeTotalTravelers($request->input('passengers', []));
        $this->validateRoomCapacity(
            (int) $data['departure_id'],
            (int) ($data['travel_date_id'] ?? 0),
            (int) $data['tour_id'],
            $totalTravelers
        );

        $data['status'] = Reservation::STATUS_EN_COURS;
        $data['visa_ok'] = $request->boolean('visa_ok');
        $user = $request->user();
        $clientIdForBranch = ($data['client_mode'] ?? '') === 'existing' ? (int) ($data['client_external_id'] ?? 0) : null;
        $ownership = $this->branchScope->defaultReservationOwnership($user, $clientIdForBranch ?: null);
        $data['branch_id'] = $ownership['branch_id'];
        $data['sales_manager_id'] = $ownership['sales_manager_id'];
        $data['agent_id'] = $user->id;
        $data['created_by'] = $user->id;
        $data['created_by_user_id'] = $user->id;

        $voyageRef = Voyage::query()->find((int) $data['tour_id']);
        $data['wp_tour_post_id'] = $voyageRef && $voyageRef->wp_post_id ? (int) $voyageRef->wp_post_id : null;
        $data['catalog_source_code'] = null;
        $data['voyage_flight_id'] = null;

        $reservation = $this->reservationService->create(
            $data,
            $request->file('payment_receipt'),
            $request->file('visa_document')
        );

        $extrasPayload = [];
        if ($request->filled('extras_json')) {
            $decoded = json_decode($request->string('extras_json')->toString(), true);
            $extrasPayload = is_array($decoded) ? $decoded : [];
        }
        foreach ($extrasPayload as $extra) {
            if (! is_array($extra)) {
                continue;
            }
            $name = trim((string) ($extra['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            ReservationExtra::query()->create([
                'reservation_id' => $reservation->id,
                'name' => $name,
                'price' => isset($extra['price']) ? (float) $extra['price'] : 0,
                'passenger_key' => isset($extra['voyage_extra_id']) ? 'voyage_extra:'.(int) $extra['voyage_extra_id'] : null,
            ]);
        }

        $tourId = (int) $reservation->tour_id;
        $tdId = isset($data['travel_date_id']) && $data['travel_date_id'] !== null && $data['travel_date_id'] !== ''
            ? (int) $data['travel_date_id']
            : 0;

        return redirect()
            ->route('admin.reservations.index', array_filter([
                'voyage_id' => $tourId > 0 ? $tourId : null,
                'travel_date_id' => $tdId > 0 ? $tdId : null,
                'status' => Reservation::STATUS_EN_COURS,
                'highlight' => $reservation->id,
                'id' => $reservation->id,
                'created' => '1',
            ], fn ($v) => $v !== null && $v !== ''))
            ->with('reservation_created', AdminReservationFlash::createdPayload($reservation));
    }

    /**
     * Fiche réservation : redirige vers l’édition (même périmètre d’accès que {@see edit}).
     * La route est utilisée après création (workspace), liens « Ouvrir », etc.
     */
    public function show(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($this->branchScope->userCanAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');

        return redirect()->route('admin.reservations.edit', $reservation);
    }

    /**
     * Formulaire d'édition d'une réservation.
     */
    public function edit(Request $request, Reservation $reservation): View
    {
        abort_unless($this->branchScope->userCanAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');
        $reservation->load(['passengers', 'client', 'offer', 'reservationRooms.departureHotelRoom', 'departure', 'branch', 'partner', 'creator', 'createdBy']);
        $voyages = Voyage::orderByDesc('id')->limit(200)->get(['id', 'name', 'slug']);
        $clientsQuery = Client::query()->orderByDesc('id')->limit(200);
        $this->branchScope->scopeClients($clientsQuery, $request->user());
        $clients = $clientsQuery->get(['id', 'client_code', 'full_name', 'email', 'phone']);

        $tourHotelsWithRooms = collect();
        $wpTourId = $reservation->getWpTourId();
        if ($wpTourId) {
            $tourHotelsWithRooms = TourHotel::getAllForTour($wpTourId)->load('rooms');
        }

        return view('admin.reservations.edit', [
            'reservation' => $reservation,
            'voyages' => $voyages,
            'clients' => $clients,
            'tourHotelsWithRooms' => $tourHotelsWithRooms,
            'reservationEmbed' => $request->boolean('embed'),
            'embedReturn' => $request->boolean('embed') ? array_filter([
                'voyage_id' => $request->query('rq_voyage_id'),
                'travel_date_id' => $request->query('rq_travel_date_id'),
                'status' => $request->query('rq_status'),
                'search' => $request->query('rq_search'),
            ], fn ($v) => $v !== null && $v !== '') : [],
        ]);
    }

    /**
     * Mise à jour d'une réservation (avec chambres et validation capacité).
     */
    public function update(Request $request, Reservation $reservation): RedirectResponse|HttpResponse
    {
        abort_unless($this->branchScope->userCanAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');
        $this->mergeDepartureFromLegacyRequest($request, $reservation);

        $data = $request->validate([
            'tour_id' => 'required|integer',
            'departure_id' => 'required|integer|exists:departures,id',
            'travel_date_id' => 'nullable|integer',
            'client_mode' => 'required|in:existing,new',
            'client_external_id' => 'required_if:client_mode,existing|nullable|integer|exists:clients,id',
            'client_first_name' => 'required_if:client_mode,new|nullable|string|max:100',
            'client_last_name' => 'required_if:client_mode,new|nullable|string|max:100',
            'client_phone' => 'nullable|string|max:50',
            'client_email' => 'nullable|email|max:190',
            'client_document_type' => 'nullable|string|max:50',
            'client_document_number' => 'nullable|string|max:100',
            'payment_type' => 'nullable|in:CASHPLUS,VIREMENT,ESPECE',
            'payment_receipt' => 'nullable|file|max:5120',
            'base_price' => 'nullable|numeric|min:0',
            'hotel_rooms' => 'nullable|array',
            'hotel_rooms.*.departure_hotel_room_id' => 'nullable|integer',
            'hotel_rooms.*.tour_hotel_id' => 'nullable|integer',
            'hotel_rooms.*.tour_hotel_room_id' => 'nullable|integer',
            'hotel_rooms.*.room_count' => 'nullable|integer|min:0',
            'visa_ok' => 'nullable|boolean',
            'visa_notes' => 'nullable|string|max:2000',
            'visa_status' => 'nullable|in:not_required,pending,approved,rejected',
            'visa_document' => 'nullable|file|max:5120',
            'passengers' => 'nullable|array',
            'passengers.*.id' => 'nullable|integer',
            'passengers.*.first_name' => 'nullable|string|max:100',
            'passengers.*.last_name' => 'nullable|string|max:100',
            'passengers.*.type' => 'nullable|in:adult,child,infant',
            'passengers.*.birth_date' => 'nullable|date',
            'passengers.*.document_type' => 'nullable|string|max:50',
            'passengers.*.document_number' => 'nullable|string|max:100',
        ]);
        $this->validateDepartureMatchesTour($data);

        if (array_key_exists('base_price', $data)) {
            $data['paid_amount'] = $data['base_price'];
            unset($data['base_price']);
        }

        $totalTravelers = $this->computeTotalTravelers($request->input('passengers', []));
        $this->validateRoomCapacity(
            (int) $data['departure_id'],
            (int) ($data['travel_date_id'] ?? 0),
            (int) $data['tour_id'],
            $totalTravelers
        );

        $data['visa_ok'] = $request->boolean('visa_ok');
        $data['updated_by'] = $request->user()->id;

        $this->reservationService->update(
            $reservation,
            $data,
            $request->file('payment_receipt'),
            $request->file('visa_document')
        );

        if ($request->boolean('_embed')) {
            $back = route('admin.reservations.index', array_filter([
                'voyage_id' => $request->input('_return_voyage_id'),
                'travel_date_id' => $request->input('_return_travel_date_id'),
                'status' => $request->input('_return_status'),
                'search' => $request->input('_return_search'),
            ], fn ($v) => $v !== null && $v !== ''));

            return response()
                ->view('admin.reservations.embed-parent-refresh', ['url' => $back, 'message' => 'Réservation mise à jour.']);
        }

        return redirect()
            ->route('admin.reservations.index')
            ->with('success', 'Réservation mise à jour.');
    }

    /**
     * Nombre total de voyageurs : 1 (principal) + accompagnants avec au moins un nom renseigné.
     */
    private function computeTotalTravelers(array $passengers): int
    {
        $count = 1;
        foreach ($passengers as $p) {
            if (! is_array($p)) {
                continue;
            }
            $hasName = (trim((string) ($p['first_name'] ?? '')) !== '') || (trim((string) ($p['last_name'] ?? '')) !== '');
            if ($hasName) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Si le client envoie seulement travel_date_id (flux historique), déduit departure_id
     * à partir du voyage + wp_travel_date_id. Peut aussi reprendre departure_id / travel_date_id
     * depuis la réservation en édition.
     */
    protected function mergeDepartureFromLegacyRequest(Request $request, ?Reservation $reservation = null): void
    {
        if ($reservation) {
            if ((int) $request->input('tour_id', 0) <= 0 && (int) $reservation->tour_id > 0) {
                $request->merge(['tour_id' => (int) $reservation->tour_id]);
            }
            if ((int) $request->input('travel_date_id', 0) <= 0 && (int) ($reservation->travel_date_id ?? 0) > 0) {
                $request->merge(['travel_date_id' => (int) $reservation->travel_date_id]);
            }
        }

        if ((int) $request->input('departure_id', 0) > 0) {
            return;
        }

        $voyageId = (int) $request->input('tour_id', 0);
        $tdId = (int) $request->input('travel_date_id', 0);
        if ($voyageId > 0 && $tdId > 0) {
            $dep = Departure::query()
                ->where('voyage_id', $voyageId)
                ->where('wp_travel_date_id', $tdId)
                ->first();
            if ($dep) {
                $request->merge(['departure_id' => $dep->id]);

                return;
            }
        }

        if ($reservation && (int) ($reservation->departure_id ?? 0) > 0) {
            $request->merge(['departure_id' => (int) $reservation->departure_id]);
        }
    }

    private function validateDepartureMatchesTour(array $data): void
    {
        $depId = (int) ($data['departure_id'] ?? 0);
        $tourId = (int) ($data['tour_id'] ?? 0);
        if ($depId <= 0 || $tourId <= 0) {
            return;
        }
        $dep = Departure::query()->find($depId);
        if (! $dep || (int) $dep->voyage_id !== $tourId) {
            throw ValidationException::withMessages([
                'departure_id' => ['Le départ sélectionné ne correspond pas à ce voyage.'],
            ]);
        }
    }

    /**
     * Vérifie que la capacité disponible sur la date de départ couvre le nombre de voyageurs.
     * La capacité vient des chambres configurées dans l’hôtel du voyage + l’occupation (stock réel).
     */
    private function validateRoomCapacity(int $departureId, int $travelDateId, int $tourId, int $totalTravelers): void
    {
        if ($totalTravelers <= 0 || $tourId <= 0) {
            return;
        }

        if ($departureId > 0) {
            $dep = Departure::query()->find($departureId);
            if ($dep && (int) $dep->available_capacity < $totalTravelers) {
                throw ValidationException::withMessages([
                    'hotel_rooms' => [
                        "Capacité insuffisante sur ce départ ({$dep->available_capacity} place(s) disponible(s)) pour {$totalTravelers} voyageur(s).",
                    ],
                ]);
            }

            return;
        }

        if ($travelDateId <= 0) {
            return;
        }

        $voyage = \App\Models\Voyage::query()->find($tourId);
        if (! $voyage || ! $voyage->wp_post_id) {
            return;
        }

        $wpTourId = (int) $voyage->wp_post_id;
        $tourHotels = TourHotel::getAllForTour($wpTourId)->load('rooms');

        $totalCapacitySeats = 0;
        foreach ($tourHotels as $hotel) {
            foreach ($hotel->rooms->where('is_active', true) as $room) {
                $cap = (int) ($room->capacity_total ?? 0);
                $count = (int) ($room->room_count ?? 0);
                if ($cap > 0 && $count > 0) {
                    $totalCapacitySeats += $count * $cap;
                }
            }
        }

        if ($totalCapacitySeats <= 0) {
            // Aucune capacité configurée : la réservation sera bloquée plus tard côté service si nécessaire.
            return;
        }

        $occupiedSeats = 0;
        try {
            $occupiedSeats = (int) \DB::table('tour_room_type_occupancies')
                ->where('travel_date_id', $travelDateId)
                ->sum('seats_occupied_total');
        } catch (\Throwable $e) {
            // Table absente ou erreur DB : pas de contrôle de capacité fine ici ; le service crée la réservation en chemin standard.
            return;
        }

        $availableSeats = max(0, $totalCapacitySeats - $occupiedSeats);
        if ($availableSeats > 0 && $availableSeats < $totalTravelers) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'hotel_rooms' => [
                    "Capacité insuffisante sur cette date de départ ({$availableSeats} place(s) disponible(s)) pour {$totalTravelers} voyageur(s).",
                ],
            ]);
        }
    }

    /**
     * Suppression d'une réservation.
     */
    public function destroy(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($this->branchScope->userCanAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');
        $this->reservationService->delete($reservation);

        return redirect()
            ->route('admin.reservations.index')
            ->with('success', 'Réservation supprimée.');
    }

    /**
     * Valider une réservation (passer en VALIDEE).
     */
    public function validateReservation(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($this->branchScope->userCanAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');
        $this->reservationService->validateReservation($reservation);

        return redirect()
            ->back()
            ->with('success', 'Réservation validée.');
    }

    /**
     * Servir le fichier reçu (image/PDF) depuis le stockage — évite le 404 si le symlink storage n'existe pas.
     */
    public function showReceipt(Request $request): StreamedResponse|\Illuminate\Http\Response
    {
        $path = $request->query('path');
        if (! $path || ! is_string($path)) {
            abort(404);
        }
        $path = str_replace('\\', '/', trim($path));
        $valid = ! str_contains($path, '..') && (str_starts_with($path, 'reservation-receipts/') || str_starts_with($path, 'reservation-visa/'));
        if (! $valid) {
            abort(404);
        }
        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }
        $mime = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';

        return response()->stream(function () use ($path) {
            $stream = Storage::disk('public')->readStream($path);
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    /**
     * Requête réservations hub avec les filtres query (liste, stats, debug).
     */
    protected function hubFilteredReservationBuilder(Request $request): Builder
    {
        $user = $request->user();

        $tourFilter = (int) $request->query('voyage_id', 0);
        if ($tourFilter <= 0) {
            $tourFilter = (int) $request->query('tour_id', 0);
        }

        $travelDateFilter = (int) $request->query('travel_date_id', 0);
        $base = $this->reservationListQuery->baseQuery($user, [
            'tour_id' => $tourFilter > 0 ? $tourFilter : 0,
            'travel_date_id' => $travelDateFilter > 0 ? $travelDateFilter : 0,
        ]);

        $this->reservationListQuery->applyTourFilter($base, $tourFilter);

        $this->reservationListQuery->applyTravelDateFilter($base, $travelDateFilter > 0 ? $travelDateFilter : null);

        $channel = (string) $request->query('channel', '');
        $this->reservationListQuery->applyChannelFilter($base, $channel !== '' ? $channel : null);

        $search = (string) $request->query('search', '');
        $this->reservationListQuery->applyClientSearch($base, $search);

        $statusParam = (string) $request->query('status', '');
        if (! in_array($statusParam, [
            Reservation::STATUS_EN_COURS,
            Reservation::STATUS_VALIDEE,
            Reservation::STATUS_ANNULEE,
        ], true)) {
            $statusParam = '';
        }
        $this->reservationListQuery->applyStatusFilter($base, $statusParam !== '' ? $statusParam : null);

        return $base;
    }

    /**
     * Données hub (stats + page courante) : même logique pour la vue HTML et {@see hubRefresh()}.
     *
     * @return array{hubStats: array, reservations: \Illuminate\Contracts\Pagination\LengthAwarePaginator, filterTourId: int|null, filterTravelDateId: int|null, filterSearch: string|null, filterStatus: string|null, hubTableMode: string, hubVoyageFiltered: bool}
     */
    protected function hubListData(Request $request): array
    {
        $base = $this->hubFilteredReservationBuilder($request);

        $tourFilter = (int) $request->query('voyage_id', 0);
        if ($tourFilter <= 0) {
            $tourFilter = (int) $request->query('tour_id', 0);
        }
        $travelDateFilter = (int) $request->query('travel_date_id', 0);
        $hubVoyageFiltered = $tourFilter > 0 || $travelDateFilter > 0;
        $search = (string) $request->query('search', '');
        $statusParam = (string) $request->query('status', '');
        if (! in_array($statusParam, [
            Reservation::STATUS_EN_COURS,
            Reservation::STATUS_VALIDEE,
            Reservation::STATUS_ANNULEE,
        ], true)) {
            $statusParam = '';
        }

        $hubStats = $this->reservationListQuery->aggregateStatusCounts(clone $base);

        $reservations = (clone $base)
            ->with(['passengers', 'client', 'offer', 'branch', 'partner', 'travelDate', 'creator', 'createdBy', 'agent', 'salesManager'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $this->normalizeVoyageLabels(
            $reservations->getCollection()
                ->pluck('offer')
                ->filter()
                ->values()
        );

        return [
            'hubStats' => $hubStats,
            'reservations' => $reservations,
            'filterTourId' => $tourFilter > 0 ? $tourFilter : null,
            'filterTravelDateId' => $travelDateFilter > 0 ? $travelDateFilter : null,
            'filterSearch' => $search !== '' ? $search : null,
            'filterStatus' => $statusParam !== '' ? $statusParam : null,
            'hubTableMode' => $this->reservationHubTableProfile->mode($request->user()),
            'hubVoyageFiltered' => $hubVoyageFiltered,
        ];
    }

    /**
     * Liste + stats : une seule base de requête filtrée (identique au tableau paginé).
     */
    protected function renderList(Request $request): View
    {
        $data = $this->hubListData($request);
        $highlightReservationId = (int) $request->query('highlight', 0);
        $selectedVoyage = $this->resolveSelectedVoyageForHeader($data['filterTourId'] ?? null);

        $reservationCreated = $request->session()->pull('reservation_created');
        if (! is_array($reservationCreated)) {
            $reservationCreated = null;
        }
        if ($reservationCreated === null
            && $request->query('created') === '1'
            && (int) $request->query('id', 0) > 0) {
            $rid = (int) $request->query('id');
            $res = Reservation::query()->find($rid);
            if ($res
                && $res->created_at
                && $res->created_at->gt(now()->subMinutes(5))
                && ($this->branchScope->userCanAccessReservation($request->user(), $res)
                    || $this->branchScope->userCanViewReservationSharedOperational($request->user(), $res))) {
                $reservationCreated = AdminReservationFlash::createdPayload($res);
            }
        }

        $voyageOptions = Voyage::query()
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'wp_post_id']);

        $voyageOptions = $this->normalizeVoyageLabels($voyageOptions)
            ->sortBy(fn (Voyage $voyage) => Str::lower((string) ($voyage->resolved_name ?? $voyage->name)))
            ->values();

        return view('admin.reservations.index', array_merge($data, [
            'voyageOptions' => $voyageOptions,
            'voyage' => $selectedVoyage,
            'highlightReservationId' => $highlightReservationId,
            'reservationCreated' => $reservationCreated,
        ]));
    }

    protected function resolveSelectedVoyageForHeader(?int $voyageId): ?Voyage
    {
        if ($voyageId === null || $voyageId <= 0) {
            return null;
        }

        $voyage = Voyage::query()->find($voyageId, ['id', 'name', 'slug', 'wp_post_id']);
        if (! $voyage) {
            return null;
        }

        return $this->normalizeVoyageLabels(collect([$voyage]))->first();
    }

    /**
     * @param  Collection<int, Voyage>  $voyages
     * @return Collection<int, Voyage>
     */
    protected function normalizeVoyageLabels(Collection $voyages): Collection
    {
        $voyages = $voyages
            ->filter(fn ($voyage) => $voyage instanceof Voyage)
            ->values();

        if ($voyages->isEmpty()) {
            return $voyages;
        }

        $wpTitles = WpPost::query()
            ->whereIn('ID', $voyages->pluck('wp_post_id')->filter(fn ($id) => (int) $id > 0)->map(fn ($id) => (int) $id)->unique()->values()->all())
            ->pluck('post_title', 'ID');

        $updates = [];
        foreach ($voyages as $voyage) {
            $currentName = trim((string) $voyage->name);
            $wpTitle = trim((string) $wpTitles->get((int) ($voyage->wp_post_id ?? 0), ''));

            $resolvedName = $this->isRealVoyageTitle($currentName)
                ? $currentName
                : ($wpTitle !== '' ? $wpTitle : '');

            if ($resolvedName !== '' && $resolvedName !== $voyage->name) {
                $voyage->name = $resolvedName;
                $updates[(int) $voyage->id] = $resolvedName;
            }

            $voyage->resolved_name = $resolvedName !== '' ? $resolvedName : $currentName;
        }

        foreach ($updates as $voyageId => $resolvedName) {
            Voyage::query()->whereKey($voyageId)->update(['name' => $resolvedName]);
        }

        return $voyages;
    }

    protected function isRealVoyageTitle(?string $value): bool
    {
        $title = trim((string) $value);
        if ($title === '') {
            return false;
        }

        $normalized = Str::of($title)->lower()->squish()->value();

        return ! in_array($normalized, ['brouillon auto', 'tour', 'untitled tour'], true);
    }

    /**
     * Calendrier des départs (admin).
     * Le filtre "Voyage" utilise la même source que /admin/circuits/voyages : tous les tours WordPress (WpPost::tours()).
     */
    public function calendar(Request $request): View
    {
        $voyagesForFilter = collect();
        try {
            $tours = WpPost::tours()
                ->orderBy('post_title')
                ->get(['ID', 'post_title']);
            $voyagesForFilter = $tours->map(fn ($t) => (object) ['id' => $t->ID, 'name' => $t->post_title ?? '']);
        } catch (\Throwable $e) {
            \Log::warning('ReservationsController@calendar: WP tours list failed, fallback to Voyage', ['error' => $e->getMessage()]);
            // Fallback: on garde un identifiant cohérent avec TravelDate.travel_id => Voyage.wp_post_id (pas Voyage.id)
            $voyagesForFilter = Voyage::query()
                ->whereNotNull('wp_post_id')
                ->orderBy('name')
                ->get(['wp_post_id', 'name'])
                ->map(fn ($v) => (object) ['id' => (int) $v->wp_post_id, 'name' => $v->name]);
        }

        $destinations = Voyage::query()
            ->whereNotNull('destination')
            ->where('destination', '!=', '')
            ->distinct()
            ->orderBy('destination')
            ->pluck('destination');

        $statuses = Voyage::query()
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $selectedVoyageId = (int) $request->query('voyage', 0);
        $selectedDestination = $request->query('destination', '');
        $selectedStatus = $request->query('status', '');
        $budgetMin = $request->query('budget_min', '');
        $budgetMax = $request->query('budget_max', '');
        $month = $request->query('month', '');
        $search = $request->query('search', '');
        $dateFrom = $request->query('date_from', '');
        $dateTo = $request->query('date_to', '');

        return view('admin.reservations.calendrier.index', [
            'voyages' => $voyagesForFilter,
            'destinations' => $destinations,
            'statuses' => $statuses,
            'selectedVoyageId' => $selectedVoyageId,
            'selectedDestination' => $selectedDestination,
            'selectedStatus' => $selectedStatus,
            'budgetMin' => $budgetMin,
            'budgetMax' => $budgetMax,
            'month' => $month,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Événements JSON pour le calendrier : dates de départ (offres) + réservations liées.
     * Le paramètre "voyage" est l'ID tour WordPress (TravelDate.travel_id).
     */
    public function calendarEvents(Request $request): JsonResponse
    {
        $voyageFilter = (int) $request->query('voyage', 0);
        $destination = $request->query('destination', '');
        $status = $request->query('status', '');
        $budgetMin = $request->has('budget_min') && $request->query('budget_min') !== '' ? (float) $request->query('budget_min') : null;
        $budgetMax = $request->has('budget_max') && $request->query('budget_max') !== '' ? (float) $request->query('budget_max') : null;
        $month = $request->query('month', '');
        $search = trim((string) $request->query('search', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        if ($voyageFilter > 0) {
            $wpTourIds = collect([$voyageFilter]);
        } else {
            $wpTourIds = Voyage::query()
                ->whereNotNull('wp_post_id')
                ->when($destination !== '', fn ($q) => $q->where('destination', $destination))
                ->when($status !== '', fn ($q) => $q->where('status', $status))
                ->when($budgetMin !== null, fn ($q) => $q->where('price_from', '>=', $budgetMin))
                ->when($budgetMax !== null, fn ($q) => $q->where('price_from', '<=', $budgetMax))
                ->when($search !== '', fn ($q) => $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'like', '%'.$search.'%')
                        ->orWhere('destination', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                }))
                ->pluck('wp_post_id');
        }

        if ($wpTourIds->isEmpty()) {
            return response()->json([]);
        }

        $wpTourIds = $wpTourIds->filter()->map(fn ($id) => (int) $id)->unique()->values();

        $applyTravelDateWindow = function (Builder $q) use ($month, $dateFrom, $dateTo): void {
            if ($dateFrom !== '' && $dateTo !== '') {
                try {
                    $df = Carbon::parse($dateFrom)->toDateString();
                    $dt = Carbon::parse($dateTo)->toDateString();
                    $q->whereBetween('date', [$df, $dt]);
                } catch (\Throwable $e) {
                    // ignore invalid
                }
            } elseif ($month !== '' && preg_match('/^(\d{4})-(\d{2})$/', $month, $m)) {
                $q->whereYear('date', (int) $m[1])
                    ->whereMonth('date', (int) $m[2]);
            } else {
                $q->whereYear('date', now()->year)
                    ->whereMonth('date', now()->month);
            }
        };

        $travelDatesQuery = TravelDate::query()
            ->where('is_active', true)
            ->whereIn('travel_id', $wpTourIds);

        $applyTravelDateWindow($travelDatesQuery);

        $travelDates = $travelDatesQuery->orderBy('date')->get();

        $travelIds = $travelDates->pluck('travel_id')->unique()->filter()->map(fn ($id) => (int) $id)->values();

        $wpPosts = collect();
        if ($travelIds->isNotEmpty()) {
            try {
                $wpPosts = WpPost::query()
                    ->whereIn('ID', $travelIds)
                    ->get(['ID', 'post_title', 'post_name', 'post_excerpt', 'post_content'])
                    ->keyBy('ID');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $voyages = $travelIds->isNotEmpty()
            ? Voyage::query()->whereIn('wp_post_id', $travelIds)->get()->keyBy('wp_post_id')
            : collect();

        $events = [];
        foreach ($travelDates as $travelDate) {
            $voyage = $voyages->get($travelDate->travel_id);
            $wpPost = $wpPosts->get((int) $travelDate->travel_id);
            $title = $wpPost ? ($wpPost->post_title ?? '') : ($voyage?->name ?? ('Tour #'.$travelDate->travel_id));
            $dateStr = $travelDate->date?->format('Y-m-d');
            $wpId = $travelDate->travel_id;
            $events[] = [
                'id' => 'td-'.$travelDate->id,
                'title' => $title !== '' ? $title : ('Tour #'.$wpId),
                'start' => $dateStr,
                'allDay' => true,
                'extendedProps' => [
                    'kind' => 'departure',
                    'voyage_id' => $voyage?->id,
                    'wp_travel_id' => $wpId,
                    'travel_date_id' => $travelDate->id,
                    'departure_date' => $dateStr,
                    'destination' => $voyage?->destination,
                    'price_from' => $voyage?->price_from,
                    'price_override' => $travelDate->price_override,
                    'currency_symbol' => $voyage?->currency_symbol ?? 'DH',
                    'status' => $voyage?->status,
                    'duration_text' => $voyage?->duration_text,
                    'route_consulter' => route('admin.circuits.voyages.edit', ['id' => $wpId]),
                    'route_reserver' => $voyage ? route('admin.reservations.create', ['tour_id' => $voyage->id, 'travel_date_id' => $travelDate->id]) : route('admin.reservations.create'),
                    'route_voir_fiche' => route('admin.circuits.voyages.show', ['id' => $wpId]),
                ],
            ];
        }

        $reservationQuery = Reservation::query()
            ->with(['offer:id,name,wp_post_id', 'creator:id,name,email', 'branch:id,name', 'partner:id,name'])
            ->whereNotNull('travel_date_id')
            ->whereHas('travelDate', function (Builder $q) use ($wpTourIds, $applyTravelDateWindow) {
                $q->where('is_active', true)->whereIn('travel_id', $wpTourIds);
                $applyTravelDateWindow($q);
            });

        $calendarScopeContext = [];
        if ($voyageFilter > 0) {
            $laravelTourId = (int) (Voyage::query()->where('wp_post_id', $voyageFilter)->orderBy('id')->value('id') ?? 0);
            if ($laravelTourId > 0) {
                $calendarScopeContext['tour_id'] = $laravelTourId;
            }
        }

        $this->scopeReservationAccessForCalendar($reservationQuery, $request->user(), $calendarScopeContext);

        if ($search !== '') {
            $reservationQuery->where(function (Builder $q) use ($search) {
                $q->where('client_first_name', 'like', '%'.$search.'%')
                    ->orWhere('client_last_name', 'like', '%'.$search.'%')
                    ->orWhere('client_email', 'like', '%'.$search.'%')
                    ->orWhere('client_phone', 'like', '%'.$search.'%')
                    ->orWhereHas('offer', fn (Builder $q2) => $q2->where('name', 'like', '%'.$search.'%'));
            });
        }

        foreach ($reservationQuery->orderBy('id')->get() as $reservation) {
            $td = $reservation->travelDate;
            if (! $td || ! $td->date) {
                continue;
            }
            $dateStr = $td->date->format('Y-m-d');
            $client = trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? ''));
            $tourName = $reservation->offer?->name ?? 'Voyage';
            $chip = '#'.$reservation->id.' · '.($client !== '' ? $client : 'Client');
            $events[] = [
                'id' => 'res-'.$reservation->id,
                'title' => $chip,
                'start' => $dateStr,
                'allDay' => true,
                'extendedProps' => [
                    'kind' => 'reservation',
                    'reservation_id' => $reservation->id,
                    'reservation_status' => $reservation->status,
                    'client_label' => $client,
                    'tour_name' => $tourName,
                    'travel_date_id' => $td->id,
                    'wp_travel_id' => $td->travel_id,
                    'voyage_id' => $reservation->tour_id,
                    'departure_date' => $dateStr,
                    'route_reservation' => route('admin.reservations.edit', $reservation),
                ],
            ];
        }

        return response()->json($events);
    }

    /**
     * Détail JSON d'une réservation (modale calendrier).
     */
    public function calendarReservationDetails(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id <= 0) {
            return response()->json(['error' => 'Paramètre id manquant'], 422);
        }

        $user = $request->user();
        $reservation = Reservation::query()
            ->with(['offer:id,name,wp_post_id', 'travelDate', 'client:id,full_name,email,phone', 'branch:id,name', 'partner:id,name', 'creator:id,name,email'])
            ->whereKey($id)
            ->first();

        if (! $reservation) {
            return response()->json(['error' => 'Réservation introuvable ou accès refusé'], 404);
        }

        $fullAccess = $this->branchScope->userCanAccessReservation($user, $reservation);
        $operationalRead = ! $fullAccess && $this->branchScope->userCanViewReservationSharedOperational($user, $reservation);
        if (! $fullAccess && ! $operationalRead) {
            return response()->json(['error' => 'Réservation introuvable ou accès refusé'], 404);
        }

        $td = $reservation->travelDate;
        $departure = $td?->date?->format('Y-m-d');
        $departureFormatted = $td?->date?->translatedFormat('l j F Y');

        $clientName = trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? ''))
            ?: ($reservation->client?->full_name ?? '—');

        $payload = [
            'view_mode' => $fullAccess ? 'full' : 'operational_read_only',
            'kind' => 'reservation',
            'id' => $reservation->id,
            'status' => $reservation->status,
            'client' => $clientName,
            'email' => $reservation->client_email ?: $reservation->client?->email,
            'phone' => $reservation->client_phone ?: $reservation->client?->phone,
            'tour_name' => $reservation->offer?->name ?? '—',
            'branch' => $reservation->branch?->name,
            'agency' => $reservation->agency_label,
            'creator_name' => $reservation->creator?->name,
            'departure_date' => $departure,
            'departure_date_formatted' => $departureFormatted,
            'payment_type' => $reservation->payment_type,
            'total_price' => $reservation->total_price,
            'route_edit' => route('admin.reservations.edit', $reservation),
        ];

        if (! $fullAccess) {
            $payload['email'] = null;
            $payload['phone'] = null;
            $payload['creator_name'] = null;
            $payload['payment_type'] = null;
            $payload['total_price'] = null;
            $payload['route_edit'] = null;
        }

        return response()->json($payload);
    }

    /**
     * Réservations calendrier : périmètre agence par défaut ; vue partagée si un voyage WP est filtré
     * (même logique que le hub filtré par voyage).
     *
     * @param  array{tour_id?: int}  $context
     */
    private function scopeReservationAccessForCalendar(Builder $query, User $user, array $context = []): void
    {
        $ctx = [
            'tour_id' => (int) ($context['tour_id'] ?? 0),
            'travel_date_id' => (int) ($context['travel_date_id'] ?? 0),
            'departure_id' => (int) ($context['departure_id'] ?? 0),
            'shared_operational_aggregate' => ! empty($context['shared_operational_aggregate']),
        ];
        $this->branchScope->scopeReservations($query, $user, $ctx);
        $this->branchScope->constrainReservationQueryForPortalUser($query, $user, $ctx);
    }

    /**
     * Détails d'un événement calendrier (pour le modal).
     * Priorité : travel_date_id (exact) > voyage_id + date > wp_travel_id + date.
     */
    public function calendarEventDetails(Request $request): JsonResponse
    {
        $travelDateId = (int) $request->query('travel_date_id', 0);
        $voyageId = (int) $request->query('voyage_id', 0);
        $wpTravelId = (int) $request->query('wp_travel_id', 0);
        $date = $request->query('date', '');

        $travelDate = null;
        $wpId = null;
        $voyage = null;

        if ($travelDateId > 0) {
            $travelDate = TravelDate::query()->where('is_active', true)->find($travelDateId);
            if ($travelDate) {
                $wpId = (int) $travelDate->travel_id;
                $voyage = Voyage::query()->where('wp_post_id', $wpId)->orderBy('id')->first();
            }
        }

        if (! $travelDate && $date !== '') {
            if ($voyageId > 0) {
                $voyage = Voyage::query()->find($voyageId);
                if ($voyage && $voyage->wp_post_id) {
                    $wpId = (int) $voyage->wp_post_id;
                }
            }
            if ($wpId === null && $wpTravelId > 0) {
                $wpId = $wpTravelId;
                $voyage = Voyage::query()->where('wp_post_id', $wpTravelId)->orderBy('id')->first();
            }
            if ($wpId > 0) {
                $travelDate = TravelDate::query()
                    ->where('travel_id', $wpId)
                    ->where('date', $date)
                    ->where('is_active', true)
                    ->first();
            }
        }

        if (! $travelDate) {
            return response()->json(['error' => $date === '' ? 'Paramètre date manquant' : 'Date de départ introuvable'], $date === '' ? 422 : 404);
        }
        if ($wpId === null) {
            $wpId = (int) $travelDate->travel_id;
            $voyage = $voyage ?? Voyage::query()->where('wp_post_id', $wpId)->orderBy('id')->first();
        }

        $wpPost = null;
        try {
            $wpPost = WpPost::query()->where('ID', $wpId)->first();
        } catch (\Throwable $e) {
            // ignore
        }

        // Source de vérité : données du voyage = post WordPress + meta (pas le modèle Laravel Voyage qui peut être désynchronisé)
        $destination = null;
        $durationText = null;
        $priceFrom = null;
        $displayPrice = null;
        $currencySymbol = 'DH';
        $featuredImageUrl = null;
        $minPeople = null;
        $maxPeople = null;
        $status = null;

        if ($wpPost) {
            $address = $wpPost->getMeta('address');
            $multiLocation = $wpPost->getMeta('multi_location');
            $destination = trim((string) $address) !== '' ? $address : $this->wpTourRepository->getLocationNamesFromMultiLocation($multiLocation);
            if ($destination === '') {
                $destination = null;
            }

            $durationDayRaw = $wpPost->getMeta('duration_day');
            $durationDays = $this->parseDurationDaysForModal($durationDayRaw);
            $durationText = $durationDays >= 1 ? $durationDays.' jour'.($durationDays > 1 ? 's' : '').($durationDays >= 2 ? ' / '.($durationDays - 1).' nuit'.(($durationDays - 1) > 1 ? 's' : '') : '') : null;

            $priceFrom = $this->parsePriceFromMeta($wpPost->getMeta('min_price')) ?? $this->parsePriceFromMeta($wpPost->getMeta('base_price'));
            $priceOverride = $travelDate->price_override !== null ? (float) $travelDate->price_override : null;
            $displayPrice = $priceOverride ?? $priceFrom;

            $currencySymbol = $wpPost->getMeta('currency') ?: 'DH';
            if (is_string($currencySymbol) && strtoupper($currencySymbol) === 'MAD') {
                $currencySymbol = 'DH';
            }

            $heroId = (int) $wpPost->getMeta('_tour_hero_image_id') ?: (int) $wpPost->getMeta('_thumbnail_id');
            if ($heroId > 0) {
                $featuredImageUrl = WpHeroImageService::getAttachmentUrl($heroId);
            }
            $minPeople = $this->parseIntMeta($wpPost->getMeta('min_people'));
            $maxPeople = $this->parseIntMeta($wpPost->getMeta('max_people'));
            $status = $wpPost->post_status ?? null;
        }

        $hotelsWithRooms = TourHotel::getAllForTour($wpId)->load('rooms');
        $hotelsPayload = $hotelsWithRooms->map(function ($h) {
            $rooms = $h->rooms->where('is_active', true)->values()->map(fn ($r) => [
                'id' => $r->id,
                'room_type' => $r->room_type,
                'room_label' => $r->room_label,
                'room_count' => (int) $r->room_count,
                'capacity_adults' => (int) $r->capacity_adults,
                'capacity_children' => (int) $r->capacity_children,
                'capacity_total' => (int) $r->capacity_total,
                'supplement' => (float) $r->supplement,
            ])->all();

            return [
                'id' => $h->id,
                'hotel_name' => $h->hotel_name,
                'check_in_day' => $h->check_in_day,
                'check_out_day' => $h->check_out_day,
                'rooms' => $rooms,
            ];
        })->all();

        $basePayload = [
            'travel_date_id' => $travelDate->id,
            'voyage_id' => $voyage?->id,
            'name' => $wpPost?->post_title ?? ('Tour #'.$wpId),
            'slug' => $wpPost?->post_name ?? '',
            'destination' => $destination,
            'departure_date' => $travelDate->date->format('Y-m-d'),
            'departure_date_formatted' => $travelDate->date->translatedFormat('l j F Y'),
            'duration_text' => $durationText,
            'price_from' => $priceFrom,
            'price_override' => $travelDate->price_override !== null ? (float) $travelDate->price_override : null,
            'currency_symbol' => $currencySymbol,
            'display_price' => $displayPrice,
            'status' => $status,
            'description' => $wpPost?->post_content ?? null,
            'accroche' => $wpPost?->post_excerpt ?? null,
            'featured_image_url' => $featuredImageUrl,
            'min_people' => $minPeople,
            'max_people' => $maxPeople,
            'seats' => $travelDate->seats,
            'hotels_with_rooms' => $hotelsPayload,
            'route_consulter' => route('admin.circuits.voyages.edit', ['id' => $wpId]),
            'route_reserver' => $voyage
                ? route('admin.reservations.create', ['tour_id' => $voyage->id, 'travel_date_id' => $travelDate->id])
                : route('admin.reservations.create'),
            'route_voir_fiche' => route('admin.circuits.voyages.show', ['id' => $wpId]),
        ];

        return response()->json($basePayload);
    }

    /**
     * Parse duration_day meta as number of days. Ignore "X hours" to avoid wrong duration.
     */
    private function parseDurationDaysForModal(mixed $value): int
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
     * Parse price from meta (numeric).
     */
    private function parsePriceFromMeta(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $n = is_numeric($value) ? (float) $value : null;

        return $n !== null && $n >= 0 ? $n : null;
    }

    /**
     * Parse integer from meta.
     */
    private function parseIntMeta(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $n = (int) $value;

        return $n >= 0 ? $n : null;
    }
}
