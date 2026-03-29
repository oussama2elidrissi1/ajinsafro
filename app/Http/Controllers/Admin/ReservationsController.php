<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\User;
use App\Models\ReservationRoom;
use App\Models\Client;
use App\Models\TravelDate;
use App\Models\Voyage;
use App\Models\TourHotel;
use App\Models\TourHotelRoom;
use App\Models\Wp\WpPost;
use App\Services\BranchScopeService;
use App\Services\ReservationService;
use App\Services\Wp\WpHeroImageService;
use App\Services\Wp\WpTourRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ReservationsController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService,
        protected WpTourRepository $wpTourRepository,
        protected BranchScopeService $branchScope,
    ) {
    }

    /**
     * Liste principale des réservations (toutes).
     */
    public function index(Request $request): View
    {
        return $this->renderList($request, null, null);
    }

    /**
     * Entrées de sous-menu (en-attente, confirmées, etc.) réutilisent la même vue de liste
     * avec un filtre de statut basé sur le slug du sous-menu.
     */
    public function page(Request $request): View
    {
        $submenu = $request->route()->parameter('submenu');

        $status = match ($submenu) {
            'en-attente' => 'EN_COURS',
            'confirmees' => 'VALIDEE',
            'annulees' => 'ANNULEE',
            default => null,
        };

        return $this->renderList($request, $status, $submenu);
    }

    /**
     * Formulaire de création de réservation.
     * Préremplissage depuis le calendrier : tour_id, travel_date_id (optionnel).
     * Le voyage affiché vient du tour_id (Voyage Laravel). Les libellés viennent de WordPress quand disponible.
     */
    public function create(Request $request): View
    {
        $requestedTourId = (int) $request->query('tour_id', 0);
        $travelDateId = (int) $request->query('travel_date_id', 0);

        $clientsQuery = Client::query()->orderByDesc('id')->limit(200);
        $this->branchScope->scopeClients($clientsQuery, $request->user());
        $clients = $clientsQuery->get(['id', 'client_code', 'full_name', 'email', 'phone']);
        $voyages = Voyage::orderByDesc('id')->limit(200)->get(['id', 'name', 'slug', 'wp_post_id']);
        if ($requestedTourId > 0 && $voyages->where('id', $requestedTourId)->isEmpty()) {
            $requestedVoyage = Voyage::find($requestedTourId);
            if ($requestedVoyage) {
                $voyages = $voyages->prepend($requestedVoyage)->unique('id')->values();
            }
        }

        $wpPostIds = $voyages->pluck('wp_post_id')->filter()->unique()->values()->all();
        $wpTitles = collect();
        if (!empty($wpPostIds)) {
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
                if (!$voyageForTour || (int) $selectedTravelDate->travel_id !== (int) $voyageForTour->wp_post_id) {
                    $selectedTravelDate = null;
                    $travelDateIncoherent = true;
                }
            }
        }

        $preselectedTourId = null;
        if ($requestedTourId > 0 && $voyages->contains('id', $requestedTourId)) {
            $preselectedTourId = $requestedTourId;
        }

        return view('admin.reservations.create', [
            'voyages' => $voyages,
            'wpTitles' => $wpTitles,
            'clients' => $clients,
            'selectedTravelDate' => $selectedTravelDate,
            'travelDateId' => $travelDateId > 0 ? $travelDateId : null,
            'preselectedTourId' => $preselectedTourId,
            'travelDateIncoherent' => $travelDateIncoherent,
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
        if (!$voyage || !$voyage->wp_post_id) {
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
     * Enregistrement d'une réservation (avec chambres et validation capacité).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tour_id' => 'required|integer',
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
            'hotel_rooms.*.tour_hotel_id' => 'required_with:hotel_rooms.*|nullable|integer',
            'hotel_rooms.*.tour_hotel_room_id' => 'required_with:hotel_rooms.*|nullable|integer',
            'hotel_rooms.*.room_count' => 'required_with:hotel_rooms.*|nullable|integer|min:0',
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

        $totalTravelers = $this->computeTotalTravelers($request->input('passengers', []));
        $this->validateRoomCapacity(
            (int) $request->input('travel_date_id', 0),
            (int) $request->input('tour_id', 0),
            $totalTravelers
        );

        $data['status'] = Reservation::STATUS_EN_COURS;
        $data['visa_ok'] = $request->boolean('visa_ok');
        $user = $request->user();
        $data['branch_id'] = $user->branch_id;
        $data['agent_id'] = $user->id;
        $data['sales_manager_id'] = $user->branch?->manager_user_id;
        $data['created_by'] = $user->id;

        $this->reservationService->create(
            $data,
            $request->file('payment_receipt'),
            $request->file('visa_document')
        );

        return redirect()
            ->route('admin.reservations.index')
            ->with('success', 'Réservation créée.');
    }

    /**
     * Fiche réservation : redirige vers l’édition (même périmètre d’accès que {@see edit}).
     * La route est utilisée après création (workspace), liens « Ouvrir », etc.
     */
    public function show(Request $request, Reservation $reservation): RedirectResponse
    {
        $branchIds = $this->branchScope->visibleBranchIds($request->user());
        if ($branchIds !== null && ! in_array($reservation->branch_id, $branchIds, true)) {
            abort(403, 'Accès non autorisé à cette réservation.');
        }

        return redirect()->route('admin.reservations.edit', $reservation);
    }

    /**
     * Formulaire d'édition d'une réservation.
     */
    public function edit(Request $request, Reservation $reservation): View
    {
        $branchIds = $this->branchScope->visibleBranchIds($request->user());
        if ($branchIds !== null && ! in_array($reservation->branch_id, $branchIds, true)) {
            abort(403, 'Accès non autorisé à cette réservation.');
        }
        $reservation->load(['passengers', 'client', 'tour', 'reservationRooms']);
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
        ]);
    }

    /**
     * Mise à jour d'une réservation (avec chambres et validation capacité).
     */
    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        $branchIds = $this->branchScope->visibleBranchIds($request->user());
        if ($branchIds !== null && ! in_array($reservation->branch_id, $branchIds, true)) {
            abort(403, 'Accès non autorisé à cette réservation.');
        }
        $data = $request->validate([
            'tour_id' => 'required|integer',
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
            'hotel_rooms.*.tour_hotel_id' => 'required_with:hotel_rooms.*|nullable|integer',
            'hotel_rooms.*.tour_hotel_room_id' => 'required_with:hotel_rooms.*|nullable|integer',
            'hotel_rooms.*.room_count' => 'required_with:hotel_rooms.*|nullable|integer|min:0',
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

        $totalTravelers = $this->computeTotalTravelers($request->input('passengers', []));
        $this->validateRoomCapacity(
            (int) $request->input('travel_date_id', 0),
            (int) $request->input('tour_id', 0),
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
            if (!is_array($p)) {
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
     * Vérifie que la capacité disponible sur la date de départ couvre le nombre de voyageurs.
     * La capacité vient des chambres configurées dans l’hôtel du voyage + l’occupation (stock réel).
     */
    private function validateRoomCapacity(int $travelDateId, int $tourId, int $totalTravelers): void
    {
        if ($totalTravelers <= 0 || $travelDateId <= 0 || $tourId <= 0) {
            // Si pas de date de départ, on ne peut pas gérer le stock par départ.
            return;
        }

        $voyage = \App\Models\Voyage::query()->find($tourId);
        if (!$voyage || !$voyage->wp_post_id) {
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
            // Si la table d’occupation n’existe pas encore, on ne bloque pas ici (le service plantera si nécessaire).
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
    public function destroy(Reservation $reservation): RedirectResponse
    {
        $this->reservationService->delete($reservation);

        return redirect()
            ->route('admin.reservations.index')
            ->with('success', 'Réservation supprimée.');
    }

    /**
     * Valider une réservation (passer en VALIDEE).
     */
    public function validateReservation(Reservation $reservation): RedirectResponse
    {
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
        if (!$path || !is_string($path)) {
            abort(404);
        }
        $path = str_replace('\\', '/', trim($path));
        $valid = !str_contains($path, '..') && (str_starts_with($path, 'reservation-receipts/') || str_starts_with($path, 'reservation-visa/'));
        if (!$valid) {
            abort(404);
        }
        if (!Storage::disk('public')->exists($path)) {
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
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    /**
     * Rend la vue de liste des réservations avec éventuellement un filtre de statut.
     */
    protected function renderList(Request $request, ?string $status, ?string $submenu): View
    {
        $query = Reservation::query()->with(['passengers', 'client', 'tour', 'branch']);
        $this->branchScope->scopeReservations($query, $request->user());

        if ($status) {
            $query->where('status', $status);
        }

        $tourFilter = (int) $request->query('voyage_id', 0);
        if ($tourFilter <= 0) {
            $tourFilter = (int) $request->query('tour_id', 0);
        }
        if ($tourFilter > 0) {
            $query->where('tour_id', $tourFilter);
        }

        $reservations = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'status' => $status,
            'submenu' => $submenu,
        ]);
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
                    $q2->where('name', 'like', '%' . $search . '%')
                        ->orWhere('destination', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
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
            $title = $wpPost ? ($wpPost->post_title ?? '') : ($voyage?->name ?? ('Tour #' . $travelDate->travel_id));
            $dateStr = $travelDate->date?->format('Y-m-d');
            $wpId = $travelDate->travel_id;
            $events[] = [
                'id' => 'td-' . $travelDate->id,
                'title' => $title !== '' ? $title : ('Tour #' . $wpId),
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
            ->with(['tour:id,name,wp_post_id'])
            ->whereNotNull('travel_date_id')
            ->whereHas('travelDate', function (Builder $q) use ($wpTourIds, $applyTravelDateWindow) {
                $q->where('is_active', true)->whereIn('travel_id', $wpTourIds);
                $applyTravelDateWindow($q);
            });

        $this->scopeReservationAccessForCalendar($reservationQuery, $request->user());

        if ($search !== '') {
            $reservationQuery->where(function (Builder $q) use ($search) {
                $q->where('client_first_name', 'like', '%' . $search . '%')
                    ->orWhere('client_last_name', 'like', '%' . $search . '%')
                    ->orWhere('client_email', 'like', '%' . $search . '%')
                    ->orWhere('client_phone', 'like', '%' . $search . '%')
                    ->orWhereHas('tour', fn (Builder $q2) => $q2->where('name', 'like', '%' . $search . '%'));
            });
        }

        foreach ($reservationQuery->orderBy('id')->get() as $reservation) {
            $td = $reservation->travelDate;
            if (! $td || ! $td->date) {
                continue;
            }
            $dateStr = $td->date->format('Y-m-d');
            $client = trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? ''));
            $tourName = $reservation->tour?->name ?? 'Voyage';
            $chip = '#' . $reservation->id . ' · ' . ($client !== '' ? $client : 'Client');
            $events[] = [
                'id' => 'res-' . $reservation->id,
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

        $query = Reservation::query()
            ->with(['tour:id,name,wp_post_id', 'travelDate', 'client:id,full_name,email,phone', 'branch:id,name'])
            ->whereKey($id);

        $this->scopeReservationAccessForCalendar($query, $request->user());

        $reservation = $query->first();
        if (! $reservation) {
            return response()->json(['error' => 'Réservation introuvable ou accès refusé'], 404);
        }

        $td = $reservation->travelDate;
        $departure = $td?->date?->format('Y-m-d');
        $departureFormatted = $td?->date?->translatedFormat('l j F Y');

        return response()->json([
            'kind' => 'reservation',
            'id' => $reservation->id,
            'status' => $reservation->status,
            'client' => trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? ''))
                ?: ($reservation->client?->full_name ?? '—'),
            'email' => $reservation->client_email ?: $reservation->client?->email,
            'phone' => $reservation->client_phone ?: $reservation->client?->phone,
            'tour_name' => $reservation->tour?->name ?? '—',
            'branch' => $reservation->branch?->name,
            'departure_date' => $departure,
            'departure_date_formatted' => $departureFormatted,
            'payment_type' => $reservation->payment_type,
            'total_price' => $reservation->total_price,
            'route_edit' => route('admin.reservations.edit', $reservation),
        ]);
    }

    /**
     * Réservations : même périmètre agence + pour commercial / chef / agent : dossiers liés au compte.
     */
    private function scopeReservationAccessForCalendar(Builder $query, User $user): void
    {
        $this->branchScope->scopeReservations($query, $user);
        if ($user->isCommercial() || $user->isChefCommercial() || $user->isAgent()) {
            $uid = $user->id;
            $query->where(function (Builder $q) use ($uid) {
                $q->where('agent_id', $uid)
                    ->orWhere('sales_manager_id', $uid)
                    ->orWhere('created_by', $uid);
            });
        }
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

        if (!$travelDate && $date !== '') {
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

        if (!$travelDate) {
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
            $durationText = $durationDays >= 1 ? $durationDays . ' jour' . ($durationDays > 1 ? 's' : '') . ($durationDays >= 2 ? ' / ' . ($durationDays - 1) . ' nuit' . (($durationDays - 1) > 1 ? 's' : '') : '') : null;

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
            'name' => $wpPost?->post_title ?? ('Tour #' . $wpId),
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
