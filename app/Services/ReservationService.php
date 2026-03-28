<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationPassenger;
use App\Models\ReservationRoom;
use App\Models\TourHotel;
use App\Models\TourHotelRoom;
use App\Services\PartnerCommissionService;
use App\Services\WordPressMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReservationService
{
    public function __construct(
        private readonly WordPressMediaService $mediaService,
        private readonly PartnerCommissionService $commissionService,
    ) {
    }

    /**
     * Liste paginée des réservations avec filtres simples.
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Reservation::query()->withCount('passengers');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_type'])) {
            $query->where('payment_type', $filters['payment_type']);
        }

        if (!empty($filters['client'])) {
            $q = trim((string) $filters['client']);
            $query->where(function ($sub) use ($q) {
                $sub->where('client_first_name', 'like', '%' . $q . '%')
                    ->orWhere('client_last_name', 'like', '%' . $q . '%')
                    ->orWhere('client_email', 'like', '%' . $q . '%')
                    ->orWhere('client_phone', 'like', '%' . $q . '%');
            });
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Création d'une réservation (transactionnelle).
     */
    public function create(array $data, ?UploadedFile $paymentReceipt = null, ?UploadedFile $visaDocument = null): Reservation
    {
        return DB::transaction(function () use ($data, $paymentReceipt, $visaDocument) {
            $reservation = new Reservation();
            $this->fillReservation($reservation, $data);

            if (empty($reservation->status)) {
                $reservation->status = Reservation::STATUS_EN_COURS;
            }

            $reservation->save();

            if ($paymentReceipt instanceof UploadedFile) {
                $reservation->payment_receipt_path = $this->storeReceipt($reservation, $paymentReceipt);
                $reservation->save();
            }
            if ($visaDocument instanceof UploadedFile) {
                $reservation->visa_document_path = $this->storeVisaDocument($reservation, $visaDocument);
                $reservation->save();
            }

            $this->syncPassengers($reservation, $data['passengers'] ?? []);

            // Stock & allocation progressive uniquement si une date de départ est fournie.
            if (!empty($reservation->travel_date_id)) {
                $this->allocateAndSyncReservationRooms($reservation);
            } else {
                // Fallback : pas de date => pas de gestion de stock par départ.
                $this->syncReservationRooms($reservation, $data['hotel_rooms'] ?? []);
            }

            if ($reservation->partner_id) {
                $this->commissionService->calculateAndSaveForReservation($reservation->fresh());
            }
            return $reservation->fresh(['passengers', 'reservationRooms']);
        });
    }

    /**
     * Mise à jour d'une réservation.
     */
    public function update(Reservation $reservation, array $data, ?UploadedFile $paymentReceipt = null, ?UploadedFile $visaDocument = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $data, $paymentReceipt, $visaDocument) {
            // Revenir à un état neutre avant de recalculer une allocation (passengers_count, travel_date_id, etc.).
            $this->rollbackReservationAllocations($reservation->id);

            $this->fillReservation($reservation, $data);
            $reservation->save();

            if ($paymentReceipt instanceof UploadedFile) {
                $reservation->payment_receipt_path = $this->storeReceipt($reservation, $paymentReceipt);
                $reservation->save();
            }
            if ($visaDocument instanceof UploadedFile) {
                $reservation->visa_document_path = $this->storeVisaDocument($reservation, $visaDocument);
                $reservation->save();
            }

            $this->syncPassengers($reservation, $data['passengers'] ?? []);

            if (!empty($reservation->travel_date_id)) {
                $this->allocateAndSyncReservationRooms($reservation);
            } else {
                $this->syncReservationRooms($reservation, $data['hotel_rooms'] ?? []);
            }

            if ($reservation->partner_id) {
                $this->commissionService->calculateAndSaveForReservation($reservation->fresh());
            }
            return $reservation->fresh(['passengers', 'reservationRooms']);
        });
    }

    public function validateReservation(Reservation $reservation): Reservation
    {
        $reservation->status = Reservation::STATUS_VALIDEE;
        $reservation->save();
        if ($reservation->partner_id) {
            $this->commissionService->validateCommissionForReservation($reservation);
        }
        return $reservation;
    }

    public function delete(Reservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $this->rollbackReservationAllocations($reservation->id);
            if ($reservation->partner_id) {
                $this->commissionService->cancelCommissionForReservation($reservation);
            }
            $reservation->delete();
        });
    }

    private function fillReservation(Reservation $reservation, array $data): void
    {
        $reservation->tour_id = $data['tour_id'] ?? $reservation->tour_id;
        $travelDateId = $data['travel_date_id'] ?? null;
        if ($travelDateId !== null && $travelDateId !== '' && $travelDateId !== 'null') {
            $reservation->travel_date_id = (int) $travelDateId;
        } else {
            $reservation->travel_date_id = null;
        }
        $reservation->client_mode = $data['client_mode'] ?? $reservation->client_mode ?? 'existing';
        $reservation->client_external_id = $data['client_external_id'] ?? $reservation->client_external_id;

        $reservation->client_first_name = $data['client_first_name'] ?? $reservation->client_first_name;
        $reservation->client_last_name = $data['client_last_name'] ?? $reservation->client_last_name;
        $reservation->client_email = $data['client_email'] ?? $reservation->client_email;
        $reservation->client_phone = $data['client_phone'] ?? $reservation->client_phone;
        $reservation->client_document_type = $data['client_document_type'] ?? $reservation->client_document_type;
        $reservation->client_document_number = $data['client_document_number'] ?? $reservation->client_document_number;

        $reservation->payment_type = $data['payment_type'] ?? $reservation->payment_type;

        if (!empty($data['status'])) {
            $reservation->status = $data['status'];
        }

        $reservation->notes = $data['notes'] ?? $reservation->notes;

        $reservation->base_price = isset($data['base_price']) && $data['base_price'] !== '' ? (float) $data['base_price'] : null;
        if (array_key_exists('paid_amount', $data)) {
            $reservation->paid_amount = $data['paid_amount'] !== '' && $data['paid_amount'] !== null
                ? (float) $data['paid_amount']
                : null;
        }
        if (array_key_exists('prestation_type', $data)) {
            $reservation->prestation_type = $data['prestation_type'] ?: null;
        }
        // room_supplement_total est recalculé dans syncReservationRooms

        $reservation->visa_ok = array_key_exists('visa_ok', $data)
            ? (bool) $data['visa_ok']
            : ($reservation->visa_ok ?? true);
        $reservation->visa_notes = $data['visa_notes'] ?? $reservation->visa_notes;
        $reservation->visa_status = $data['visa_status'] ?? $reservation->visa_status;

        if (array_key_exists('partner_id', $data)) {
            $reservation->partner_id = $data['partner_id'];
        }
        if (array_key_exists('branch_id', $data)) {
            $reservation->branch_id = $data['branch_id'];
        }
        if (array_key_exists('sales_manager_id', $data)) {
            $reservation->sales_manager_id = $data['sales_manager_id'];
        }
        if (array_key_exists('agent_id', $data)) {
            $reservation->agent_id = $data['agent_id'];
        }
        if (array_key_exists('created_by', $data)) {
            $reservation->created_by = $data['created_by'];
        }
        if (array_key_exists('updated_by', $data)) {
            $reservation->updated_by = $data['updated_by'];
        }
    }

    /**
     * Stocke un document visa dans un sous-dossier dédié.
     */
    private function storeVisaDocument(Reservation $reservation, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'pdf';
        $filename = 'visa-' . $reservation->id . '-' . time() . '.' . $extension;
        $directory = 'reservation-visa/' . date('Y/m');

        Storage::disk('public')->putFileAs($directory, $file, $filename);

        return $directory . '/' . $filename;
    }

    /**
     * Stocke le reçu de paiement dans un sous-dossier dédié.
     */
    private function storeReceipt(Reservation $reservation, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'pdf';
        $filename = 'reservation-' . $reservation->id . '-' . time() . '.' . $extension;
        $directory = 'reservation-receipts/' . date('Y/m');

        Storage::disk('public')->putFileAs($directory, $file, $filename);

        return $directory . '/' . $filename; // utilisé avec asset('storage/'.$path)
    }

    /**
     * @param array<int,array<string,mixed>> $passengersData
     */
    private function syncPassengers(Reservation $reservation, array $passengersData): void
    {
        $keepIds = [];

        foreach ($passengersData as $row) {
            if (!is_array($row)) {
                continue;
            }
            $hasContent = ($row['first_name'] ?? '') !== '' || ($row['last_name'] ?? '') !== '';
            if (!$hasContent) {
                continue;
            }

            $id = isset($row['id']) ? (int) $row['id'] : 0;
            $payload = [
                'first_name' => $row['first_name'] ?? null,
                'last_name' => $row['last_name'] ?? null,
                'type' => $row['type'] ?? null,
                'birth_date' => $row['birth_date'] ?? null,
                'document_type' => $row['document_type'] ?? null,
                'document_number' => $row['document_number'] ?? null,
            ];

            if ($id > 0) {
                $passenger = ReservationPassenger::where('reservation_id', $reservation->id)->where('id', $id)->first();
                if ($passenger) {
                    $passenger->fill($payload)->save();
                    $keepIds[] = $passenger->id;
                    continue;
                }
            }

            $passenger = $reservation->passengers()->create($payload);
            $keepIds[] = $passenger->id;
        }

        if (!empty($keepIds)) {
            ReservationPassenger::where('reservation_id', $reservation->id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        } else {
            ReservationPassenger::where('reservation_id', $reservation->id)->delete();
        }

        $reservation->passengers_count = ReservationPassenger::where('reservation_id', $reservation->id)->count() ?: 1;
        $reservation->save();
    }

    /**
     * Synchronise les chambres réservées et recalcule room_supplement_total.
     *
     * @param array<int, array{tour_hotel_id?: int, tour_hotel_room_id?: int, room_count?: int}> $hotelRooms
     */
    private function syncReservationRooms(Reservation $reservation, array $hotelRooms): void
    {
        $keepIds = [];
        $totalSupplement = 0.0;

        foreach ($hotelRooms as $row) {
            if (!is_array($row)) {
                continue;
            }
            $tourHotelId = isset($row['tour_hotel_id']) ? (int) $row['tour_hotel_id'] : 0;
            $tourHotelRoomId = isset($row['tour_hotel_room_id']) ? (int) $row['tour_hotel_room_id'] : 0;
            $roomCount = max(0, (int) ($row['room_count'] ?? 0));
            if ($tourHotelId <= 0 || $tourHotelRoomId <= 0 || $roomCount < 1) {
                continue;
            }

            $room = TourHotelRoom::find($tourHotelRoomId);
            $supplementUnit = $room ? (float) $room->supplement : 0.0;
            $supplementTotal = $supplementUnit * $roomCount;
            $totalSupplement += $supplementTotal;

            $existing = ReservationRoom::where('reservation_id', $reservation->id)
                ->where('tour_hotel_id', $tourHotelId)
                ->where('tour_hotel_room_id', $tourHotelRoomId)
                ->first();

            if ($existing) {
                $existing->room_count = $roomCount;
                $existing->supplement_unit = $supplementUnit;
                $existing->supplement_total = $supplementTotal;
                $existing->save();
                $keepIds[] = $existing->id;
            } else {
                $created = $reservation->reservationRooms()->create([
                    'tour_hotel_id' => $tourHotelId,
                    'tour_hotel_room_id' => $tourHotelRoomId,
                    'room_count' => $roomCount,
                    'supplement_unit' => $supplementUnit,
                    'supplement_total' => $supplementTotal,
                ]);
                $keepIds[] = $created->id;
            }
        }

        ReservationRoom::where('reservation_id', $reservation->id)->whereNotIn('id', $keepIds)->delete();
        $reservation->room_supplement_total = $totalSupplement;
        $reservation->save();
    }

    /**
     * Rollback : retire l'occupation (stock réel) créée par cette réservation.
     * Utilisé sur update/delete pour recalculer sans accumuler l'occupation.
     */
    private function rollbackReservationAllocations(int $reservationId): void
    {
        $allocations = DB::table('reservation_room_allocations')
            ->where('reservation_id', $reservationId)
            ->select([
                'travel_date_id',
                'tour_hotel_id',
                'tour_hotel_room_id',
                'seats_allocated',
            ])
            ->get();

        if ($allocations->isEmpty()) {
            // Si aucun enregistrement d'allocation : on laisse reservation_rooms telles quelles.
            ReservationRoom::query()->where('reservation_id', $reservationId)->delete();
            return;
        }

        // Regrouper par (travel_date_id, tour_hotel_room_id) pour éviter des updates multiples.
        $grouped = $allocations->groupBy(fn ($a) => $a->travel_date_id . '_' . $a->tour_hotel_room_id);

        foreach ($grouped as $key => $rows) {
            /** @var object $first */
            $first = $rows->first();
            $travelDateId = (int) $first->travel_date_id;
            $tourHotelRoomId = (int) $first->tour_hotel_room_id;
            $seatsToRollback = (int) $rows->sum(fn ($r) => (int) $r->seats_allocated);
            if ($seatsToRollback <= 0) {
                continue;
            }

            // Lock occupation row si elle existe (elle doit exister si tables sont en place).
            $occ = DB::table('tour_room_type_occupancies')
                ->where('travel_date_id', $travelDateId)
                ->where('tour_hotel_room_id', $tourHotelRoomId)
                ->lockForUpdate()
                ->first();

            if (!$occ) {
                continue;
            }

            $newVal = max(0, (int) $occ->seats_occupied_total - $seatsToRollback);

            DB::table('tour_room_type_occupancies')
                ->where('travel_date_id', $travelDateId)
                ->where('tour_hotel_room_id', $tourHotelRoomId)
                ->update(['seats_occupied_total' => $newVal]);
        }

        DB::table('reservation_room_allocations')
            ->where('reservation_id', $reservationId)
            ->delete();

        // On supprime les lignes de facturation pour être cohérent avec la nouvelle allocation.
        ReservationRoom::query()->where('reservation_id', $reservationId)->delete();
    }

    /**
     * Allocation progressive + synchronisation des reservation_rooms et de l'occupation.
     *
     * Règle facturation (client donné) :
     * - supplément facturé "par chambre" => seulement quand cette réservation ouvre une chambre supplémentaire.
     */
    private function allocateAndSyncReservationRooms(Reservation $reservation): void
    {
        $travelDateId = (int) $reservation->travel_date_id;
        if ($travelDateId <= 0) {
            // Sécurité : si aucun travel_date_id, fallback.
            $this->syncReservationRooms($reservation, []);
            return;
        }

        $wpTourId = $reservation->getWpTourId();
        if (!$wpTourId) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'hotel_rooms' => ['Voyage associé introuvable (wp_post_id manquant).'],
            ]);
        }

        $passengersCount = (int) ($reservation->passengers_count ?? 0);
        if ($passengersCount <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'hotel_rooms' => ['Nombre de voyageurs invalide.'],
            ]);
        }

        $tourHotels = TourHotel::getAllForTour((int) $wpTourId)->load('rooms');
        if ($tourHotels->isEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'hotel_rooms' => ['Aucun hôtel configuré pour ce voyage.'],
            ]);
        }

        // La réservation est par définition sur un seul hôtel (règle métier).
        // On prend donc le 1er hôtel (en pratique une contrainte WP tour_id_unique doit garantir l’unicité).
        $tourHotel = $tourHotels->first();
        $rooms = $tourHotel->rooms->where('is_active', true)->values();

        if ($rooms->isEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'hotel_rooms' => ['Aucune chambre active configurée pour cet hôtel.'],
            ]);
        }

        $roomTypes = [];
        $roomIds = [];
        $totalCapacitySeats = 0;
        foreach ($rooms as $room) {
            $cap = (int) ($room->capacity_total ?? 0);
            $count = (int) ($room->room_count ?? 0);
            if ($cap <= 0 || $count <= 0) {
                continue;
            }
            $roomTypes[] = ['tour_hotel_room' => $room, 'tour_hotel_id' => (int) $tourHotel->id];
            $roomIds[] = (int) $room->id;
            $totalCapacitySeats += $count * $cap;
        }

        if (empty($roomTypes) || $totalCapacitySeats <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'hotel_rooms' => ['Capacité totale des chambres = 0.'],
            ]);
        }

        $roomIds = array_values(array_unique($roomIds));

        // 1) Assurer l'existence des lignes d'occupation (seats_occupied_total = 0 si nouvelle date).
        $existingRoomIds = collect();
        try {
            $existingRoomIds = DB::table('tour_room_type_occupancies')
                ->where('travel_date_id', $travelDateId)
                ->whereIn('tour_hotel_room_id', $roomIds)
                ->pluck('tour_hotel_room_id');
        } catch (\Throwable $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'hotel_rooms' => ['Tables d’occupation manquantes. Exécute les requêtes SQL nécessaires (tour_room_type_occupancies, reservation_room_allocations).'],
            ]);
        }

        $missing = array_values(array_diff($roomIds, $existingRoomIds->map(fn ($v) => (int) $v)->all()));
        foreach ($missing as $missingRoomId) {
            $room = $rooms->firstWhere('id', $missingRoomId);
            if (!$room) {
                continue;
            }

            DB::table('tour_room_type_occupancies')->insert([
                'travel_date_id' => $travelDateId,
                'tour_hotel_id' => (int) $tourHotel->id,
                'tour_hotel_room_id' => (int) $missingRoomId,
                'seats_occupied_total' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2) Lock occupation rows pour éviter le surbooking concurrent.
        $occupancies = DB::table('tour_room_type_occupancies')
            ->where('travel_date_id', $travelDateId)
            ->whereIn('tour_hotel_room_id', $roomIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('tour_hotel_room_id');

        $remainingSeats = $passengersCount;
        $allocByRoomId = [];
        $totalSupplement = 0.0;

        foreach ($rooms as $room) {
            if ($remainingSeats <= 0) {
                break;
            }

            $cap = (int) ($room->capacity_total ?? 0);
            $count = (int) ($room->room_count ?? 0);
            if ($cap <= 0 || $count <= 0) {
                continue;
            }

            $roomId = (int) $room->id;
            $typeTotalSeats = $count * $cap;
            $occupied = (int) (($occupancies[$roomId]->seats_occupied_total ?? 0));
            $available = $typeTotalSeats - $occupied;
            if ($available <= 0) {
                continue;
            }

            $take = min($remainingSeats, $available);
            $seatsBefore = $occupied;
            $seatsAfter = $occupied + (int) $take;

            $roomsConsumedBefore = $seatsBefore > 0 ? intdiv($seatsBefore + $cap - 1, $cap) : 0;
            $roomsConsumedAfter = $seatsAfter > 0 ? intdiv($seatsAfter + $cap - 1, $cap) : 0;
            $roomsNewCount = max(0, $roomsConsumedAfter - $roomsConsumedBefore);

            // Persist occupation.
            DB::table('tour_room_type_occupancies')
                ->where('travel_date_id', $travelDateId)
                ->where('tour_hotel_room_id', $roomId)
                ->update(['seats_occupied_total' => $seatsAfter]);

            // Keep allocation for rollback/debug.
            $allocByRoomId[$roomId] = [
                'tour_hotel_id' => (int) $tourHotel->id,
                'tour_hotel_room_id' => $roomId,
                'seats_allocated' => (int) $take,
                'rooms_new_count' => (int) $roomsNewCount,
                'rooms_total_count_after' => (int) $roomsConsumedAfter,
                'supplement_unit' => (float) ($room->supplement ?? 0),
            ];

            if ($roomsNewCount > 0) {
                $totalSupplement += $roomsNewCount * (float) ($room->supplement ?? 0);
            }

            $remainingSeats -= (int) $take;
        }

        if ($remainingSeats > 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'hotel_rooms' => ['Capacité insuffisante pour cette date de départ (allocation progressive impossible).'],
            ]);
        }

        // 3) Synchroniser reservation_rooms et reservation_room_allocations.
        ReservationRoom::query()->where('reservation_id', $reservation->id)->delete();
        DB::table('reservation_room_allocations')->where('reservation_id', $reservation->id)->delete();

        foreach ($allocByRoomId as $roomId => $alloc) {
            DB::table('reservation_room_allocations')->insert([
                'reservation_id' => (int) $reservation->id,
                'travel_date_id' => $travelDateId,
                'tour_hotel_id' => (int) $alloc['tour_hotel_id'],
                'tour_hotel_room_id' => (int) $alloc['tour_hotel_room_id'],
                'seats_allocated' => (int) $alloc['seats_allocated'],
                'rooms_new_count' => (int) $alloc['rooms_new_count'],
                'rooms_total_count' => (int) $alloc['rooms_total_count_after'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ((int) $alloc['rooms_new_count'] > 0) {
                $reservation->reservationRooms()->create([
                    'tour_hotel_id' => (int) $alloc['tour_hotel_id'],
                    'tour_hotel_room_id' => (int) $alloc['tour_hotel_room_id'],
                    'room_count' => (int) $alloc['rooms_new_count'],
                    'supplement_unit' => (float) $alloc['supplement_unit'],
                    'supplement_total' => (float) $alloc['rooms_new_count'] * (float) $alloc['supplement_unit'],
                ]);
            }
        }

        $reservation->room_supplement_total = $totalSupplement;
        $reservation->save();

        // 4) Recalculer le stock restant côté WP (seats = capacity - occupied).
        try {
            $occupiedTotal = DB::table('tour_room_type_occupancies')
                ->where('travel_date_id', $travelDateId)
                ->sum('seats_occupied_total');

            $remainingSeats = max(0, (int) $totalCapacitySeats - (int) $occupiedTotal);
            DB::connection('wp')->table('aj_travel_dates')
                ->where('id', $travelDateId)
                ->update(['seats' => $remainingSeats]);
        } catch (\Throwable $e) {
            // Best effort : le stock réel reste l’occupation MySQL.
            \Log::warning('ReservationService@allocate seats update wp failed', [
                'reservation_id' => $reservation->id,
                'travel_date_id' => $travelDateId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


