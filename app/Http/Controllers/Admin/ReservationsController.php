<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\TravelDate;
use App\Models\Voyage;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

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
            'en-attente'  => 'EN_COURS',
            'confirmees'  => 'VALIDEE',
            'annulees'    => 'ANNULEE',
            default       => null,
        };

        return $this->renderList($request, $status, $submenu);
    }

    /**
     * Formulaire de création de réservation.
     */
    public function create(): View
    {
        $voyages = Voyage::orderByDesc('id')->limit(200)->get(['id', 'name', 'slug']);

        return view('admin.reservations.create', [
            'voyages' => $voyages,
        ]);
    }

    /**
     * Enregistrement minimal d'une réservation (version simplifiée).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tour_id' => 'required|integer',
            'client_first_name' => 'nullable|string|max:100',
            'client_last_name' => 'nullable|string|max:100',
            'payment_type' => 'nullable|in:CASHPLUS,VIREMENT,ESPECE',
            'payment_receipt' => 'nullable|file|max:5120',
            'passengers' => 'nullable|array',
            'passengers.*.first_name' => 'nullable|string|max:100',
            'passengers.*.last_name' => 'nullable|string|max:100',
            'passengers.*.type' => 'nullable|in:adult,child,infant',
            'passengers.*.birth_date' => 'nullable|date',
            'passengers.*.document_type' => 'nullable|string|max:50',
            'passengers.*.document_number' => 'nullable|string|max:100',
        ]);

        $data['client_mode'] = 'new';
        $data['status'] = Reservation::STATUS_EN_COURS;

        if ($request->hasFile('payment_receipt')) {
            $data['payment_receipt'] = $request->file('payment_receipt');
        }

        $this->reservationService->create($data, $request->file('payment_receipt') ?? null);

        return redirect()
            ->route('admin.reservations.index')
            ->with('success', 'Réservation créée.');
    }

    /**
     * Rend la vue de liste des réservations avec éventuellement un filtre de statut.
     */
    protected function renderList(Request $request, ?string $status, ?string $submenu): View
    {
        $query = Reservation::query()->with('passengers');

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

        $selectedVoyageId = (int) $request->query('voyage', 0);

        return view('admin.reservations.calendrier.index', compact('voyages', 'selectedVoyageId'));
    }

    /**
     * Événements JSON pour le calendrier des départs.
     */
    public function calendarEvents(Request $request): JsonResponse
    {
        $voyageId = (int) $request->query('voyage', 0);

        $travelDatesQuery = TravelDate::query()
            ->where('is_active', true);

        $voyageForFilter = null;
        if ($voyageId > 0) {
            $voyageForFilter = Voyage::query()->find($voyageId);
            if (!$voyageForFilter || !$voyageForFilter->wp_post_id) {
                return response()->json([]);
            }
            $travelDatesQuery->where('travel_id', $voyageForFilter->wp_post_id);
        }

        $travelDates = $travelDatesQuery
            ->orderBy('date')
            ->get();

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

            $events[] = [
                'title' => $voyage->name,
                'start' => $travelDate->date?->format('Y-m-d'),
                'allDay' => true,
                'url' => route('admin.circuits.voyages.edit', ['id' => $voyage->id]),
                'extendedProps' => [
                    'voyage_id' => $voyage->id,
                    'wp_travel_id' => $travelDate->travel_id,
                    'destination' => $voyage->destination,
                    'price_from' => $voyage->price_from,
                    'currency_symbol' => $voyage->currency_symbol,
                    'travel_date_id' => $travelDate->id,
                ],
            ];
        }

        return response()->json($events);
    }
}
