<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Client;
use App\Models\TravelDate;
use App\Models\Voyage;
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
     */
    public function calendar(Request $request): View
    {
        $voyages = Voyage::query()
            ->orderBy('name')
            ->get(['id', 'name']);

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
            'voyages' => $voyages,
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
     */
    public function calendarEvents(Request $request): JsonResponse
    {
        $voyageId = (int) $request->query('voyage', 0);
        $destination = $request->query('destination', '');
        $status = $request->query('status', '');
        $budgetMin = $request->has('budget_min') && $request->query('budget_min') !== '' ? (float) $request->query('budget_min') : null;
        $budgetMax = $request->has('budget_max') && $request->query('budget_max') !== '' ? (float) $request->query('budget_max') : null;
        $month = $request->query('month', '');
        $search = trim((string) $request->query('search', ''));

        $voyageIdsWithDates = Voyage::query()
            ->whereNotNull('wp_post_id')
            ->when($voyageId > 0, fn ($q) => $q->where('id', $voyageId))
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

        if ($voyageIdsWithDates->isEmpty()) {
            return response()->json([]);
        }

        $travelDatesQuery = TravelDate::query()
            ->where('is_active', true)
            ->whereIn('travel_id', $voyageIdsWithDates);

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

        $voyages = Voyage::query()
            ->whereIn('wp_post_id', $travelDates->pluck('travel_id')->unique()->filter())
            ->get()
            ->keyBy('wp_post_id');

        $events = [];
        foreach ($travelDates as $travelDate) {
            $voyage = $voyages->get($travelDate->travel_id);
            if (!$voyage) {
                continue;
            }
            $dateStr = $travelDate->date?->format('Y-m-d');
            $events[] = [
                'id' => 'td-' . $travelDate->id,
                'title' => $voyage->name,
                'start' => $dateStr,
                'allDay' => true,
                'extendedProps' => [
                    'voyage_id' => $voyage->id,
                    'wp_travel_id' => $travelDate->travel_id,
                    'travel_date_id' => $travelDate->id,
                    'departure_date' => $dateStr,
                    'destination' => $voyage->destination,
                    'price_from' => $voyage->price_from,
                    'price_override' => $travelDate->price_override,
                    'currency_symbol' => $voyage->currency_symbol,
                    'status' => $voyage->status,
                    'duration_text' => $voyage->duration_text,
                    'route_consulter' => route('admin.circuits.voyages.edit', ['id' => $voyage->id]),
                    'route_reserver' => route('admin.reservations.create', ['tour_id' => $voyage->id]),
                    'route_voir_fiche' => route('admin.circuits.voyages.show', ['id' => $voyage->id]),
                ],
            ];
        }

        return response()->json($events);
    }

    /**
     * Détails d'un événement calendrier (pour le modal).
     */
    public function calendarEventDetails(Request $request): JsonResponse
    {
        $voyageId = (int) $request->query('voyage_id', 0);
        $date = $request->query('date', '');

        if ($voyageId <= 0 || $date === '') {
            return response()->json(['error' => 'Paramètres manquants'], 422);
        }

        $voyage = Voyage::query()->find($voyageId);
        if (!$voyage || !$voyage->wp_post_id) {
            return response()->json(['error' => 'Voyage introuvable'], 404);
        }

        $travelDate = TravelDate::query()
            ->where('travel_id', $voyage->wp_post_id)
            ->where('date', $date)
            ->where('is_active', true)
            ->first();

        if (!$travelDate) {
            return response()->json(['error' => 'Date de départ introuvable'], 404);
        }

        $payload = [
            'voyage_id' => $voyage->id,
            'name' => $voyage->name,
            'slug' => $voyage->slug,
            'destination' => $voyage->destination,
            'departure_date' => $travelDate->date->format('Y-m-d'),
            'departure_date_formatted' => $travelDate->date->translatedFormat('l j F Y'),
            'duration_text' => $voyage->duration_text,
            'price_from' => $voyage->price_from,
            'price_override' => $travelDate->price_override,
            'currency_symbol' => $voyage->currency_symbol,
            'display_price' => $travelDate->price_override ?? $voyage->price_from,
            'status' => $voyage->status,
            'description' => $voyage->description,
            'accroche' => $voyage->accroche,
            'featured_image_url' => $voyage->featured_image_url,
            'min_people' => $voyage->min_people,
            'max_people' => $voyage->max_people,
            'seats' => $travelDate->seats,
            'route_consulter' => route('admin.circuits.voyages.edit', ['id' => $voyage->id]),
            'route_reserver' => route('admin.reservations.create', ['tour_id' => $voyage->id]),
            'route_voir_fiche' => route('admin.circuits.voyages.show', ['id' => $voyage->id]),
        ];

        return response()->json($payload);
    }
}
