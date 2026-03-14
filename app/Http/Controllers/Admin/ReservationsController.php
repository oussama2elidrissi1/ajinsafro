<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Client;
use App\Models\TravelDate;
use App\Models\Voyage;
use App\Models\Wp\WpPost;
use App\Services\ReservationService;
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
     */
    public function create(): View
    {
        $voyages = Voyage::orderByDesc('id')->limit(200)->get(['id', 'name', 'slug']);
        $clients = Client::orderByDesc('id')->limit(200)->get(['id', 'client_code', 'full_name', 'email', 'phone']);

        return view('admin.reservations.create', [
            'voyages' => $voyages,
            'clients' => $clients,
        ]);
    }

    /**
     * Enregistrement minimal d'une réservation (version simplifiée).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tour_id' => 'required|integer',
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

        $data['status'] = Reservation::STATUS_EN_COURS;
        $data['visa_ok'] = $request->boolean('visa_ok');

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
     * Formulaire d'édition d'une réservation.
     */
    public function edit(Reservation $reservation): View
    {
        $reservation->load(['passengers', 'client', 'tour']);
        $voyages = Voyage::orderByDesc('id')->limit(200)->get(['id', 'name', 'slug']);
        $clients = Client::orderByDesc('id')->limit(200)->get(['id', 'client_code', 'full_name', 'email', 'phone']);

        return view('admin.reservations.edit', [
            'reservation' => $reservation,
            'voyages' => $voyages,
            'clients' => $clients,
        ]);
    }

    /**
     * Mise à jour d'une réservation.
     */
    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validate([
            'tour_id' => 'required|integer',
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

        $data['visa_ok'] = $request->boolean('visa_ok');

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
        $query = Reservation::query()->with(['passengers', 'client', 'tour']);

        if ($status) {
            $query->where('status', $status);
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
        ]);
    }

    /**
     * Événements JSON pour le calendrier des départs (avec filtres).
     * Le paramètre "voyage" est l'ID du tour WordPress (comme dans le filtre), pour filtrer par travel_id.
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

        // 1) Déterminer les tours WP à considérer (source de vérité: wp_posts.ID == TravelDate.travel_id)
        // Si un voyage est sélectionné, on filtre directement par cet ID WordPress.
        if ($voyageFilter > 0) {
            $wpTourIds = collect([$voyageFilter]);
        } else {
            // Sinon, on filtre via la table Laravel voyages (métadonnées), mais on retourne toujours des wp_post_id
            $wpTourIds = Voyage::query()
                ->whereNotNull('wp_post_id')
                ->when($destination !== '', fn ($q) => $q->where('destination', $destination))
                ->when($status !== '', fn ($q) => $q->where('status', $status))
                ->when($budgetMin !== null, fn ($q) => $q->where('price_from', '>=', $budgetMin))
                ->when($budgetMax !== null, fn ($q) => $q->where('price_from', '<=', $budgetMax))
                ->when($search !== '', fn ($q) => $q->where(function ($q2) use ($search) {
                    // NB: on cherche sur les champs Laravel (si non synchronisés, filtre partiel — OK)
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

        $travelDatesQuery = TravelDate::query()
            ->where('is_active', true)
            ->whereIn('travel_id', $wpTourIds);

        if ($month !== '') {
            if (preg_match('/^(\d{4})-(\d{2})$/', $month, $m)) {
                $travelDatesQuery->whereYear('date', (int) $m[1])
                    ->whereMonth('date', (int) $m[2]);
            }
        }

        $travelDates = $travelDatesQuery->orderBy('date')->get();
        if ($travelDates->isEmpty()) {
            return response()->json([]);
        }

        $travelIds = $travelDates->pluck('travel_id')->unique()->filter()->map(fn ($id) => (int) $id)->values();

        // 2) Charger les tours WordPress (titres/slug) pour éviter tout mismatch avec voyages.name (ex: "Brouillon auto")
        $wpPosts = collect();
        try {
            $wpPosts = WpPost::query()
                ->whereIn('ID', $travelIds)
                ->get(['ID', 'post_title', 'post_name', 'post_excerpt', 'post_content'])
                ->keyBy('ID');
        } catch (\Throwable $e) {
            // Si WP indisponible ici, on gardera un fallback au niveau du titre.
        }

        // 3) Charger les voyages Laravel (métadonnées) quand ils existent (destination, price_from, etc.)
        $voyages = Voyage::query()
            ->whereIn('wp_post_id', $travelIds)
            ->get()
            ->keyBy('wp_post_id');

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
                    'voyage_id' => $voyage?->id, // Laravel id (si existe) – utile pour la réservation
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
                    'route_reserver' => $voyage ? route('admin.reservations.create', ['tour_id' => $voyage->id]) : route('admin.reservations.create'),
                    'route_voir_fiche' => route('admin.circuits.voyages.show', ['id' => $wpId]),
                ],
            ];
        }

        return response()->json($events);
    }

    /**
     * Détails d'un événement calendrier (pour le modal).
     * Accepte voyage_id (Laravel) ou wp_travel_id (ID tour WordPress) + date.
     */
    public function calendarEventDetails(Request $request): JsonResponse
    {
        $voyageId = (int) $request->query('voyage_id', 0);
        $wpTravelId = (int) $request->query('wp_travel_id', 0);
        $date = $request->query('date', '');

        if ($date === '') {
            return response()->json(['error' => 'Paramètre date manquant'], 422);
        }

        $wpId = null;
        $voyage = null;
        if ($voyageId > 0) {
            $voyage = Voyage::query()->find($voyageId);
            if ($voyage && $voyage->wp_post_id) {
                $wpId = $voyage->wp_post_id;
            }
        }
        if ($wpId === null && $wpTravelId > 0) {
            $wpId = $wpTravelId;
            $voyage = Voyage::query()->where('wp_post_id', $wpTravelId)->first();
        }
        if ($wpId === null) {
            return response()->json(['error' => 'Voyage introuvable'], 404);
        }

        $travelDate = TravelDate::query()
            ->where('travel_id', $wpId)
            ->where('date', $date)
            ->where('is_active', true)
            ->first();

        if (!$travelDate) {
            return response()->json(['error' => 'Date de départ introuvable'], 404);
        }

        // Charger le WP Post (titre/slug/contenu) pour garantir cohérence avec le filtre Voyages.
        $wpPost = null;
        try {
            $wpPost = WpPost::query()->where('ID', $wpId)->first();
        } catch (\Throwable $e) {
            // ignore
        }

        if ($voyage) {
            $payload = [
                'voyage_id' => $voyage->id,
                'name' => $wpPost?->post_title ?? $voyage->name,
                'slug' => $wpPost?->post_name ?? $voyage->slug,
                'destination' => $voyage->destination,
                'departure_date' => $travelDate->date->format('Y-m-d'),
                'departure_date_formatted' => $travelDate->date->translatedFormat('l j F Y'),
                'duration_text' => $voyage->duration_text,
                'price_from' => $voyage->price_from,
                'price_override' => $travelDate->price_override,
                'currency_symbol' => $voyage->currency_symbol,
                'display_price' => $travelDate->price_override ?? $voyage->price_from,
                'status' => $voyage->status,
                'description' => $wpPost?->post_content ?? $voyage->description,
                'accroche' => $wpPost?->post_excerpt ?? $voyage->accroche,
                'featured_image_url' => $voyage->featured_image_url,
                'min_people' => $voyage->min_people,
                'max_people' => $voyage->max_people,
                'seats' => $travelDate->seats,
                'route_consulter' => route('admin.circuits.voyages.edit', ['id' => $wpId]),
                'route_reserver' => route('admin.reservations.create', ['tour_id' => $voyage->id]),
                'route_voir_fiche' => route('admin.circuits.voyages.show', ['id' => $wpId]),
            ];
        } else {
            $payload = [
                'voyage_id' => null,
                'name' => $wpPost ? $wpPost->post_title : 'Tour #' . $wpId,
                'slug' => $wpPost ? $wpPost->post_name : '',
                'destination' => null,
                'departure_date' => $travelDate->date->format('Y-m-d'),
                'departure_date_formatted' => $travelDate->date->translatedFormat('l j F Y'),
                'duration_text' => null,
                'price_from' => null,
                'price_override' => $travelDate->price_override,
                'currency_symbol' => 'DH',
                'display_price' => $travelDate->price_override,
                'status' => null,
                'description' => $wpPost ? $wpPost->post_content : null,
                'accroche' => $wpPost ? $wpPost->post_excerpt : null,
                'featured_image_url' => null,
                'min_people' => null,
                'max_people' => null,
                'seats' => $travelDate->seats,
                'route_consulter' => route('admin.circuits.voyages.edit', ['id' => $wpId]),
                'route_reserver' => route('admin.reservations.create'),
                'route_voir_fiche' => route('admin.circuits.voyages.show', ['id' => $wpId]),
            ];
        }

        return response()->json($payload);
    }
}
