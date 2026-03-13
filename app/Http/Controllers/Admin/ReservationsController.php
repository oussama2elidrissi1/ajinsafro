<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Voyage;
use App\Services\ReservationService;
use Illuminate\Http\Request;
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
}
