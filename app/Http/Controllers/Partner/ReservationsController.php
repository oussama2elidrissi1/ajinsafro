<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\TravelDate;
use App\Models\Voyage;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ReservationsController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService
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
        $query = $this->scopeReservations($request)->with(['tour:id,name']);
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        $reservations = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        return view('partner.reservations.index', compact('reservations'));
    }

    public function create(Request $request): View
    {
        $partner = $this->getPartner($request);
        $clients = collect();
        if (Schema::hasColumn('clients', 'partner_id')) {
            $clients = Client::where('partner_id', $partner->id)->orderBy('full_name')->get(['id', 'client_code', 'full_name', 'email', 'phone']);
        }
        $voyages = Voyage::orderBy('name')->get(['id', 'name']);
        $travelDates = collect();
        try {
            $travelDates = TravelDate::where('is_active', true)->orderBy('date')->get();
        } catch (\Throwable $e) {
            report($e);
        }
        return view('partner.reservations.create', compact('clients', 'voyages', 'travelDates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $partner = $this->getPartner($request);
        $data = $request->validate([
            'tour_id' => ['required', 'exists:voyages,id'],
            'travel_date_id' => ['nullable', 'exists:travel_dates,id'],
            'client_mode' => ['in:existing,new'],
            'client_external_id' => ['nullable', 'exists:clients,id'],
            'client_first_name' => ['required_without:client_external_id', 'nullable', 'string', 'max:100'],
            'client_last_name' => ['required_without:client_external_id', 'nullable', 'string', 'max:100'],
            'client_email' => ['nullable', 'email', 'max:190'],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'payment_type' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'passengers_count' => ['nullable', 'integer', 'min:1'],
        ]);
        $data['partner_id'] = $partner->id;
        $data['client_mode'] = $data['client_mode'] ?? ($data['client_external_id'] ? 'existing' : 'new');
        $data['passengers'] = [];
        $data['hotel_rooms'] = [];
        if (!empty($data['client_external_id'])) {
            $client = Client::where('partner_id', $partner->id)->findOrFail($data['client_external_id']);
            $data['client_first_name'] = $client->first_name;
            $data['client_last_name'] = $client->last_name;
            $data['client_email'] = $client->email;
            $data['client_phone'] = $client->phone;
        }
        $this->reservationService->create($data);
        return redirect()->route('partner.reservations.index')->with('success', 'Réservation créée.');
    }

    public function show(Request $request, Reservation $reservation): View|RedirectResponse
    {
        $partner = $this->getPartner($request);
        if ($reservation->partner_id !== $partner->id) {
            abort(403);
        }
        $reservation->load(['tour', 'partner']);
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
        $reservation->load(['tour', 'passengers', 'reservationRooms']);
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
            'status' => ['in:EN_COURS,VALIDEE,ANNULEE'],
        ]);
        $data['partner_id'] = $partner->id;
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
