<?php

namespace App\Http\Controllers\Admin;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Departure;
use App\Models\DepartureHotelRoom;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\ReservationDossier;
use App\Models\ReservationDocument;
use App\Models\ReservationPayment;
use App\Models\TourHotel;
use App\Models\TravelDate;
use App\Models\User;
use App\Models\Voyage;
use App\Models\Wp\TourDayActivity;
use App\Models\Wp\WpPost;
use App\Services\BranchScopeService;
use App\Services\AgentCommissionService;
use App\Services\ReservationHubTableProfile;
use App\Services\AdminWpTourCatalogQuery;
use App\Services\ReservationListQueryService;
use App\Services\ReservationDossierService;
use App\Services\ReservationService;
use App\Services\ReservationVisibilityService;
use App\Services\Reservations\ReservationPricingService;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        protected ReservationVisibilityService $reservationVisibility,
        protected ReservationDossierService $reservationDossier,
        protected ReservationPricingService $reservationPricing,
        protected AgentCommissionService $agentCommissionService,
    ) {}

    /**
     * Hub unique : liste, filtres, stats alignées, actions (modals / tiroir).
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('admin.reservation-dossiers.index', $request->query());
    }

    /**
     * JSON debug : toutes les réservations du périmètre courant (mêmes filtres que le hub), max 500.
     * Uniquement si APP_DEBUG EUR" pour tester lEURTMaffichage / la cohérence des données.
     */
    public function hubDebug(Request $request): JsonResponse
    {
        abort_unless(config('app.debug'), 404);
        abort_unless($request->user()->can('reservations.view'), 403);
        $visibility = $this->reservationVisibility->flagsFor($request->user());

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

        $items = $rows->map(function (Reservation $r) use ($visibility) {
            return [
                'id' => $r->id,
                'tour_id' => $r->tour_id,
                'tour_name' => $r->offer?->name,
                'tour_wp_post_id' => $r->offer?->wp_post_id,
                'wp_tour_post_id' => $r->wp_tour_post_id ?? null,
                'channel' => $r->channel ?? null,
                'catalog_source_code' => $r->catalog_source_code ?? null,
                'voyage_flight_id' => $r->voyage_flight_id ?? null,
                'prestation_type' => $r->prestation_type,
                'travel_date_id' => $r->travel_date_id,
                'travel_date' => $r->travelDate?->date?->format('Y-m-d'),
                'status' => $r->status,
                'created_at' => $visibility['view_assignment_context'] ? $r->created_at?->toIso8601String() : null,
                'creator_name' => $visibility['view_assignment_context'] ? $r->creator?->name : null,
                'agency_name' => $visibility['view_assignment_context'] ? $r->agency_label : null,
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
            'visibility' => $visibility,
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
                'filterChannel' => $data['filterChannel'] ?? null,
                'reservationVisibility' => $data['reservationVisibility'],
            ])->render(),
            'pagination_html' => $data['reservations']->links()->render(),
        ]);
    }

    /**
     * Anciennes URLs (/toutes, /en-attente, /paiementsEUR) + hub avec query équivalente.
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
        $visibility = $this->reservationVisibility->flagsFor($user);
        $fullAccess = $this->reservationVisibility->canAccessReservation($user, $reservation);
        $operationalRead = false;
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
            'view_mode' => $visibility['limited_presentation'] ? 'limited' : 'full',
            'id' => $reservation->id,
            'status' => $reservation->status,
            'created_at' => $reservation->created_at?->format('d/m/Y H:i'),
            'client_label' => $clientLabel !== '' ? $clientLabel : null,
            'client_code' => $visibility['view_sensitive'] ? $reservation->client?->client_code : null,
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
                    .($reservation->departure->end_date ? ' + '.$reservation->departure->end_date->format('d/m/Y') : ''))
                : null,
            'hotel_room_lines' => $reservation->reservationRooms->map(function ($rr) {
                $dhr = $rr->departureHotelRoom;

                return [
                    'room_type' => $dhr?->room_type ?: $rr->room_type_snapshot,
                    'room_count' => $rr->room_count,
                    'passenger_count' => $rr->passenger_count,
                    'room_mode' => $rr->room_mode,
                    'shared_room_status' => $rr->shared_room_status,
                    'source_room_type' => $rr->source_room_type,
                    'source_room_id' => $rr->source_room_id,
                    'departure_hotel_id' => $rr->departure_hotel_id,
                    'departure_hotel_room_id' => $rr->departure_hotel_room_id,
                ];
            })->values()->all(),
            'demi_double_pending_seats' => $reservation->status === Reservation::STATUS_SHARED_ROOM_PENDING
                ? (int) $reservation->reservationRooms
                    ->filter(function ($rr) {
                        $mode = (string) ($rr->room_mode ?? '');
                        $sharedStatus = (string) ($rr->shared_room_status ?? 'pending');
                        $sourceType = (string) ($rr->source_room_type ?? '');
                        if ($mode === 'shared_double' && $sharedStatus !== 'paired') {
                            return true;
                        }

                        return $mode === '' && $sourceType === 'double' && (int) ($rr->passenger_count ?? 0) === 1;
                    })
                    ->sum(fn ($rr) => (int) ($rr->passenger_count ?? 0))
                : 0,
            'prestation_type' => $reservation->prestation_type,
            'base_price' => $visibility['view_financial'] ? $reservation->base_price : null,
            'paid_amount' => $visibility['view_financial'] ? $reservation->paid_amount : null,
            'payment_type' => $visibility['view_financial'] ? $reservation->payment_type : null,
            'branch' => $visibility['view_assignment_context'] ? $reservation->branch?->name : null,
            'agency' => $visibility['view_assignment_context'] ? $reservation->agency_label : null,
            'creator_name' => $visibility['view_assignment_context'] ? ($reservation->creator ?? $reservation->createdBy)?->name : null,
            'creator_email' => ($visibility['view_assignment_context'] && $visibility['view_client_contact']) ? ($reservation->creator ?? $reservation->createdBy)?->email : null,
            'passengers' => $visibility['view_sensitive'] ? $passengersFull : $passengersOperational,
            'visibility' => $visibility,
        ];

        if (! $visibility['view_sensitive']) {
            $payload['hotel_room_lines'] = [];
        }

        if ($visibility['limited_presentation']) {
            $payload['created_at'] = null;
            $payload['branch'] = null;
            $payload['agency'] = null;
            $payload['creator_name'] = null;
            $payload['creator_email'] = null;
        }
            Log::info('URGENT ROOM ENDPOINT RESULT', [
                'mode' => $payload['mode'] ?? null,
                'rooms_count' => count($payload['rooms'] ?? []),
                'rooms' => $payload['rooms'] ?? [],
            ]);


        return response()->json($payload);
    }

    /**
     * Formulaire de création de réservation.
     * Préremplissage depuis le calendrier / workspace : voyage_id ou tour_id, travel_date_id (optionnel).
     * Le voyage affiché vient du Voyage Laravel. Les libellés viennent de WordPress quand disponible.
     */
    public function create(Request $request): View
    {
        $voyageIdParam = $request->query('voyage_id');
        $tourIdParam = $request->query('tour_id');
        $requestedTourId = (int) ($voyageIdParam !== null ? $voyageIdParam : ($tourIdParam ?? 0));
        $travelDateId = (int) $request->query('travel_date_id', 0);
        $requestedDepartureId = (int) $request->query('departure_id', 0);

        $clientsQuery = Client::query()->orderByDesc('id')->limit(200);
        $this->branchScope->scopeClients($clientsQuery, $request->user());
        $clients = $clientsQuery->get(['id', 'client_code', 'full_name', 'email', 'phone', 'national_id_number', 'passport_number']);
        $voyages = AdminWpTourCatalogQuery::reservableVoyages();

        // Compat: certains écrans passent un `tour_id` WordPress (wp_post_id) au lieu du Voyage Laravel id.
        // On tente de le convertir en Voyage id pour que les extras / départs se chargent correctement.
        if ($requestedTourId > 0 && $voyageIdParam === null) {
            $voyageByWpId = Voyage::query()
                ->with(['extras' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
                ->where('wp_post_id', $requestedTourId)
                ->where('status', 'actif')
                ->first();

            if ($voyageByWpId
                && $voyageByWpId->wp_post_id > 0
                && WpPost::query()->tours()->where('ID', $voyageByWpId->wp_post_id)->where('post_status', 'publish')->exists()
            ) {
                $requestedTourId = (int) $voyageByWpId->id;
                $voyages = $voyages->prepend($voyageByWpId)->unique('id')->values();
            }
        }

        if ($requestedTourId > 0 && $voyageIdParam === null) {
            $requestedVoyage = Voyage::query()
                ->with(['extras' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
                ->find($requestedTourId);
            // Ne préremplir que si le voyage est actif et lié   un WP publié
            if ($requestedVoyage
                && $requestedVoyage->status === 'actif'
                && $requestedVoyage->wp_post_id > 0
                && WpPost::query()->tours()->where('ID', $requestedVoyage->wp_post_id)->where('post_status', 'publish')->exists()
            ) {
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
                    $voyageFromTravelDate = Voyage::query()
                        ->where('wp_post_id', (int) $selectedTravelDate->travel_id)
                        ->first();

                    if ($voyageFromTravelDate) {
                        $requestedTourId = (int) $voyageFromTravelDate->id;
                    } else {
                        $selectedTravelDate = null;
                        $travelDateIncoherent = true;
                    }
                }
            }
        }

        $selectedDeparture = null;
        if ($requestedDepartureId > 0) {
            $selectedDeparture = Departure::query()->find($requestedDepartureId);
            if ($selectedDeparture && $requestedTourId > 0 && (int) $selectedDeparture->voyage_id !== $requestedTourId) {
                $requestedTourId = (int) $selectedDeparture->voyage_id;
            }
        }
        if (! $selectedDeparture && $requestedTourId > 0 && $travelDateId > 0) {
            $selectedDeparture = Departure::query()
                ->where('voyage_id', $requestedTourId)
                ->where('wp_travel_date_id', $travelDateId)
                ->first();
        }

        $selectedVoyageForPrice = $requestedTourId > 0
            ? ($voyages->firstWhere('id', $requestedTourId) ?: Voyage::query()->find($requestedTourId))
            : null;
        $selectedTravelDateForPrice = $selectedTravelDate;
        if (! $selectedTravelDateForPrice && $selectedDeparture?->wp_travel_date_id) {
            $selectedTravelDateForPrice = TravelDate::query()->find((int) $selectedDeparture->wp_travel_date_id);
        }
        $selectedUnitPrice = null;
        $selectedUnitPriceDebug = null;
        if ($selectedVoyageForPrice) {
            $selectedUnitPriceDebug = $this->reservationPricing->resolveUnitPrice(
                $selectedVoyageForPrice,
                $selectedDeparture,
                $selectedTravelDateForPrice
            );
            $selectedUnitPrice = $selectedUnitPriceDebug['unit_price'];

            Log::info('[Reservation Price Source]', [
                'tour_id' => $requestedTourId > 0 ? $requestedTourId : null,
                'title' => $selectedVoyageForPrice->name ?? null,
                'product_base_price' => $selectedUnitPriceDebug['sources']['wp_base_price'] ?? null,
                'adult_price' => $selectedUnitPriceDebug['sources']['wp_adult_price'] ?? null,
                'min_price' => $selectedUnitPriceDebug['sources']['wp_min_price'] ?? null,
                'final_reservation_unit_price' => $selectedUnitPrice ?? null,
            ]);
        }

        if ($requestedTourId > 0 && $voyages->where('id', $requestedTourId)->isEmpty()) {
            $requestedVoyage = Voyage::query()
                ->with(['extras' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
                ->find($requestedTourId);
            if ($requestedVoyage) {
                $voyages = $voyages->prepend($requestedVoyage)->unique('id')->values();
            }
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

        $optionalActivitiesByVoyage = $this->optionalActivityExtrasByVoyage($voyages);
        $extrasByVoyage = $voyages
            ->mapWithKeys(fn (Voyage $voyage) => [
                (string) $voyage->id => $voyage->extras
                    ->where('is_active', true)
                    ->values()
                    ->map(fn ($extra) => [
                        'id' => (int) $extra->id,
                        'type' => 'voyage_extra',
                        'source_type' => 'voyage_extra',
                        'source_id' => (int) $extra->id,
                        'name' => (string) $extra->name,
                        'description' => (string) ($extra->description ?? ''),
                        'price_adult' => (float) ($extra->price_adult ?? 0),
                        'price_child' => (float) ($extra->price_child ?? 0),
                        'extra_type' => (string) ($extra->extra_type ?? ''),
                        'icon' => (string) ($extra->icon ?? 'fa-plus-circle'),
                    ])
                    ->toBase()
                    ->merge($optionalActivitiesByVoyage[(string) $voyage->id] ?? [])
                    ->values()
                    ->all(),
            ])
            ->all();

        $preselectedTourId = null;
        if ($requestedTourId > 0 && $voyages->contains('id', $requestedTourId)) {
            $preselectedTourId = $requestedTourId;
        }

        $fastCreateMode = $preselectedTourId && $selectedDeparture?->id && $travelDateId > 0;

        return view('admin.reservations.create', [
            'voyages' => $voyages,
            'wpTitles' => $wpTitles,
            'clients' => $clients,
            'selectedTravelDate' => $selectedTravelDate,
            'selectedDepartureId' => $selectedDeparture?->id,
            'selectedDeparture' => $selectedDeparture,
            'travelDateId' => $travelDateId > 0 ? $travelDateId : null,
            'preselectedTourId' => $preselectedTourId,
            'preselectedTour' => $preselectedTourId ? ($voyages->firstWhere('id', $preselectedTourId) ?: Voyage::query()->find($preselectedTourId)) : null,
            'fastCreateMode' => $fastCreateMode,
            'travelDateIncoherent' => $travelDateIncoherent,
            'extrasByVoyage' => $extrasByVoyage,
            'selectedUnitPrice' => $selectedUnitPrice,
            'selectedUnitPriceDebug' => $selectedUnitPriceDebug,
        ]);
    }

    /**
     * @param  Collection<int, Voyage>  $voyages
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function optionalActivityExtrasByVoyage(Collection $voyages): array
    {
        $voyagesByWpId = $voyages
            ->filter(fn (Voyage $voyage) => (int) ($voyage->wp_post_id ?? 0) > 0)
            ->keyBy(fn (Voyage $voyage) => (int) $voyage->wp_post_id);

        if ($voyagesByWpId->isEmpty()) {
            return [];
        }

        try {
            $rows = TourDayActivity::query()
                ->with('activity')
                ->whereIn('tour_id', $voyagesByWpId->keys()->all())
                ->where('is_included', 0)
                ->orderBy('tour_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        } catch (\Throwable $e) {
            Log::warning('reservations.create.optional_activities_failed', ['error' => $e->getMessage()]);
            return [];
        }

        return $rows
            ->groupBy(fn (TourDayActivity $row) => (string) ($voyagesByWpId->get((int) $row->tour_id)?->id ?? 0))
            ->map(function (Collection $items) {
                return $items
                    ->filter(fn (TourDayActivity $row) => (int) ($row->activity_id ?? 0) > 0)
                    ->unique(function (TourDayActivity $row) {
                        return implode('|', [
                            (int) $row->tour_id,
                            (int) $row->activity_id,
                            trim((string) ($row->custom_title ?? '')),
                            trim((string) ($row->custom_description ?? '')),
                            (string) ($row->custom_price ?? ''),
                        ]);
                    })
                    ->map(function (TourDayActivity $row) {
                        $activity = $row->activity;
                        $title = trim((string) ($row->custom_title ?: ($activity?->title ?? 'Activit optionnelle')));
                        $description = trim((string) ($row->custom_description ?: ($activity?->description ?? '')));
                        $adultPrice = $row->custom_price !== null
                            ? (float) $row->custom_price
                            : (float) ($activity?->adult_price ?? $activity?->base_price ?? 0);
                        $childPrice = (float) ($activity?->child_price ?? 0);
                        if ($childPrice <= 0) {
                            $childPrice = $adultPrice;
                        }

                        return [
                            'id' => 'activity-'.$row->id,
                            'type' => 'activity',
                            'source_type' => 'activity',
                            'source_id' => (int) $row->id,
                            'activity_id' => (int) $row->activity_id,
                            'name' => $title,
                            'description' => $description !== '' ? $description : 'Activit propose au client comme option.',
                            'price_adult' => round($adultPrice, 2),
                            'price_child' => round($childPrice, 2),
                            'extra_type' => 'activity_optional',
                            'badge' => 'Activit optionnelle',
                            'icon' => 'fa-map-marker-alt',
                        ];
                    })
                    ->values()
                    ->all();
            })
            ->filter(fn ($items, string $voyageId) => (int) $voyageId > 0)
            ->all();
    }

    /**
     * Formulaire de création de réservation V2 (nouvelle UX).
     * Réutilise exactement la même logique que create() mais renvoie la vue V2 isolée.
     */
    public function createV2(Request $request): View
    {
        $voyageIdParam = $request->query('voyage_id');
        $tourIdParam = $request->query('tour_id');
        $requestedTourId = (int) ($voyageIdParam !== null ? $voyageIdParam : ($tourIdParam ?? 0));
        $travelDateId = (int) $request->query('travel_date_id', 0);
        $requestedDepartureId = (int) $request->query('departure_id', 0);

        $clientsQuery = Client::query()->orderByDesc('id')->limit(200);
        $this->branchScope->scopeClients($clientsQuery, $request->user());
        $clients = $clientsQuery->get(['id', 'client_code', 'full_name', 'email', 'phone', 'national_id_number', 'passport_number']);
        $voyages = AdminWpTourCatalogQuery::reservableVoyages();

        // Compat: `tour_id` est historiquement un wp_post_id (WordPress). Si `voyage_id` n'est pas fourni,
        // résoudre d'abord `tour_id` comme wp_post_id afin d'éviter les collisions avec un id Voyage Laravel.
        if ($requestedTourId > 0 && $voyageIdParam === null) {
            $voyageByWpId = Voyage::query()
                ->with(['extras' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
                ->where('wp_post_id', $requestedTourId)
                ->where('status', 'actif')
                ->first();

            if ($voyageByWpId
                && $voyageByWpId->wp_post_id > 0
                && WpPost::query()->tours()->where('ID', $voyageByWpId->wp_post_id)->where('post_status', 'publish')->exists()
            ) {
                $requestedTourId = (int) $voyageByWpId->id;
                $voyages = $voyages->prepend($voyageByWpId)->unique('id')->values();
            }
        }

        if ($requestedTourId > 0 && $voyages->where('id', $requestedTourId)->isEmpty()) {
            $requestedVoyage = Voyage::query()
                ->with(['extras' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
                ->find($requestedTourId);
            // Ne préremplir que si le voyage est actif et lié   un WP publié
            if ($requestedVoyage
                && $requestedVoyage->status === 'actif'
                && $requestedVoyage->wp_post_id > 0
                && WpPost::query()->tours()->where('ID', $requestedVoyage->wp_post_id)->where('post_status', 'publish')->exists()
            ) {
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
                    $voyageFromTravelDate = Voyage::query()
                        ->where('wp_post_id', (int) $selectedTravelDate->travel_id)
                        ->first();

                    if ($voyageFromTravelDate) {
                        $requestedTourId = (int) $voyageFromTravelDate->id;
                    } else {
                        $selectedTravelDate = null;
                        $travelDateIncoherent = true;
                    }
                }
            }
        }

        $selectedDeparture = null;
        if ($requestedDepartureId > 0) {
            $selectedDeparture = Departure::query()->find($requestedDepartureId);
            if ($selectedDeparture && $requestedTourId > 0 && (int) $selectedDeparture->voyage_id !== $requestedTourId) {
                $requestedTourId = (int) $selectedDeparture->voyage_id;
            }
        }
        if (! $selectedDeparture && $requestedTourId > 0 && $travelDateId > 0) {
            $selectedDeparture = Departure::query()
                ->where('voyage_id', $requestedTourId)
                ->where('wp_travel_date_id', $travelDateId)
                ->first();
        }

        if ($requestedTourId > 0 && $voyages->where('id', $requestedTourId)->isEmpty()) {
            $requestedVoyage = Voyage::query()
                ->with(['extras' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
                ->find($requestedTourId);
            if ($requestedVoyage) {
                $voyages = $voyages->prepend($requestedVoyage)->unique('id')->values();
            }
        }

        $optionalActivitiesByVoyage = $this->optionalActivityExtrasByVoyage($voyages);
        $extrasByVoyage = $voyages
            ->mapWithKeys(fn (Voyage $voyage) => [
                (string) $voyage->id => $voyage->extras
                    ->where('is_active', true)
                    ->values()
                    ->map(fn ($extra) => [
                        'id' => (int) $extra->id,
                        'type' => 'voyage_extra',
                        'source_type' => 'voyage_extra',
                        'source_id' => (int) $extra->id,
                        'name' => (string) $extra->name,
                        'description' => (string) ($extra->description ?? ''),
                        'price_adult' => (float) ($extra->price_adult ?? 0),
                        'price_child' => (float) ($extra->price_child ?? 0),
                        'extra_type' => (string) ($extra->extra_type ?? ''),
                        'icon' => (string) ($extra->icon ?? 'fa-plus-circle'),
                    ])
                    ->toBase()
                    ->merge($optionalActivitiesByVoyage[(string) $voyage->id] ?? [])
                    ->values()
                    ->all(),
            ])
            ->all();

        $preselectedTourId = null;
        $selectedUnitPrice = null;
        $selectedUnitPriceDebug = null;
        if ($requestedTourId > 0) {
            $voyageForPrice = $voyages->firstWhere('id', $requestedTourId)
                ?: Voyage::query()->find($requestedTourId);
            if ($voyageForPrice) {
                $selectedUnitPriceDebug = $this->reservationPricing->resolveUnitPrice(
                    $voyageForPrice,
                    $selectedDeparture,
                    $selectedTravelDate
                );
                $selectedUnitPrice = $selectedUnitPriceDebug['unit_price'];

                Log::info('[Reservation Price Source]', [
                    'tour_id' => $requestedTourId,
                    'title' => $voyageForPrice->name ?? null,
                    'product_base_price' => $selectedUnitPriceDebug['sources']['wp_base_price'] ?? null,
                    'adult_price' => $selectedUnitPriceDebug['sources']['wp_adult_price'] ?? null,
                    'min_price' => $selectedUnitPriceDebug['sources']['wp_min_price'] ?? null,
                    'final_reservation_unit_price' => $selectedUnitPrice ?? null,
                ]);
            }
        }
        if ($requestedTourId > 0 && $voyages->contains('id', $requestedTourId)) {
            $preselectedTourId = $requestedTourId;
        }

        return view('admin.reservations.create-v2', [
            'voyages' => $voyages,
            'wpTitles' => $wpTitles,
            'clients' => $clients,
            'selectedTravelDate' => $selectedTravelDate,
            'selectedDepartureId' => $selectedDeparture?->id,
            'travelDateId' => $travelDateId > 0 ? $travelDateId : null,
            'preselectedTourId' => $preselectedTourId,
            'travelDateIncoherent' => $travelDateIncoherent,
            'extrasByVoyage' => $extrasByVoyage,
            'selectedUnitPrice' => $selectedUnitPrice,
            'selectedUnitPriceDebug' => $selectedUnitPriceDebug,
        ]);
    }

    /**
     * API JSON : hítels et chambres pour un voyage (tour_id = Voyage.id).
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
            Log::info('URGENT ROOM ENDPOINT RESULT', [
                'mode' => $payload['mode'] ?? null,
                'rooms_count' => count($payload['rooms'] ?? []),
                'rooms' => $payload['rooms'] ?? [],
            ]);


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
        $travelDates = TravelDate::query()
            ->whereIn('id', $deps->pluck('wp_travel_date_id')->filter()->unique()->values()->all())
            ->get()
            ->keyBy('id');

        return response()->json([
            'departures' => $deps->map(function (Departure $d) use ($travelDates, $voyage) {
                $travelDate = $d->wp_travel_date_id ? $travelDates->get((int) $d->wp_travel_date_id) : null;
                $priceOverride = $travelDate?->price_override !== null ? (float) $travelDate->price_override : null;
                $resolvedPrice = $this->reservationPricing->resolveUnitPrice($voyage, $d, $travelDate);

                return [
                    'id' => $d->id,
                    'label' => ($d->start_date ? $d->start_date->format('d/m/Y') : '-')
                        .($d->end_date ? ' -> '.$d->end_date->format('d/m/Y') : ''),
                    'status' => $d->status,
                    'available_capacity' => (int) ($d->available_capacity ?? 0),
                    'base_price' => $d->base_price !== null ? (float) $d->base_price : null,
                    'sale_price' => $d->sale_price !== null ? (float) $d->sale_price : null,
                    'price_override' => $priceOverride,
                    'unit_price' => $resolvedPrice['unit_price'],
                    'unit_price_source' => $resolvedPrice['source'],
                    'unit_price_sources' => config('app.debug') ? $resolvedPrice['sources'] : null,
                    'wp_travel_date_id' => $d->wp_travel_date_id,
                ];
            })->values()->all(),
        ]);
    }

    /**
     * Hítels + chambres (stock départ) pour un départ donné.
     */
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
            Log::warning('Reservation rooms endpoint validation failed', [
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'tour_id' => $request->input('tour_id'),
                'departure_id' => $request->input('departure_id'),
                'travel_date_id' => $request->input('travel_date_id'),
            ]);

            return response()->json([
                'success' => false,
                'mode' => 'error',
                'message' => 'Erreur de chargement des disponibilits du dpart.',
                'debug' => config('app.debug') ? $e->errors() : null,
            ], 200);
        } catch (
            \Throwable $e
        ) {
            Log::error('Reservation rooms endpoint failed', [
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
                'message' => 'Erreur de chargement des disponibilits du dpart.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 200);
        }
    }

    /**
     * Enregistrement d'une réservation (avec chambres et validation capacité).
     */
    public function store(Request $request)
    {
        try {
            $this->mergeDepartureFromLegacyRequest($request);

        $data = $request->validate($this->reservationValidationRules());
        $data['passengers'] = $this->mergeMainTravelerIntoPassengers($request, $data);
        $this->validateDepartureMatchesTour($data);
        if (empty($data['client_external_id']) || (int) ($data['client_external_id'] ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'client_external_id' => ['Le client est requis. Crez un client ou slectionnez-en un existant.'],
            ]);
        }
        $data['room_allocations_payload'] = $this->extractRoomAllocationsPayloadFromRequest($request);
        $this->validateRoomingPayload($data['passengers'], $data['room_allocations_payload']);

        $hasPartialHalfDouble = collect($data['room_allocations_payload'] ?? [])
            ->contains(function ($allocation) {
                $mode = (string) ($allocation['occupancy_mode'] ?? '');
                $status = (string) ($allocation['status'] ?? '');

                return in_array($mode, ['half_male', 'half_female'], true) && $status === 'partial';
            });

        $pricingContext = $this->buildReservationPricingContext($request, $data);
        $extrasPayload = $pricingContext['extras_payload'];
        $paymentAmount = $pricingContext['payment_amount'];
        $pricing = $pricingContext['pricing'];
        $this->validateExtrasPayload($extrasPayload, $pricingContext['travelers_count']);

        // Enforce room selection when accommodation_mode == rooms
        $accommodationMode = (string) ($data['accommodation_mode'] ?? ($request->input('accommodation_mode') ?? 'rooms'));
        if ($accommodationMode === 'rooms' && empty($data['room_allocations_payload'])) {
            $hotelRooms = $data['hotel_rooms'] ?? $request->input('hotel_rooms', []);
            if (! is_array($hotelRooms) || count($hotelRooms) === 0) {
                throw ValidationException::withMessages([
                    'hotel_rooms' => ['Veuillez sélectionner au moins une chambre pour ce départ.'],
                ]);
            }

            $totalSelectedCapacity = 0;
            foreach ($hotelRooms as $idx => $hr) {
                $depRoomId = isset($hr['departure_hotel_room_id']) ? (int) $hr['departure_hotel_room_id'] : 0;
                $tourRoomId = isset($hr['tour_hotel_room_id']) ? (int) $hr['tour_hotel_room_id'] : 0;
                $count = isset($hr['room_count']) ? (int) $hr['room_count'] : 0;

                if ($depRoomId <= 0 && $tourRoomId <= 0) {
                    throw ValidationException::withMessages([
                        "hotel_rooms.{$idx}.departure_hotel_room_id" => ['Identifiant de chambre invalide ou manquant (departure_hotel_room_id ou tour_hotel_room_id requis).'],
                    ]);
                }
                if ($count < 1) {
                    throw ValidationException::withMessages([
                        "hotel_rooms.{$idx}.room_count" => ['Le nombre de chambres doit être au moins 1.'],
                    ]);
                }

                if ($depRoomId > 0) {
                    $room = DepartureHotelRoom::query()->find($depRoomId);
                    if (! $room) {
                        throw ValidationException::withMessages([
                            "hotel_rooms.{$idx}.departure_hotel_room_id" => ['La chambre sélectionnée est introuvable.'],
                        ]);
                    }

                    $availableRooms = (int) ($room->available_rooms ?? 0);
                    if (in_array($room->status, ['inactive', 'closed'], true)) {
                        throw ValidationException::withMessages([
                            "hotel_rooms.{$idx}.departure_hotel_room_id" => ['Ce type de chambre n\'est plus disponible pour ce dpart.'],
                        ]);
                    }
                    if ($count > $availableRooms) {
                        throw ValidationException::withMessages([
                            "hotel_rooms.{$idx}.room_count" => ["Il n'y a que {$availableRooms} chambre(s) disponible(s) pour ce type."],
                        ]);
                    }

                    $capacityPerRoom = max(1, (int) ($room->capacity_total ?? 0));
                    $totalSelectedCapacity += $count * $capacityPerRoom;
                } else {
                    // WP tour room reference
                    $tourRoom = \App\Models\TourHotelRoom::query()->find($tourRoomId);
                    if (! $tourRoom) {
                        throw ValidationException::withMessages([
                            "hotel_rooms.{$idx}.tour_hotel_room_id" => ['La chambre (tour) sélectionnée est introuvable.'],
                        ]);
                    }

                    $travelDateId = (int) ($data['travel_date_id'] ?? 0);
                    $availability = null;
                    if ($travelDateId > 0) {
                        $availability = \App\Models\TourHotelRoomAvailability::query()
                            ->where('tour_hotel_room_id', $tourRoomId)
                            ->where('travel_date_id', $travelDateId)
                            ->first();
                    }

                    $availableRooms = $availability ? (int) ($availability->available_rooms ?? 0) : (int) ($tourRoom->room_count ?? 0);
                    if ($count > $availableRooms) {
                        throw ValidationException::withMessages([
                            "hotel_rooms.{$idx}.room_count" => ["Il n'y a que {$availableRooms} chambre(s) disponible(s) pour ce type (WP)."],
                        ]);
                    }

                    $capacityPerRoom = max(1, (int) ($tourRoom->capacity_total ?? $tourRoom->capacity_adults ?? 0));
                    $totalSelectedCapacity += $count * $capacityPerRoom;
                }
            }

            if ($totalSelectedCapacity < $pricingContext['travelers_count']) {
                throw ValidationException::withMessages([
                    'hotel_rooms' => ['La capacité des chambres sélectionnées est insuffisante pour le nombre de voyageurs.'],
                ]);
            }
        }

        $data['status'] = $hasPartialHalfDouble
            ? Reservation::STATUS_SHARED_ROOM_PENDING
            : Reservation::STATUS_EN_COURS;
        $data['dossier_status'] = Reservation::DOSSIER_PENDING;
        $data['visa_ok'] = $request->boolean('visa_ok');
        $user = $request->user();
        $clientIdForBranch = ($data['client_mode'] ?? '') === 'existing' ? (int) ($data['client_external_id'] ?? 0) : null;
        $ownership = $this->branchScope->defaultReservationOwnership($user, $clientIdForBranch ?: null);
        $data['branch_id'] = $ownership['branch_id'];
        $data['sales_manager_id'] = $ownership['sales_manager_id'];
        $data['agent_id'] = $user->id;
        $data['assigned_to'] = $user->id;
        $data['created_by'] = $user->id;
        $data['created_by_user_id'] = $user->id;
        $data['base_price'] = $pricing['base_price'];
        $data['unit_price_before_discount'] = $pricing['unit_price_before_discount'] ?? $pricing['base_price'];
        $data['discount_type'] = $pricing['discount_type'] ?? null;
        $data['discount_value'] = $pricing['discount_value'] ?? 0;
        $data['unit_price_after_discount'] = $pricing['unit_price_after_discount'] ?? $pricing['base_price'];
        $data['total_base'] = $pricing['total_base'];
        $data['room_supplement_total'] = $pricing['room_supplement_total'];
        $data['extras_total'] = $pricing['extras_total'];
        $data['total_amount'] = $pricing['total_amount'];
        $data['paid_amount'] = $pricing['paid_amount'];
        $data['remaining_amount'] = $pricing['remaining_amount'];
        $data['payment_status'] = $pricing['payment_status'];
        $data['extras_payload'] = collect($pricing['details']['extras'] ?? [])->map(function (array $extra) {
            return [
                'voyage_extra_id' => $extra['voyage_extra_id'] ?? null,
                'source_type' => $extra['source_type'] ?? null,
                'source_id' => $extra['source_id'] ?? null,
                'name' => $extra['name'] ?? 'Extra',
                'description' => $extra['description'] ?? null,
                'unit_price' => $extra['unit_price_adult'] ?? 0,
                'quantity' => $extra['quantity'] ?? 1,
                'total_price' => $extra['total_price'] ?? 0,
                'application_scope' => $extra['application_scope'] ?? 'dossier',
                'traveler_keys' => $extra['traveler_keys'] ?? [],
            ];
        })->values()->all();

        $paymentPayload = null;
        if ($paymentAmount > 0) {
            if (empty($data['payment_type'])) {
                throw ValidationException::withMessages([
                    'payment_type' => ['Le type de paiement est requis lorsque vous indiquez un montant payé.'],
                ]);
            }
            $paymentPayload = [
                'payment_date' => $request->input('payment_date') ?: now()->toDateString(),
                'payment_method' => ! empty($data['payment_type']) ? $data['payment_type'] : 'Autre',
                'amount' => $paymentAmount,
                'reference' => $request->input('payment_reference'),
                'note' => $request->input('payment_note'),
                'created_by' => $user->id,
            ];
        }
        $data['payment_payload'] = $paymentPayload;

        $voyageRef = Voyage::query()->find((int) $data['tour_id']);
        $data['wp_tour_post_id'] = $voyageRef && $voyageRef->wp_post_id ? (int) $voyageRef->wp_post_id : null;
        $data['catalog_source_code'] = null;
        $data['voyage_flight_id'] = null;
        $data['documents_payload'] = collect($request->file('dossier_documents', []))
            ->filter()
            ->map(fn ($file, $index) => [
                'file' => $file,
                'type' => 'other',
                'title' => 'Document dossier #'.($index + 1),
                'created_by' => $user->id,
            ])->values()->all();

        $dossier = DB::transaction(function () use ($data, $request) {
            $client = $this->reservationDossier->resolveOrCreateClientFromPayload($data);
            if ($client) {
                $data['client_external_id'] = $client->id;
                $data['client_mode'] = 'existing';
            }

            $dossier = ReservationDossier::query()->create([
                'client_id' => $client?->id,
                'total_base' => (float) $data['total_base'],
                'room_supplement_total' => (float) $data['room_supplement_total'],
                'extras_total' => (float) $data['extras_total'],
                'total_amount' => (float) $data['total_amount'],
                'paid_amount' => (float) $data['paid_amount'],
                'remaining_amount' => (float) $data['remaining_amount'],
                'payment_status' => (string) $data['payment_status'],
                'dossier_status' => (string) $data['dossier_status'],
                'created_by' => $data['created_by_user_id'] ?? $data['created_by'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
            ]);

            $this->reservationDossier->assignUniqueDossierNumber($dossier, now());

            $data['reservation_dossier_id'] = $dossier->id;
            $data['dossier_number'] = $dossier->dossier_number;

            $reservation = $this->reservationService->create(
                $data,
                $request->file('payment_receipt'),
                $request->file('visa_document')
            );

            $reservation->reservation_dossier_id = $dossier->id;
            $reservation->dossier_number = $dossier->dossier_number;
            $reservation->save();

            $dossier->client_id = $reservation->client_external_id ?: $dossier->client_id;
            $dossier->main_reservation_id = $reservation->id;
            $dossier->save();

            $this->reservationDossier->syncDossierFromReservation($reservation->fresh('dossier'));

            return $dossier->fresh();
        });

            return redirect()
                ->route('admin.reservation-dossiers.show', $dossier)
                ->with('success', 'Dossier de réservation créé avec succès.');
        } catch (ValidationException $e) {
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                $messages = $e->errors();
                $grouped = [
                    'rooming' => [],
                    'extras' => [],
                    'client' => [],
                    'general' => [],
                ];
                foreach ($messages as $field => $errs) {
                    if (str_starts_with($field, 'room_allocations') || str_starts_with($field, 'hotel_rooms')) {
                        $grouped['rooming'] = array_merge($grouped['rooming'], $errs);
                    } elseif (str_starts_with($field, 'extras')) {
                        $grouped['extras'] = array_merge($grouped['extras'], $errs);
                    } elseif (str_starts_with($field, 'client_')) {
                        $grouped['client'] = array_merge($grouped['client'], $errs);
                    } else {
                        $grouped['general'] = array_merge($grouped['general'], $errs);
                    }
                }
                return response()->json([
                    'success' => false,
                    'errors' => array_filter($grouped),
                ], 422);
            }
            throw $e;
        }
    }

    /**
     * Fiche réservation : redirige vers lEURTMédition (même périmètre dEURTMaccès que {@see edit}).
     * La route est utilisée après création (workspace), liens « Ouvrir », etc.
     */
    public function show(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');

        if ($reservation->reservation_dossier_id) {
            return redirect()->route('admin.reservation-dossiers.show', $reservation->reservation_dossier_id);
        }

        return redirect()->route('admin.reservations.edit', $reservation);
    }

    /**
     * Formulaire d'édition d'une réservation.
     */
    public function edit(Request $request, Reservation $reservation): View
    {
        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');
        $reservation->load(['passengers', 'client', 'offer', 'extras', 'payments.creator', 'documents.creator', 'histories.user', 'reservationRooms.departureHotelRoom', 'departure', 'branch', 'partner', 'creator', 'createdBy']);
        $voyages = AdminWpTourCatalogQuery::reservableVoyages();
        // Conserver le voyage historique de la réservation même s'il n'est plus reservable
        $reservationVoyageId = $reservation->tour_id;
        if ($reservationVoyageId && $voyages->where('id', $reservationVoyageId)->isEmpty()) {
            $historicalVoyage = Voyage::query()->find($reservationVoyageId, ['id', 'name', 'slug']);
            if ($historicalVoyage) {
                $voyages = $voyages->prepend($historicalVoyage)->unique('id')->values();
            }
        }
        $clientsQuery = Client::query()->orderByDesc('id')->limit(200);
        $this->branchScope->scopeClients($clientsQuery, $request->user());
        $clients = $clientsQuery->get(['id', 'client_code', 'full_name', 'email', 'phone', 'national_id_number', 'passport_number']);

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
        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');
        $this->mergeDepartureFromLegacyRequest($request, $reservation);

        $data = $request->validate($this->reservationValidationRules(true));
        $this->validateDepartureMatchesTour($data);

        $pricingContext = $this->buildReservationPricingContext($request, $data);
        $extrasPayload = $pricingContext['extras_payload'];
        $pricing = $pricingContext['pricing'];

        $data['visa_ok'] = $request->boolean('visa_ok');
        $data['updated_by'] = $request->user()->id;
        $data['base_price'] = $pricing['base_price'];
        $data['unit_price_before_discount'] = $pricing['unit_price_before_discount'] ?? $pricing['base_price'];
        $data['discount_type'] = $pricing['discount_type'] ?? null;
        $data['discount_value'] = $pricing['discount_value'] ?? 0;
        $data['unit_price_after_discount'] = $pricing['unit_price_after_discount'] ?? $pricing['base_price'];
        $data['total_base'] = $pricing['total_base'];
        $data['room_supplement_total'] = $pricing['room_supplement_total'];
        $data['extras_total'] = $pricing['extras_total'];
        $data['total_amount'] = $pricing['total_amount'];
        $data['paid_amount'] = (float) ($reservation->paid_amount ?? 0);
        $data['remaining_amount'] = max(0, round($pricing['total_amount'] - $data['paid_amount'], 2));
        $data['payment_status'] = $this->reservationPricing->derivePaymentStatus((float) $pricing['total_amount'], (float) $data['paid_amount']);
        $data['dossier_status'] = $reservation->dossier_status ?: Reservation::DOSSIER_PENDING;
        $data['extras_payload'] = collect($pricing['details']['extras'] ?? [])->map(function (array $extra) {
            return [
                'voyage_extra_id' => $extra['voyage_extra_id'] ?? null,
                'source_type' => $extra['source_type'] ?? null,
                'source_id' => $extra['source_id'] ?? null,
                'name' => $extra['name'] ?? 'Extra',
                'description' => $extra['description'] ?? null,
                'unit_price' => $extra['unit_price_adult'] ?? 0,
                'quantity' => $extra['quantity'] ?? 1,
                'total_price' => $extra['total_price'] ?? 0,
                'application_scope' => $extra['application_scope'] ?? 'dossier',
                'traveler_keys' => $extra['traveler_keys'] ?? [],
            ];
        })->values()->all();

        $dossier = DB::transaction(function () use ($request, $reservation, $data) {
            $client = $this->reservationDossier->resolveOrCreateClientFromPayload($data, $reservation);
            if ($client) {
                $data['client_external_id'] = $client->id;
                $data['client_mode'] = 'existing';
            }

            $dossier = $reservation->dossier;
            if (! $dossier) {
                $dossier = ReservationDossier::query()->create([
                    'client_id' => $client?->id ?: $reservation->client_external_id,
                    'main_reservation_id' => $reservation->id,
                    'created_by' => $reservation->created_by_user_id ?: $reservation->created_by,
                    'assigned_to' => $reservation->assigned_to ?: $reservation->agent_id,
                ]);
                $this->reservationDossier->assignUniqueDossierNumber($dossier, $reservation->created_at ?: now());
            }

            $data['reservation_dossier_id'] = $dossier->id;
            $data['dossier_number'] = $dossier->dossier_number;
            $updatedReservation = $this->reservationService->update(
                $reservation,
                $data,
                $request->file('payment_receipt'),
                $request->file('visa_document')
            );

            $updatedReservation->reservation_dossier_id = $dossier->id;
            $updatedReservation->dossier_number = $dossier->dossier_number;
            $updatedReservation->save();

            $dossier->client_id = $updatedReservation->client_external_id ?: $dossier->client_id;
            $dossier->main_reservation_id = $updatedReservation->id;
            $dossier->save();

            $this->reservationDossier->syncDossierFromReservation($updatedReservation->fresh('dossier'));

            return $dossier->fresh();
        });

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
            ->route('admin.reservation-dossiers.show', $dossier)
            ->with('success', 'Réservation mise à jour.');
    }

    /**
     * Nombre total de voyageurs : 1 (principal) + accompagnants avec au moins un nom renseigné.
     */
    public function pricingPreview(Request $request): JsonResponse
    {
        $this->mergeDepartureFromLegacyRequest($request);

        $data = $request->validate([
            'tour_id' => 'required|integer',
            'departure_id' => 'required|integer|exists:departures,id',
            'travel_date_id' => 'nullable|integer',
            'payment_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'extras_json' => 'nullable|string',
            'travelers_json' => 'nullable|string',
            'room_allocations_json' => 'nullable|string',
            'hotel_rooms' => 'nullable|array',
            'hotel_rooms.*.departure_hotel_room_id' => 'nullable|integer',
            'hotel_rooms.*.room_count' => 'nullable|integer|min:0',
            'passengers' => 'nullable|array',
            'passengers.*.first_name' => 'nullable|string|max:100',
            'passengers.*.last_name' => 'nullable|string|max:100',
            'passengers.*.type' => 'nullable|in:adult,child,infant',
        ]);
        $this->validateDepartureMatchesTour($data);

        $context = $this->buildReservationPricingContext($request, $data);

        return response()->json([
            'pricing' => $context['pricing'],
            'travelers_count' => $context['travelers_count'],
        ]);
    }

    public function storePayment(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accf¨s non autorisf© f  cette rf©servation.');

        $data = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:100',
            'amount' => 'required|numeric|gt:0',
            'reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:2000',
            'proof_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        $payment = $this->reservationDossier->addPayment($reservation, [
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'amount' => (float) $data['amount'],
            'reference' => $data['reference'] ?? null,
            'note' => $data['note'] ?? null,
            'created_by' => $request->user()->id,
        ], $request->file('proof_file'));

        if (! $payment) {
            return redirect()->back()->with('error', 'Le paiement nâ`"a pas pu fªtre enregistrf©.');
        }

        $reservation->save();
        $this->agentCommissionService->refreshFromReservationStatus($reservation->fresh(), \App\Models\AgentCommissionEntry::SOURCE_PAYMENT_RECEIVED);

        if ($reservation->payment_status === Reservation::PAYMENT_STATUS_PAID) {
            $this->agentCommissionService->markAsPayable($reservation->fresh(), $request->user());
        }

        $this->reservationDossier->addHistory(
            $reservation,
            'reservation.payment_added',
            $request->user()->id,
            null,
            [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'payment_status' => $reservation->payment_status,
                'remaining_amount' => $reservation->remaining_amount,
            ],
            $data['note'] ?? null
        );

        return redirect()->back()->with('success', 'Paiement ajoutf© au dossier.');
    }

    public function storeDocument(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accf¨s non autorisf© f  cette rf©servation.');

        $data = $request->validate([
            'type' => 'required|string|max:100',
            'title' => 'required|string|max:190',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        $document = $this->reservationDossier->addUploadedDocument(
            $reservation,
            $data['type'],
            $data['title'],
            $request->file('file'),
            $request->user()->id
        );

        $this->reservationDossier->addHistory(
            $reservation,
            'reservation.document_added',
            $request->user()->id,
            null,
            [
                'document_id' => $document->id,
                'type' => $document->type,
                'title' => $document->title,
            ]
        );

        return redirect()->back()->with('success', 'Document ajoutf© au dossier.');
    }

    public function storeNote(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');
        abort_unless(
            $request->user()->can('reservations.view_internal_notes') || $request->user()->can('reservations.update'),
            403,
            'Vous ne pouvez pas ajouter de note interne sur ce dossier.'
        );

        $data = $request->validate([
            'note' => 'required|string|max:5000',
        ]);

        $timestamp = now()->format('d/m/Y H:i');
        $author = trim((string) ($request->user()->name ?? 'Admin'));
        $entry = '['.$timestamp.'] '.$author.PHP_EOL.trim((string) $data['note']);
        $existingNotes = trim((string) ($reservation->notes ?? ''));

        $reservation->notes = $existingNotes !== ''
            ? $existingNotes.PHP_EOL.PHP_EOL.$entry
            : $entry;
        $reservation->updated_by = $request->user()->id;
        $reservation->save();

        $this->reservationDossier->syncDossierFromReservation($reservation);
        $this->reservationDossier->addHistory(
            $reservation,
            'reservation.note_added',
            $request->user()->id,
            null,
            [
                'note_excerpt' => Str::limit(trim((string) $data['note']), 160),
            ],
            trim((string) $data['note'])
        );

        return redirect()->back()->with('success', 'Note interne ajoutée au dossier.');
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accf¨s non autorisf© f  cette rf©servation.');

        $data = $request->validate([
            'note' => 'nullable|string|max:2000',
        ]);

        $oldState = [
            'status' => $reservation->status,
            'dossier_status' => $reservation->dossier_status,
            'cancelled_at' => optional($reservation->cancelled_at)->toIso8601String(),
        ];

        $this->reservationDossier->applyCancellationState($reservation);
        $reservation->save();
        $this->agentCommissionService->cancelForReservation($reservation->fresh(), $request->user());

        $this->reservationDossier->addHistory(
            $reservation,
            'reservation.cancelled',
            $request->user()->id,
            $oldState,
            [
                'status' => $reservation->status,
                'dossier_status' => $reservation->dossier_status,
                'cancelled_at' => optional($reservation->cancelled_at)->toIso8601String(),
            ],
            $data['note'] ?? null
        );

        return redirect()->back()->with('success', 'Dossier annulf©.');
    }

    public function dossierPdf(Request $request, Reservation $reservation)
    {
        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accf¨s non autorisf© f  cette rf©servation.');

        $reservation->load([
            'client',
            'offer',
            'departure',
            'passengers',
            'reservationRooms.departureHotelRoom',
            'extras',
            'payments.creator',
            'documents.creator',
            'histories.user',
        ]);

        $filename = Str::slug((string) ($reservation->dossier_number ?: 'reservation-'.$reservation->id)).'-dossier.pdf';

        return Pdf::loadView('admin.reservations.pdf.dossier', [
            'reservation' => $reservation,
        ])->stream($filename);
    }

    public function invoice(Request $request, Reservation $reservation)
    {
        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');

        $reservation->load([
            'client',
            'offer',
            'departure',
            'passengers',
            'extras',
            'payments.creator',
        ]);

        $filename = Str::slug((string) ($reservation->dossier_number ?: 'reservation-'.$reservation->id)).'-invoice.pdf';

        return Pdf::loadView('admin.reservations.pdf.invoice', [
            'reservation' => $reservation,
        ])->stream($filename);
    }

    public function paymentReceiptPdf(Request $request, Reservation $reservation, ReservationPayment $payment)
    {
        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accf¨s non autorisf© f  cette rf©servation.');
        abort_unless((int) $payment->reservation_id === (int) $reservation->id, 404);

        $payment->loadMissing('creator');
        $reservation->loadMissing(['client', 'offer', 'departure']);

        $filename = Str::slug((string) ($reservation->dossier_number ?: 'reservation-'.$reservation->id)).'-payment-'.$payment->id.'.pdf';

        return Pdf::loadView('admin.reservations.pdf.payment-receipt', [
            'reservation' => $reservation,
            'payment' => $payment,
        ])->stream($filename);
    }

    private function reservationValidationRules(bool $updating = false): array
    {
        $rules = [
            'accommodation_mode' => 'nullable|in:rooms,places_only,blocked',
            'tour_id' => 'required|integer',
            'departure_id' => 'required|integer|exists:departures,id',
            'travel_date_id' => 'nullable|integer',
            'client_mode' => 'required|in:existing,new',
            'client_external_id' => 'required_if:client_mode,existing|nullable|integer|exists:clients,id',
            'client_first_name' => 'required_if:client_mode,new|nullable|string|max:100',
            'client_last_name' => 'required_if:client_mode,new|nullable|string|max:100',
            'client_phone' => 'required_if:client_mode,new|nullable|string|max:50',
            'client_email' => 'nullable|email|max:190',
            'client_nationality' => 'nullable|string|max:120',
            'client_address' => 'nullable|string|max:255',
            'client_document_type' => 'nullable|string|max:50',
            'client_document_number' => 'nullable|string|max:100',
            'client_birth_date' => 'nullable|date',
            'client_gender' => 'nullable|in:male,female',
            'client_traveler_type' => 'nullable|in:adult,child,infant',
            // consumes_bed inferred from traveler_type: adult=true, child=true, infant=false
            'payment_type' => 'nullable|string|max:50',
            'payment_receipt' => 'nullable|file|max:5120',
            'base_price' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'total_base' => 'nullable|numeric|min:0',
            'room_supplement_total' => 'nullable|numeric|min:0',
            'extras_total' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
            'payment_reference' => 'nullable|string|max:120',
            'payment_note' => 'nullable|string|max:2000',
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
            'passengers.*.gender' => 'nullable|in:male,female',
            'passengers.*.relationship_to_main' => 'nullable|in:spouse,child,parent,friend,group,solo',
            'passengers.*.consumes_bed' => 'nullable|boolean',
            'passengers.*.birth_date' => 'nullable|date',
            'passengers.*.document_type' => 'nullable|string|max:50',
            'passengers.*.document_number' => 'nullable|string|max:100',
            'passengers.*.traveler_key' => 'nullable|string|max:80',
        ];

        if (! $updating) {
            $rules['dossier_documents'] = 'nullable|array';
            $rules['dossier_documents.*'] = 'file|max:10240';
        } else {
            $rules['passengers.*.id'] = 'nullable|integer';
        }

        return $rules;
    }

    private function extractExtrasPayloadFromRequest(Request $request): array
    {
        if (! $request->filled('extras_json')) {
            return [];
        }

        $decoded = json_decode($request->string('extras_json')->toString(), true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function extractRoomAllocationsPayloadFromRequest(Request $request): array
    {
        if (! $request->filled('room_allocations_json')) {
            return [];
        }

        $decoded = json_decode($request->string('room_allocations_json')->toString(), true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function mergeMainTravelerIntoPassengers(Request $request, array $data): array
    {
        $passengers = is_array($data['passengers'] ?? null) ? $data['passengers'] : [];
        $passengers['__main'] = [
            'traveler_key' => 'main',
            'first_name' => $data['client_first_name'] ?? null,
            'last_name' => $data['client_last_name'] ?? null,
            'type' => $request->input('client_traveler_type', 'adult'),
            'gender' => $request->input('client_gender'),
            'birth_date' => $request->input('client_birth_date'),
            'document_type' => $data['client_document_type'] ?? null,
            'document_number' => $data['client_document_number'] ?? null,
            'nationality' => $request->input('client_nationality'),
            'relationship_to_main' => 'main',
            'consumes_bed' => ($request->input('client_traveler_type', 'adult') !== 'infant'),
        ];

        return $passengers;
    }

    private function validateRoomingPayload(array $passengers, array $allocations): void
    {
        if ($allocations === []) {
            return;
        }

        $travelers = collect($passengers)
            ->filter(fn ($row) => is_array($row))
            ->mapWithKeys(function (array $row, $key) {
                $travelerKey = (string) ($row['traveler_key'] ?? ($key === '__main' ? 'main' : $key));

                return [$travelerKey => [
                    'gender' => $row['gender'] ?? null,
                    'relationship' => $row['relationship_to_main'] ?? null,
                    'consumes_bed' => filter_var($row['consumes_bed'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ]];
            });

        $assigned = [];
        foreach ($allocations as $index => $allocation) {
            if (! is_array($allocation)) {
                continue;
            }

            $capacity = max(0, (int) ($allocation['capacity'] ?? 0));
            $travelerKeys = is_array($allocation['traveler_keys'] ?? null) ? $allocation['traveler_keys'] : [];
            if ($capacity <= 0 || count($travelerKeys) > $capacity) {
                throw ValidationException::withMessages([
                    "room_allocations.{$index}" => ['Capacite chambre invalide.'],
                ]);
            }
            if (count($travelerKeys) === 0) {
                throw ValidationException::withMessages([
                    "room_allocations.{$index}" => ['Cette chambre ne contient aucun voyageur.'],
                ]);
            }

            $mode = (string) ($allocation['occupancy_mode'] ?? '');
            if ($mode === 'half_male') {
                $hasNonMale = collect($travelerKeys)
                    ->contains(fn ($key) => ($travelers->get($key)['gender'] ?? null) !== 'male');
                if ($hasNonMale) {
                    throw ValidationException::withMessages([
                        "room_allocations.{$index}" => ['Demi-double homme incompatible : tous les voyageurs doivent etre des hommes.'],
                    ]);
                }
            }
            if ($mode === 'half_female') {
                $hasNonFemale = collect($travelerKeys)
                    ->contains(fn ($key) => ($travelers->get($key)['gender'] ?? null) !== 'female');
                if ($hasNonFemale) {
                    throw ValidationException::withMessages([
                        "room_allocations.{$index}" => ['Demi-double femme incompatible : tous les voyageurs doivent etre des femmes.'],
                    ]);
                }
            }

            foreach ($travelerKeys as $travelerKey) {
                if (isset($assigned[$travelerKey])) {
                    throw ValidationException::withMessages([
                        'room_allocations' => ['Un voyageur est affecte deux fois.'],
                    ]);
                }
                $assigned[$travelerKey] = true;
            }

            $genders = collect($travelerKeys)
                ->map(fn ($key) => $travelers->get($key)['gender'] ?? null)
                ->filter()
                ->unique()
                ->values();
            $relations = collect($travelerKeys)
                ->map(fn ($key) => $travelers->get($key)['relationship'] ?? null)
                ->filter()
                ->unique()
                ->values();
            $mode = (string) ($allocation['occupancy_mode'] ?? '');
            $familyAllowed = in_array($mode, ['family'], true)
                || $relations->intersect(['spouse', 'child', 'parent', 'main'])->isNotEmpty();

            if ($genders->count() > 1 && ! $familyAllowed) {
                throw ValidationException::withMessages([
                    "room_allocations.{$index}" => ['Melange homme/femme interdit sans relation couple ou famille.'],
                ]);
            }
        }

        $missing = $travelers
            ->filter(fn ($row) => (bool) ($row['consumes_bed'] ?? true))
            ->keys()
            ->filter(fn ($key) => ! isset($assigned[$key]))
            ->values();

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'room_allocations' => ['Tous les voyageurs qui consomment un lit doivent etre affectes a une chambre.'],
            ]);
        }
    }

    private function validateExtrasPayload(array $extrasPayload, int $travelersCount): void
    {
        foreach ($extrasPayload as $index => $extra) {
            if (! is_array($extra)) {
                continue;
            }
            $quantity = (int) ($extra['quantity'] ?? 0);
            if ($quantity < 1) {
                continue;
            }
            $scope = (string) ($extra['application_scope'] ?? 'dossier');
            $name = (string) ($extra['name'] ?? 'Extra');
            $maxAllowed = 1;
            if ($scope === 'traveler_selection') {
                $maxAllowed = count($extra['traveler_keys'] ?? []);
            } elseif ($scope === 'per_traveler') {
                $maxAllowed = $travelersCount;
            }
            if ($quantity > $maxAllowed && $maxAllowed > 0) {
                throw ValidationException::withMessages([
                    "extras.{$index}" => ["Extra \"{$name}\" : quantite max autorisee = {$maxAllowed}."],
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{pricing: array<string, mixed>, extras_payload: array<int, array<string, mixed>>, payment_amount: float, travelers_count: int}
     */
    private function buildReservationPricingContext(Request $request, array $data): array
    {
        $travelersCount = $this->computeTotalTravelers($data['passengers'] ?? $request->input('passengers', []));
        $accommodationMode = (string) ($request->input('accommodation_mode') ?? 'rooms');

        $extrasPayload = $this->extractExtrasPayloadFromRequest($request);
        $paymentAmount = round((float) $request->input('payment_amount', 0), 2);

        $pricing = $this->reservationPricing->calculate([
            'tour_id' => (int) $data['tour_id'],
            'departure_id' => (int) $data['departure_id'],
            'travel_date_id' => (int) ($data['travel_date_id'] ?? 0),
            'hotel_rooms' => $request->input('hotel_rooms', []),
            'room_allocations' => $this->extractRoomAllocationsPayloadFromRequest($request),
            'passengers' => $data['passengers'] ?? $request->input('passengers', []),
            'extras_json' => $extrasPayload,
            'payment_amount' => $paymentAmount,
            'discount_type' => $request->input('discount_type'),
            'discount_value' => $request->input('discount_value'),
            'accommodation_mode' => $accommodationMode,
        ]);

        // Log pricing context for debugging
        try {
            Log::info('Reservation final pricing validation', [
                'tour_id' => (int) $data['tour_id'],
                'departure_id' => (int) ($data['departure_id'] ?? 0),
                'travel_date_id' => (int) ($data['travel_date_id'] ?? 0),
                'accommodation_mode' => $accommodationMode,
                'request_base_price' => $request->input('base_price'),
                'request_total_amount' => $request->input('total_amount'),
                'pricing_unit_price' => $pricing['base_price'] ?? null,
                'pricing_total_amount' => $pricing['total_amount'] ?? null,
                'pricing_source' => $pricing['details']['departure']['base_price'] ?? null,
            ]);
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        if ($accommodationMode === 'places_only') {
            // ensure pricing service found a valid unit price
            if (empty($pricing['base_price']) || (float) $pricing['base_price'] <= 0) {
                throw ValidationException::withMessages([
                    'base_price' => ['Aucun prix valide trouvé pour ce départ.'],
                ]);
            }

            $this->validateDeparturePlacesCapacity((int) $data['departure_id'], (int) ($data['travel_date_id'] ?? 0), $travelersCount);
        } else {
            $this->validateRoomCapacity(
                (int) $data['departure_id'],
                (int) ($data['travel_date_id'] ?? 0),
                (int) $data['tour_id'],
                $travelersCount
            );
        }


        return [
            'pricing' => $pricing,
            'extras_payload' => $extrasPayload,
            'payment_amount' => $paymentAmount,
            'travelers_count' => $travelersCount,
        ];
    }

    private function computeTotalTravelers(array $passengers): int
    {
        $count = isset($passengers['__main']) ? 0 : 1;
        foreach ($passengers as $p) {
            if (! is_array($p)) {
                continue;
            }
            $hasName = (trim((string) ($p['first_name'] ?? '')) !== '') || (trim((string) ($p['last_name'] ?? '')) !== '');
            $travelerKey = trim((string) ($p['traveler_key'] ?? ''));
            $hasTemplate = $travelerKey !== '' && $travelerKey !== 'main';
            $hasType = trim((string) ($p['type'] ?? '')) !== '';
            if ($hasName || $hasTemplate || $hasType) {
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
     * La capacité vient des chambres configurées dans lEURTMhítels du voyage + lEURTMoccupation (stock réel).
     */
    private function validateRoomCapacity(int $departureId, int $travelDateId, int $tourId, int $totalTravelers): void
    {
        if ($totalTravelers <= 0 || $tourId <= 0) {
            return;
        }

        if ($departureId > 0) {
            $dep = Departure::query()->with(['departureHotels.rooms'])->find($departureId);
            if (! $dep) {
                return;
            }

            $hasAssociatedHotels = $dep->departureHotels->contains(function ($hotel) {
                return ($hotel->is_active ?? true) === true;
            });
            $configuredRooms = $dep->departureHotels
                ->flatMap(fn ($hotel) => $hotel->rooms)
                ->filter(fn ($room) => ($room->status ?? null) !== 'inactive')
                ->values();

            if ($configuredRooms->isNotEmpty()) {
                if ((int) $dep->available_capacity < $totalTravelers) {
                    throw ValidationException::withMessages([
                        'hotel_rooms' => [
                            "Capacité insuffisante sur ce départ ({$dep->available_capacity} place(s) disponible(s)) pour {$totalTravelers} voyageur(s).",
                        ],
                    ]);
                }

                return;
            }

            if ($hasAssociatedHotels) {
                throw ValidationException::withMessages([
                    'hotel_rooms' => ['Configuration incomplète : ajoutez les chambres pour ce départ.'],
                ]);
            }

            if ((int) $dep->available_capacity < $totalTravelers) {
                throw ValidationException::withMessages([
                    'hotel_rooms' => [
                        "Stock insuffisant : il reste seulement {$dep->available_capacity} places.",
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
            // Aucune capacité configurée : la réservation sera bloquée plus tard côt`© service si nécessaire.
            return;
        }

        $occupiedSeats = 0;
        try {
            $occupiedSeats = (int) \DB::table('tour_room_type_occupancies')
                ->where('travel_date_id', $travelDateId)
                ->sum('seats_occupied_total');
        } catch (\Throwable $e) {
            // Table absente ou erreur DB : pas de contrôle de capacité fine ici ; le service cr`©e la réservation en chemin standard.
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
     * Validate capacity based on departure stock (places only mode).
     */
    private function validateDeparturePlacesCapacity(int $departureId, int $travelDateId, int $totalTravelers): void
    {
        if ($totalTravelers <= 0) {
            return;
        }

        if ($departureId > 0) {
            $dep = Departure::query()->find($departureId);
            if (! $dep) {
                throw ValidationException::withMessages([
                    'departure_id' => ['Départ invalide.'],
                ]);
            }

            $available = (int) ($dep->available_capacity ?? $dep->available_places ?? 0);
            if ($available < $totalTravelers) {
                throw ValidationException::withMessages([
                    'hotel_rooms' => ["Stock insuffisant : il reste seulement {$available} places."],
                ]);
            }

            return;
        }

        // If no explicit departure, try travel_date fallback (best-effort)
        if ($travelDateId > 0) {
            $voyageOccupancy = 0;
            try {
                $occupied = DB::table('tour_room_type_occupancies')
                    ->where('travel_date_id', $travelDateId)
                    ->sum('seats_occupied_total');
                $voyageOccupancy = (int) $occupied;
            } catch (\Throwable $e) {
                return;
            }
            // can't determine total capacity here reliably EUR" allow proceed
            return;
        }
    }

    /**
     * Suppression d'une réservation.
     */
    public function destroy(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');
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
        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');
        $this->reservationService->validateReservation($reservation);

        return redirect()
            ->back()
            ->with('success', 'Réservation validée.');
    }

    /**
     * Liste des réservations compatibles pour jumelage demi-double.
     */
    public function pairingCandidates(Request $request, Reservation $reservation)
    {
        try {
            abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');

            if (! $reservation->needsSharedRoomPairing()) {
                return response()->json(['error' => 'Cette réservation n\'est pas en attente de jumelage.'], 422);
            }

            if (empty($reservation->tour_id) || empty($reservation->departure_id)) {
                return response()->json(['error' => 'Cette réservation n\'est pas associée à un voyage ou un départ.'], 422);
            }

            $sourceRooms = $reservation->reservationRooms
                ->filter(function ($rr) use ($reservation) {
                    $mode = (string) ($rr->room_mode ?? '');
                    $state = (string) ($rr->shared_room_status ?? '');
                    $paired = (int) ($rr->paired_reservation_id ?? 0);
                    $occupied = (int) ($rr->passenger_count ?? 0);
                    $capacity = $reservation->resolveRoomCapacity($rr);

                    $isExplicit = in_array($mode, ['half_male', 'half_female', 'shared_double'], true);
                    $normalized = str_replace(['-', '_'], '', strtolower($mode));
                    $isVariant = in_array($normalized, ['halfmale','halffemale','shareddouble','demidoublehomme','demidoublefemme','halfdoublemale','halfdoublefemale'], true);

                    $sourceType = (string) ($rr->source_room_type ?? '');
                    $roomSnapshot = strtolower((string) ($rr->room_type_snapshot ?? ''));
                    $isDoubleRoom = str_contains($roomSnapshot, 'double') || str_contains($roomSnapshot, 'demi-double') || $sourceType === 'double' || $sourceType === 'tour_hotel_room';
                    $looksLikeHalfDouble = ($isDoubleRoom && $occupied > 0 && $occupied < $capacity && $capacity === 2);

                    $isHalfDouble = $isExplicit || $isVariant || $looksLikeHalfDouble;

                    return $isHalfDouble
                        && $state !== 'paired'
                        && $paired <= 0
                        && $occupied > 0
                        && $occupied < $capacity;
                });

            if ($sourceRooms->isEmpty()) {
                return response()->json(['error' => 'Aucune place demi-double en attente sur cette réservation.'], 422);
            }

            $sourceRoom = $sourceRooms->first();
            $sourceMode = (string) ($sourceRoom->room_mode ?? '');
            $sourceCapacity = $reservation->resolveRoomCapacity($sourceRoom);
            $sourceOccupied = (int) ($sourceRoom->passenger_count ?? 0);
            $sourceRemaining = max(0, $sourceCapacity - $sourceOccupied);

            $genderRequirement = match ($sourceMode) {
                'half_male' => 'male',
                'half_female' => 'female',
                default => null,
            };

            $candidates = Reservation::query()
                ->with(['client', 'travelDate', 'reservationRooms', 'passengers'])
                ->where('id', '!=', $reservation->id)
                ->where('tour_id', $reservation->tour_id)
                ->where('departure_id', $reservation->departure_id)
                ->where(function ($q) {
                    $q->whereNull('dossier_status')
                        ->orWhere('dossier_status', '!=', Reservation::DOSSIER_CANCELLED)
                        ->orWhere('dossier_status', '');
                })
                ->whereHas('reservationRooms', function ($q) use ($sourceMode) {
                    $q->where(function ($qq) use ($sourceMode) {
                        if ($sourceMode === '') {
                            $qq->whereNull('room_mode')
                                ->orWhere('room_mode', '');
                        } else {
                            $qq->where('room_mode', $sourceMode);
                        }
                    })
                        ->whereRaw('COALESCE(passenger_count, 0) > 0');
                })
                ->orderBy('created_at')
                ->get()
                ->filter(function (Reservation $candidate) use ($genderRequirement, $reservation, $sourceMode) {
                    $candidateRoom = $candidate->reservationRooms->first(function ($rr) use ($sourceMode, $candidate) {
                        $state = (string) ($rr->shared_room_status ?? '');
                        $paired = (int) ($rr->paired_reservation_id ?? 0);
                        $occupied = (int) ($rr->passenger_count ?? 0);
                        $capacity = $candidate->resolveRoomCapacity($rr);
                        return (string) ($rr->room_mode ?? '') === $sourceMode
                            && $state !== 'paired'
                            && $paired <= 0
                            && $occupied > 0
                            && $occupied < $capacity;
                    });
                    if (! $candidateRoom) {
                        return false;
                    }

                    if ($genderRequirement) {
                        $hasMatchingGender = $candidate->passengers->some(function ($p) use ($genderRequirement) {
                            return (string) ($p->gender ?? '') === $genderRequirement;
                        });
                        if (! $hasMatchingGender) {
                            return false;
                        }
                    }

                    $alreadyPaired = $candidate->reservationRooms->some(function ($rr) {
                        return (int) ($rr->paired_reservation_id ?? 0) > 0;
                    });
                    if ($alreadyPaired) {
                        return false;
                    }

                    return true;
                })
                ->values();

            $html = view('admin.reservations.partials.pairing-modal-body', [
                'reservation' => $reservation,
                'sourceRoom' => $sourceRoom,
                'sourceMode' => $sourceMode,
                'sourceCapacity' => $sourceCapacity,
                'sourceRemaining' => $sourceRemaining,
                'candidates' => $candidates,
                'genderRequirement' => $genderRequirement,
            ])->render();

            return response()->json(['html' => $html, 'count' => $candidates->count()]);
        } catch (\Throwable $e) {
            \Log::error('PairingCandidates exception', [
                'reservation_id' => $reservation->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Erreur serveur lors du chargement des candidats.',
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ], 500);
        }
    }

    /**
     * Jumelage manuel de deux réservations demi-double compatibles.
     */
    public function pairSharedRoom(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403, 'Accès non autorisé à cette réservation.');

        if (! $reservation->needsSharedRoomPairing()) {
            return redirect()->back()->with('error', 'Cette réservation n\'est pas en attente de jumelage demi-double.');
        }

        $targetReservationId = (int) $request->input('target_reservation_id', 0);
        if ($targetReservationId <= 0) {
            return redirect()->back()->with('error', 'Veuillez sélectionner une réservation compatible à jumeler.');
        }

        $targetReservation = Reservation::query()
            ->with('reservationRooms', 'passengers')
            ->find($targetReservationId);

        if (! $targetReservation) {
            return redirect()->back()->with('error', 'La réservation cible n\'existe pas.');
        }

        if (! $targetReservation->needsSharedRoomPairing()) {
            return redirect()->back()->with('error', 'La réservation cible n\'est plus en attente de jumelage.');
        }

        if ((int) $targetReservation->tour_id !== (int) $reservation->tour_id) {
            return redirect()->back()->with('error', 'Les deux réservations doivent concerner le même voyage.');
        }

        if ((int) $targetReservation->departure_id !== (int) $reservation->departure_id) {
            return redirect()->back()->with('error', 'Les deux réservations doivent concerner le même départ.');
        }

        // Identify source room line
        $sourceRoom = $reservation->reservationRooms
            ->first(function ($rr) use ($reservation) {
                $mode = (string) ($rr->room_mode ?? '');
                $state = (string) ($rr->shared_room_status ?? '');
                $paired = (int) ($rr->paired_reservation_id ?? 0);
                $occupied = (int) ($rr->passenger_count ?? 0);
                $capacity = $reservation->resolveRoomCapacity($rr);
                return in_array($mode, ['half_male', 'half_female', 'shared_double'], true)
                    && $state !== 'paired'
                    && $paired <= 0
                    && $occupied > 0
                    && $occupied < $capacity;
            });

        if (! $sourceRoom) {
            return redirect()->back()->with('error', 'Aucune place demi-double en attente sur cette réservation.');
        }

        // Identify target room line
        $targetRoom = $targetReservation->reservationRooms
            ->first(function ($rr) use ($sourceRoom, $targetReservation) {
                $mode = (string) ($rr->room_mode ?? '');
                $state = (string) ($rr->shared_room_status ?? '');
                $paired = (int) ($rr->paired_reservation_id ?? 0);
                $occupied = (int) ($rr->passenger_count ?? 0);
                $capacity = $targetReservation->resolveRoomCapacity($rr);
                return $mode === (string) ($sourceRoom->room_mode ?? '')
                    && $state !== 'paired'
                    && $paired <= 0
                    && $occupied > 0
                    && $occupied < $capacity;
            });

        if (! $targetRoom) {
            return redirect()->back()->with('error', 'La réservation cible n\'a pas de place demi-double compatible.');
        }

        // Gender check
        $genderRequirement = match ((string) ($sourceRoom->room_mode ?? '')) {
            'half_male' => 'male',
            'half_female' => 'female',
            default => null,
        };
        if ($genderRequirement) {
            $hasMatchingGender = $targetReservation->passengers->some(function ($p) use ($genderRequirement) {
                return (string) ($p->gender ?? '') === $genderRequirement;
            });
            if (! $hasMatchingGender) {
                return redirect()->back()->with('error', 'Le sexe du voyageur dans la réservation cible n\'est pas compatible avec ce jumelage.');
            }
        }

        // Capacity check after pairing
        $sourceCap = $reservation->resolveRoomCapacity($sourceRoom);
        $targetCap = $targetReservation->resolveRoomCapacity($targetRoom);
        $capacity = max($sourceCap, $targetCap);
        $totalOccupied = (int) ($sourceRoom->passenger_count ?? 0) + (int) ($targetRoom->passenger_count ?? 0);
        if ($totalOccupied > $capacity) {
            return redirect()->back()->with('error', 'Le jumelage dépasserait la capacité de la chambre ('.$totalOccupied.' / '.$capacity.').');
        }

        // Update reservation statuses
        $reservation->status = Reservation::STATUS_SHARED_ROOM_PAIRED;
        $reservation->save();
        $targetReservation->status = Reservation::STATUS_SHARED_ROOM_PAIRED;
        $targetReservation->save();

        // Update room lines
        $sourceRoom->shared_room_status = 'paired';
        $sourceRoom->paired_reservation_id = $targetReservation->id;
        $sourceRoom->save();

        $targetRoom->shared_room_status = 'paired';
        $targetRoom->paired_reservation_id = $reservation->id;
        $targetRoom->save();

        // Also update allocation status if table exists
        if (Schema::connection('mysql')->hasTable('reservation_room_allocations')) {
            $reservation->roomAllocations()->update(['status' => 'complete']);
            $targetReservation->roomAllocations()->update(['status' => 'complete']);
        }

        return redirect()->back()->with('success', 'Jumelage confirmé avec la réservation #'.$targetReservation->catalog_source_code.' (ID '.$targetReservation->id.').');
    }

    /**
     * Servir le fichier reçu (image/PDF) depuis le stockage EUR" évite le 404 si le symlink storage n'existe pas.
     */
    public function showReceipt(Request $request): StreamedResponse|\Illuminate\Http\Response
    {
        $path = $request->query('path');
        if (! $path || ! is_string($path)) {
            abort(404);
        }
        $path = str_replace('\\', '/', trim($path));
        $validPrefixes = [
            'reservation-receipts/',
            'reservation-visa/',
            'reservation-payments/',
            'reservation-documents/',
        ];
        $valid = ! str_contains($path, '..') && collect($validPrefixes)->contains(fn ($prefix) => str_starts_with($path, $prefix));
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
        $channel = (string) $request->query('channel', '');
        $base = $this->reservationListQuery->baseQuery($user, [
            'tour_id' => $tourFilter > 0 ? $tourFilter : 0,
            'travel_date_id' => $travelDateFilter > 0 ? $travelDateFilter : 0,
            'channel' => $channel !== '' ? $channel : null,
        ]);

        $this->reservationListQuery->applyTourFilter($base, $tourFilter);

        $this->reservationListQuery->applyTravelDateFilter($base, $travelDateFilter > 0 ? $travelDateFilter : null);

        $this->reservationListQuery->applyChannelFilter($base, $channel !== '' ? $channel : null);

        $search = (string) $request->query('search', '');
        $this->reservationListQuery->applyClientSearch($base, $user, $search);

        $statusParam = (string) $request->query('status', '');
        if (! in_array($statusParam, [
            Reservation::STATUS_EN_COURS,
            Reservation::STATUS_SHARED_ROOM_PENDING,
            Reservation::STATUS_SHARED_ROOM_PAIRED,
            Reservation::STATUS_VALIDEE,
            Reservation::STATUS_ANNULEE,
        ], true)) {
            $statusParam = '';
        }
        $this->reservationListQuery->applyStatusFilter($base, $statusParam !== '' ? $statusParam : null);

        // Optional SQL debug for production diagnostics:
        // /admin/reservations?...&sql_debug=1
        // Logs query, bindings, and count to laravel.log (no response change).
        if ($request->query('sql_debug') === '1'
            && $user
            && ($user->is_admin || $user->hasRole(BranchScopeService::ROLE_SUPER_ADMIN) || $user->hasRole(BranchScopeService::ROLE_SIEGE_ADMIN))
        ) {
            try {
                $sql = $base->toSql();
                $bindings = $base->getBindings();
                $count = (clone $base)->count();

                Log::debug('reservations.hub.sql_debug', [
                    'url' => $request->fullUrl(),
                    'filters' => [
                        'voyage_id' => $tourFilter,
                        'travel_date_id' => $travelDateFilter,
                        'channel' => $channel,
                        'status' => $statusParam,
                        'search' => $search,
                    ],
                    'sql' => $sql,
                    'bindings' => $bindings,
                    'count' => $count,
                ]);
            } catch (\Throwable $e) {
                Log::warning('reservations.hub.sql_debug_failed', [
                    'url' => $request->fullUrl(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $base;
    }

    /**
     * Données hub (stats + page courante) : même logique pour la vue HTML et {@see hubRefresh()}.
     *
     * @return array{hubStats: array, reservations: \Illuminate\Contracts\Pagination\LengthAwarePaginator, filterTourId: int|null, filterTravelDateId: int|null, filterSearch: string|null, filterStatus: string|null, filterChannel: string|null, hubTableMode: string, hubVoyageFiltered: bool}
     */
    protected function hubListData(Request $request): array
    {
        $base = $this->hubFilteredReservationBuilder($request);

        $tourFilter = (int) $request->query('voyage_id', 0);
        if ($tourFilter <= 0) {
            $tourFilter = (int) $request->query('tour_id', 0);
        }
        $travelDateFilter = (int) $request->query('travel_date_id', 0);
        $channel = trim((string) $request->query('channel', ''));
        $hubVoyageFiltered = $tourFilter > 0 || $travelDateFilter > 0;
        $hubTableMode = $channel === 'client'
            ? ReservationHubTableProfile::MODE_OPERATIONS
            : $this->reservationHubTableProfile->mode($request->user());
        $search = (string) $request->query('search', '');
        $statusParam = (string) $request->query('status', '');
        if (! in_array($statusParam, [
            Reservation::STATUS_EN_COURS,
            Reservation::STATUS_SHARED_ROOM_PENDING,
            Reservation::STATUS_SHARED_ROOM_PAIRED,
            Reservation::STATUS_VALIDEE,
            Reservation::STATUS_ANNULEE,
        ], true)) {
            $statusParam = '';
        }

        $hubStats = $this->reservationListQuery->aggregateStatusCounts(clone $base);
        $reservationVisibility = $this->reservationVisibility->flagsFor($request->user());

        $reservations = (clone $base)
            ->with(['passengers', 'client', 'offer', 'branch', 'partner', 'travelDate', 'creator', 'createdBy', 'agent', 'salesManager', 'reservationRooms'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $reservations->getCollection()->transform(function (Reservation $reservation) use ($request) {
            return $this->reservationVisibility->sanitizeReservationModel($reservation, $request->user());
        });

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
            'filterChannel' => $channel !== '' ? $channel : null,
            'hubTableMode' => $hubTableMode,
            'hubVoyageFiltered' => $hubVoyageFiltered,
            'reservationVisibility' => $reservationVisibility,
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
                && $this->reservationVisibility->canAccessReservation($request->user(), $res)) {
                $reservationCreated = AdminReservationFlash::createdPayload($res);
            }
        }

        $voyageOptions = AdminWpTourCatalogQuery::reservableVoyageOptions();

        $voyageOptions = $this->normalizeVoyageLabels($voyageOptions)
            ->sortBy(fn (Voyage $voyage) => Str::lower((string) ($voyage->resolved_name ?? $voyage->name)))
            ->values();

        return view('admin.reservations.index', array_merge($data, [
            'voyageOptions' => $voyageOptions,
            'voyage' => $selectedVoyage,
            'highlightReservationId' => $highlightReservationId,
            'reservationCreated' => $reservationCreated,
            'reservationVisibility' => $data['reservationVisibility'],
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
     * o/oov`©nements JSON pour le calendrier : dates de départ (offres) + réservations liées.
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
            $canViewClientContact = $this->reservationVisibility->canViewClientContact($request->user());
            $reservationQuery->where(function (Builder $q) use ($search, $canViewClientContact) {
                $q->where('client_first_name', 'like', '%'.$search.'%')
                    ->orWhere('client_last_name', 'like', '%'.$search.'%')
                    ->orWhereHas('offer', fn (Builder $q2) => $q2->where('name', 'like', '%'.$search.'%'));

                if ($canViewClientContact) {
                    $q->orWhere('client_email', 'like', '%'.$search.'%')
                        ->orWhere('client_phone', 'like', '%'.$search.'%');
                }
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
     * D`©tails JSON d'une réservation (modale calendrier).
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

        $fullAccess = $this->reservationVisibility->canAccessReservation($user, $reservation);
        $visibility = $this->reservationVisibility->flagsFor($user);
        if (! $fullAccess) {
            return response()->json(['error' => 'Réservation introuvable ou accès refusé'], 404);
        }

        $td = $reservation->travelDate;
        $departure = $td?->date?->format('Y-m-d');
        $departureFormatted = $td?->date?->translatedFormat('l j F Y');

        $clientName = trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? ''))
            ?: ($reservation->client?->full_name ?? 'EUR"');

        $payload = [
            'view_mode' => $visibility['limited_presentation'] ? 'limited' : 'full',
            'kind' => 'reservation',
            'id' => $reservation->id,
            'status' => $reservation->status,
            'client' => $clientName,
            'email' => $visibility['view_client_contact'] ? ($reservation->client_email ?: $reservation->client?->email) : null,
            'phone' => $visibility['view_client_contact'] ? ($reservation->client_phone ?: $reservation->client?->phone) : null,
            'tour_name' => $reservation->offer?->name ?? 'EUR"',
            'branch' => $visibility['view_assignment_context'] ? $reservation->branch?->name : null,
            'agency' => $visibility['view_assignment_context'] ? $reservation->agency_label : null,
            'creator_name' => $visibility['view_assignment_context'] ? $reservation->creator?->name : null,
            'departure_date' => $departure,
            'departure_date_formatted' => $departureFormatted,
            'payment_type' => $visibility['view_financial'] ? $reservation->payment_type : null,
            'total_price' => $visibility['view_financial'] ? $reservation->total_price : null,
            'route_edit' => ($request->user()->can('reservations.edit') && ! $visibility['limited_presentation']) ? route('admin.reservations.edit', $reservation) : null,
            'visibility' => $visibility,
        ];

        if ($visibility['limited_presentation']) {
            $payload['branch'] = null;
            $payload['agency'] = null;
        }
            Log::info('URGENT ROOM ENDPOINT RESULT', [
                'mode' => $payload['mode'] ?? null,
                'rooms_count' => count($payload['rooms'] ?? []),
                'rooms' => $payload['rooms'] ?? [],
            ]);


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
        $this->reservationVisibility->applyScope($query, $user);
    }

    /**
     * D`©tails d'un o/oov`©nement calendrier (pour le modal).
     * Priorit`© : travel_date_id (exact) > voyage_id + date > wp_travel_id + date.
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

        // Source de v`©rit`© : donn`©es du voyage = post WordPress + meta (pas le mod`le Laravel Voyage qui peut `tre d`synchronis``)
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

        // Try to find a matching Departure record (by wp_travel_date_id) to include departure_id in reserve link
        $departureForRoute = null;
        if ($voyage) {
            $departureForRoute = Departure::query()->where('voyage_id', $voyage->id)->where('wp_travel_date_id', $travelDate->id)->first();
        }

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
                ? route('admin.reservations.create', array_filter([
                    'tour_id' => $voyage->id,
                    'travel_date_id' => $travelDate->id,
                    'departure_id' => $departureForRoute?->id,
                ]))
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
