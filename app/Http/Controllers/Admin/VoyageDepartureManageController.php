<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\DepartureHotel;
use App\Models\DepartureHotelRoom;
use App\Models\Hotel;
use App\Models\TravelDate;
use App\Models\Voyage;
use App\Services\Booking\DepartureRoomStockService;
use App\Services\DepartureManagementService;
use App\Services\VoyageAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoyageDepartureManageController extends Controller
{
    private VoyageAvailabilityService $voyageAvailabilityService;
    private DepartureManagementService $departureManagementService;

    public function __construct(VoyageAvailabilityService $voyageAvailabilityService, DepartureManagementService $departureManagementService)
    {
        $this->voyageAvailabilityService = $voyageAvailabilityService;
        $this->departureManagementService = $departureManagementService;
    }

    private function assertDepartureBelongsToVoyage(Voyage $voyage, Departure $departure): void
    {
        if ((int) $departure->voyage_id !== (int) $voyage->id) {
            abort(404);
        }
    }

    /**
     * Réponses JSON pour le modal « Disponibilité chambres » (sinon redirection classique).
     */
    private function jsonOrRedirect(Request $request, RedirectResponse $redirect, string $message): JsonResponse|RedirectResponse
    {
        if ($request->boolean('modal_ajax') || $request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return $redirect;
    }

    public function modalDeparturesJson(Voyage $voyage): JsonResponse
    {
        $departures = $this->voyageAvailabilityService->syncFromWpDates($voyage);
        $metrics = $this->departureManagementService->buildDepartureMetrics($departures);

        Log::info('ROOM_STOCK_MODAL_DEPARTURES', [
            'laravel_voyage_id' => (int) $voyage->id,
            'wp_post_id' => (int) ($voyage->wp_post_id ?? 0),
            'found_departures_count' => $metrics->count(),
            'departures_for_selector' => $metrics->map(fn (array $row) => [
                'id' => (int) ($row['id'] ?? 0),
                'start_date' => $row['start_date_iso'] ?? null,
                'status' => $row['status'] ?? null,
            ])->values()->all(),
        ]);

        return response()->json([
            'departures' => $metrics->map(fn (array $row) => [
                'id' => (int) ($row['id'] ?? 0),
                'start_date' => $row['start_date_iso'] ?? null,
                'end_date' => $row['end_date_iso'] ?? null,
                'status' => $row['status'] ?? null,
                'status_label' => $row['status_label'] ?? null,
                'total_capacity' => (int) ($row['total_capacity'] ?? 0),
                'reserved_capacity' => (int) ($row['reserved_capacity'] ?? 0),
                'available_capacity' => (int) ($row['available_capacity'] ?? 0),
                'room_total_places' => (int) ($row['room_total_places'] ?? 0),
                'room_available_places' => (int) ($row['room_available_places'] ?? 0),
                'room_mismatch' => (bool) ($row['room_mismatch'] ?? false),
            ])->values(),
        ]);
    }

    public function syncDepartures(Voyage $voyage): JsonResponse
    {
        $wpPostId = (int) ($voyage->wp_post_id ?? 0);
        $wpDates = $wpPostId > 0
            ? TravelDate::query()->where('travel_id', $wpPostId)->orderBy('date')->orderBy('id')->get()
            : collect();

        $departures = $this->voyageAvailabilityService->syncFromWpDates($voyage);

        Log::info('ROOM_STOCK_MODAL_SYNC', [
            'received_voyage_id' => (int) $voyage->id,
            'received_wp_post_id' => $wpPostId,
            'wp_travel_dates_count' => $wpDates->count(),
            'wp_travel_dates' => $wpDates->map(fn (TravelDate $td) => [
                'id' => (int) $td->id,
                'date' => $td->date ? Carbon::parse($td->date)->format('Y-m-d') : null,
                'is_active' => (bool) $td->is_active,
            ])->values()->all(),
            'laravel_departures_count' => $departures->count(),
            'departures' => $departures->map(fn (Departure $d) => [
                'id' => (int) $d->id,
                'start_date' => $d->start_date ? Carbon::parse($d->start_date)->format('Y-m-d') : null,
                'wp_travel_date_id' => $d->wp_travel_date_id,
                'status' => $d->status,
            ])->values()->all(),
        ]);

        return response()->json([
            'success' => true,
            'wp_dates_count' => $wpDates->count(),
            'departures_count' => $departures->count(),
        ]);
    }

    public function modalDeparturePanel(Voyage $voyage, Departure $departure): View
    {
        $this->voyageAvailabilityService->syncFromWpDates($voyage, [
            'preferred_departure_id' => (int) $departure->id,
        ]);
        $this->assertDepartureBelongsToVoyage($voyage, $departure);

        $departure->load([
            'departureHotels' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            'departureHotels.rooms' => fn ($q) => $q->orderBy('id'),
        ]);

        $hotelsCatalog = Hotel::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'country']);

        return view('admin.circuits.voyages.partials.room_availability.departure_panel', [
            'voyage' => $voyage,
            'departure' => $departure,
            'hotelsCatalog' => $hotelsCatalog,
            'statuses' => Departure::STATUSES,
            'roomStatuses' => DepartureHotelRoom::STATUSES,
        ]);
    }

    public function show(Voyage $voyage, Departure $departure): View
    {
        $this->voyageAvailabilityService->syncFromWpDates($voyage, [
            'preferred_departure_id' => (int) $departure->id,
        ]);
        $this->assertDepartureBelongsToVoyage($voyage, $departure);

        $departure->load([
            'departureHotels' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            'departureHotels.rooms' => fn ($q) => $q->orderBy('id'),
        ]);

        $hotelsCatalog = Hotel::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'city', 'country']);

        return view('admin.circuits.voyages.departures.show', [
            'voyage' => $voyage,
            'departure' => $departure,
            'hotelsCatalog' => $hotelsCatalog,
            'statuses' => Departure::STATUSES,
            'roomStatuses' => DepartureHotelRoom::STATUSES,
        ]);
    }

    public function updateSettings(Request $request, Voyage $voyage, Departure $departure): RedirectResponse|JsonResponse
    {
        $this->assertDepartureBelongsToVoyage($voyage, $departure);

        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|string|in:'.implode(',', Departure::STATUSES),
            'total_capacity' => 'required|integer|min:0',
            'base_price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        $dateExists = Departure::query()
            ->where('voyage_id', (int) $voyage->id)
            ->where('id', '!=', (int) $departure->id)
            ->whereDate('start_date', $data['start_date'])
            ->exists();

        if ($dateExists) {
            $target = trim((string) $request->input('redirect_to', ''));
            $redirect = ($target !== '' && str_starts_with((string) (parse_url($target, PHP_URL_PATH) ?? ''), '/admin/'))
                ? redirect()->to($target)
                : redirect()->route('admin.circuits.voyages.departures.show', [$voyage, $departure]);

            return $this->jsonOrRedirect(
                $request,
                $redirect->withErrors(['start_date' => 'Une autre ligne existe déjà pour cette date de départ.'])->withInput(),
                'Une autre ligne existe déjà pour cette date de départ.'
            );
        }

        $reserved = (int) ($this->departureManagementService->reservedPassengersByDepartureIds([(int) $departure->id])[(int) $departure->id] ?? 0);
        [$status, $available] = $this->departureManagementService->normalizeStatusAndAvailability(
            (string) $data['status'],
            (int) $data['total_capacity'],
            $reserved
        );

        $departure->fill([
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'status' => $status,
            'total_capacity' => (int) $data['total_capacity'],
            'reserved_capacity' => $reserved,
            'available_capacity' => $available,
            'base_price' => $data['base_price'] ?? null,
            'sale_price' => $data['sale_price'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        $departure->save();

        $this->syncWpTravelDateFromDeparture($voyage, $departure);

        $target = trim((string) $request->input('redirect_to', ''));
        $redirect = ($target !== '' && str_starts_with((string) (parse_url($target, PHP_URL_PATH) ?? ''), '/admin/'))
            ? redirect()->to($target)
            : redirect()->route('admin.circuits.voyages.departures.show', [$voyage, $departure]);

        return $this->jsonOrRedirect(
            $request,
            $redirect->with('success', 'Paramètres du départ enregistrés.'),
            'Paramètres du départ enregistrés.'
        );
    }

    private function syncWpTravelDateFromDeparture(Voyage $voyage, Departure $departure): void
    {
        $wpPostId = (int) ($voyage->wp_post_id ?? 0);
        if ($wpPostId <= 0 || ! $departure->start_date) {
            return;
        }

        $startDateIso = Carbon::parse($departure->start_date)->format('Y-m-d');
        $wpTravelDateId = (int) ($departure->wp_travel_date_id ?? 0);

        $existingSameDate = TravelDate::query()
            ->where('travel_id', $wpPostId)
            ->whereDate('date', $startDateIso)
            ->when($wpTravelDateId > 0, fn ($q) => $q->where('id', '!=', $wpTravelDateId))
            ->first();

        if ($existingSameDate && $wpTravelDateId > 0) {
            $departure->wp_travel_date_id = (int) $existingSameDate->id;
            $departure->save();
            $wpTravelDateId = (int) $existingSameDate->id;
        }

        $travelDate = null;
        if ($wpTravelDateId > 0) {
            $travelDate = TravelDate::query()
                ->where('travel_id', $wpPostId)
                ->where('id', $wpTravelDateId)
                ->first();
        }

        if (! $travelDate) {
            $travelDate = $existingSameDate ?: TravelDate::query()
                ->where('travel_id', $wpPostId)
                ->whereDate('date', $startDateIso)
                ->first();
        }

        if (! $travelDate) {
            $travelDate = new TravelDate([
                'travel_id' => $wpPostId,
            ]);
        }

        $isInactive = in_array((string) $departure->status, [Departure::STATUS_CLOSED, Departure::STATUS_CANCELED, Departure::STATUS_CANCELLED], true);

        $travelDate->fill([
            'travel_id' => $wpPostId,
            'date' => $startDateIso,
            'seats' => max(0, (int) ($departure->total_capacity ?? 0)),
            'is_active' => ! $isInactive,
        ]);
        $travelDate->save();

        if ((int) ($departure->wp_travel_date_id ?? 0) !== (int) $travelDate->id) {
            $departure->wp_travel_date_id = (int) $travelDate->id;
            $departure->save();
        }
    }

    public function storeHotel(Request $request, Voyage $voyage, Departure $departure): RedirectResponse|JsonResponse
    {
        $this->assertDepartureBelongsToVoyage($voyage, $departure);

        $data = $request->validate([
            'hotel_id' => 'nullable|integer|exists:hotels,id',
            'hotel_name' => 'required_without:hotel_id|nullable|string|max:255',
            'stars' => 'nullable|integer|min:0|max:5',
            'address' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        $maxSort = (int) DepartureHotel::where('departure_id', $departure->id)->max('sort_order');

        $hotel = DepartureHotel::create([
            'departure_id' => $departure->id,
            'hotel_id' => $data['hotel_id'] ?? null,
            'hotel_name' => $data['hotel_name'] ?? null,
            'stars' => $data['stars'] ?? null,
            'address' => $data['address'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $maxSort + 1,
            'notes' => $data['notes'] ?? null,
        ]);

        if ($hotel->hotel_id && ! $hotel->hotel_name) {
            $h = Hotel::find($hotel->hotel_id);
            if ($h) {
                $hotel->update(['hotel_name' => $h->name]);
            }
        }

        return $this->jsonOrRedirect(
            $request,
            redirect()
                ->route('admin.circuits.voyages.departures.show', [$voyage, $departure])
                ->with('success', 'Hôtel ajouté au départ.')
                ->withFragment('hotels'),
            'Hôtel ajouté au départ.'
        );
    }

    public function updateHotel(Request $request, Voyage $voyage, DepartureHotel $departureHotel): RedirectResponse|JsonResponse
    {
        $departure = $departureHotel->departure;
        $this->assertDepartureBelongsToVoyage($voyage, $departure);

        $data = $request->validate([
            'hotel_id' => 'nullable|integer|exists:hotels,id',
            'hotel_name' => 'nullable|string|max:255',
            'stars' => 'nullable|integer|min:0|max:5',
            'address' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        $departureHotel->fill([
            'hotel_id' => $data['hotel_id'] ?? $departureHotel->hotel_id,
            'hotel_name' => $data['hotel_name'] ?? $departureHotel->hotel_name,
            'stars' => $data['stars'] ?? $departureHotel->stars,
            'address' => $data['address'] ?? $departureHotel->address,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $departureHotel->is_active,
            'sort_order' => $data['sort_order'] ?? $departureHotel->sort_order,
            'notes' => $data['notes'] ?? $departureHotel->notes,
        ]);
        $departureHotel->save();

        return $this->jsonOrRedirect(
            $request,
            redirect()
                ->route('admin.circuits.voyages.departures.show', [$voyage, $departure])
                ->with('success', 'Hôtel mis à jour.')
                ->withFragment('hotels'),
            'Hôtel mis à jour.'
        );
    }

    public function destroyHotel(Request $request, Voyage $voyage, DepartureHotel $departureHotel): RedirectResponse|JsonResponse
    {
        $departure = $departureHotel->departure;
        $this->assertDepartureBelongsToVoyage($voyage, $departure);

        $departureHotel->delete();

        $departure->recomputeAvailableCapacity(true);

        return $this->jsonOrRedirect(
            $request,
            redirect()
                ->route('admin.circuits.voyages.departures.show', [$voyage, $departure])
                ->with('success', 'Hôtel retiré du départ.')
                ->withFragment('hotels'),
            'Hôtel retiré du départ.'
        );
    }

    public function storeRoom(Request $request, Voyage $voyage, DepartureHotel $departureHotel, DepartureRoomStockService $stockService): RedirectResponse|JsonResponse
    {
        $departure = $departureHotel->departure;
        $this->assertDepartureBelongsToVoyage($voyage, $departure);

        $data = $request->validate([
            'room_type' => 'required|string|max:120',
            'capacity_total' => 'required|integer|min:1',
            'total_rooms' => 'required|integer|min:0',
            'available_places' => 'nullable|integer|min:0',
            'supplement' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:'.implode(',', DepartureHotelRoom::STATUSES),
        ]);

        $cap = (int) $data['capacity_total'];
        $totalRooms = (int) $data['total_rooms'];
        $syncPlaces = ! $request->boolean('manual_places');
        if (! $syncPlaces) {
            $request->validate(['available_places' => 'required|integer|min:0']);
        }
        $totalPlaces = $syncPlaces
            ? $totalRooms * $cap
            : max(0, (int) ($data['available_places'] ?? 0));

        DepartureHotelRoom::create([
            'departure_hotel_id' => $departureHotel->id,
            'room_id' => null,
            'room_type' => $data['room_type'],
            'capacity_total' => $cap,
            'total_rooms' => $totalRooms,
            'reserved_rooms' => 0,
            'total_places' => $totalPlaces,
            'reserved_places' => 0,
            'available_rooms' => $totalRooms,
            'available_places' => $totalPlaces,
            'supplement' => $data['supplement'] ?? 0,
            'status' => $data['status'],
        ]);

        $stockService->refreshDerivedForEntireDeparture((int) $departure->id);

        return $this->jsonOrRedirect(
            $request,
            redirect()
                ->route('admin.circuits.voyages.departures.show', [$voyage, $departure])
                ->with('success', 'Type de chambre ajouté.')
                ->withFragment('rooms-'.$departureHotel->id),
            'Type de chambre ajouté.'
        );
    }

    public function updateRoom(Request $request, Voyage $voyage, DepartureHotelRoom $departureHotelRoom, DepartureRoomStockService $stockService): RedirectResponse|JsonResponse
    {
        $departureHotel = $departureHotelRoom->departureHotel;
        $departure = $departureHotel->departure;
        $this->assertDepartureBelongsToVoyage($voyage, $departure);

        $data = $request->validate([
            'room_type' => 'required|string|max:120',
            'capacity_total' => 'required|integer|min:1',
            'total_rooms' => 'required|integer|min:0',
            'available_places' => 'nullable|integer|min:0',
            'supplement' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:'.implode(',', DepartureHotelRoom::STATUSES),
        ]);

        $cap = (int) $data['capacity_total'];
        $totalRooms = (int) $data['total_rooms'];
        $syncPlaces = ! $request->boolean('manual_places');
        if (! $syncPlaces) {
            $request->validate(['available_places' => 'required|integer|min:0']);
        }
        $totalPlaces = $syncPlaces
            ? $totalRooms * $cap
            : max(0, (int) ($data['available_places'] ?? 0));

        $reservedRooms = (int) $departureHotelRoom->reserved_rooms;
        $reservedPlaces = (int) $departureHotelRoom->reserved_places;

        $departureHotelRoom->fill([
            'room_type' => $data['room_type'],
            'capacity_total' => $cap,
            'total_rooms' => $totalRooms,
            'reserved_rooms' => min($reservedRooms, $totalRooms),
            'total_places' => $totalPlaces,
            'reserved_places' => min($reservedPlaces, $totalPlaces),
            'supplement' => $data['supplement'] ?? 0,
            'status' => $data['status'],
        ]);
        $departureHotelRoom->save();

        $stockService->refreshDerivedForEntireDeparture((int) $departure->id);

        return $this->jsonOrRedirect(
            $request,
            redirect()
                ->route('admin.circuits.voyages.departures.show', [$voyage, $departure])
                ->with('success', 'Stock chambre mis à jour.')
                ->withFragment('rooms-'.$departureHotel->id),
            'Stock chambre mis à jour.'
        );
    }

    public function destroyRoom(Request $request, Voyage $voyage, DepartureHotelRoom $departureHotelRoom, DepartureRoomStockService $stockService): RedirectResponse|JsonResponse
    {
        $departureHotel = $departureHotelRoom->departureHotel;
        $departure = $departureHotel->departure;
        $this->assertDepartureBelongsToVoyage($voyage, $departure);

        $hid = $departureHotel->id;
        $depId = (int) $departure->id;
        $departureHotelRoom->delete();
        $stockService->refreshDerivedForEntireDeparture($depId);

        return $this->jsonOrRedirect(
            $request,
            redirect()
                ->route('admin.circuits.voyages.departures.show', [$voyage, $departure])
                ->with('success', 'Ligne chambre supprimée.')
                ->withFragment('rooms-'.$hid),
            'Ligne chambre supprimée.'
        );
    }
}
