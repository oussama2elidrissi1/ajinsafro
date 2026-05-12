<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Departure;
use App\Models\DepartureHotelRoom;
use App\Models\Reservation;
use App\Models\ReservationPassenger;
use App\Models\ReservationRoom;
use App\Models\TourHotel;
use App\Models\TourHotelRoom;
use App\Models\Voyage;
use App\Services\Booking\ReservationLifecycleService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ReservationService
{
    private ?bool $reservationsHasBasePriceColumn = null;

    private ?bool $reservationsHasRoomSupplementTotalColumn = null;

    private ?bool $reservationsHasChannelColumn = null;

    public function __construct(
        private readonly WordPressMediaService $mediaService,
        private readonly PartnerCommissionService $commissionService,
        private readonly DepartureStockService $departureStock,
        private readonly ReservationLifecycleService $reservationLifecycle,
        private readonly ReservationDossierService $reservationDossier,
    ) {}

    /**
     * Liste paginée des réservations avec filtres simples.
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Reservation::query()->withCount('passengers');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_type'])) {
            $query->where('payment_type', $filters['payment_type']);
        }

        if (! empty($filters['client'])) {
            $q = trim((string) $filters['client']);
            $query->where(function ($sub) use ($q) {
                $sub->where('client_first_name', 'like', '%'.$q.'%')
                    ->orWhere('client_last_name', 'like', '%'.$q.'%')
                    ->orWhere('client_email', 'like', '%'.$q.'%')
                    ->orWhere('client_phone', 'like', '%'.$q.'%');
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
            $client = $this->reservationDossier->resolveOrCreateClientFromPayload($data);
            $data = $this->enrichDataWithClientSnapshot($data, $client);

            $reservation = new Reservation;
            $this->fillReservation($reservation, $data);

            if (empty($reservation->status)) {
                $reservation->status = Reservation::STATUS_PENDING;
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

            if ($this->usesDepartureHotelRooms($data['hotel_rooms'] ?? []) && ! empty($reservation->departure_id)) {
                $this->syncReservationRooms($reservation, $data['hotel_rooms'] ?? []);
                $synced = $reservation->fresh(['reservationRooms']);
                $this->reservationLifecycle->validateAvailabilityIfNeeded($synced);
                $this->reservationLifecycle->commitAfterPersist($synced);
            } elseif (! empty($reservation->travel_date_id) && $this->shouldAllocateProgressiveRooms($reservation)) {
                $this->allocateAndSyncReservationRooms($reservation);
            } else {
                if (! empty($reservation->travel_date_id)) {
                    $this->logProgressiveAllocationSkipped($reservation);
                }
                $this->syncReservationRooms($reservation, $data['hotel_rooms'] ?? []);
            }

            $this->syncExtras($reservation, $data['extras_payload'] ?? []);
            $this->reservationDossier->ensureDossierNumber($reservation);
            $this->reservationDossier->refreshReservationFinancials($reservation);
            $reservation->save();

            if (! empty($data['payment_payload']) && is_array($data['payment_payload'])) {
                $this->reservationDossier->addPayment($reservation, $data['payment_payload'], $paymentReceipt);
                $reservation->save();
            }

            if (! empty($data['documents_payload']) && is_array($data['documents_payload'])) {
                foreach ($data['documents_payload'] as $documentPayload) {
                    if (! is_array($documentPayload) || ! (($documentPayload['file'] ?? null) instanceof UploadedFile)) {
                        continue;
                    }

                    $this->reservationDossier->addUploadedDocument(
                        $reservation,
                        (string) ($documentPayload['type'] ?? 'other'),
                        (string) ($documentPayload['title'] ?? 'Document'),
                        $documentPayload['file'],
                        isset($documentPayload['created_by']) ? (int) $documentPayload['created_by'] : null
                    );
                }
            }

            if ($reservation->partner_id) {
                $this->commissionService->calculateAndSaveForReservation($reservation->fresh());
            }
            $this->reservationDossier->addHistory(
                $reservation,
                'reservation.created',
                isset($data['created_by']) ? (int) $data['created_by'] : null,
                null,
                [
                    'dossier_number' => $reservation->dossier_number,
                    'total_amount' => $reservation->total_amount,
                    'paid_amount' => $reservation->paid_amount,
                    'payment_status' => $reservation->payment_status,
                ]
            );
            $fresh = $reservation->fresh(['passengers', 'reservationRooms', 'tour']);
            if (config('app.debug') && $fresh) {
                Log::debug('reservation.created', [
                    'id' => $fresh->id,
                    'tour_id' => $fresh->tour_id,
                    'laravel_voyage_ids_same_wp' => Voyage::allIdsSharingWpTour((int) $fresh->tour_id),
                    'wp_post_id' => $fresh->tour?->wp_post_id,
                    'travel_date_id' => $fresh->travel_date_id,
                    'prestation_type' => $fresh->prestation_type,
                    'status' => $fresh->status,
                ]);
            }

            return $fresh;
        });
    }

    /**
     * Mise à jour d'une réservation.
     */
    public function update(Reservation $reservation, array $data, ?UploadedFile $paymentReceipt = null, ?UploadedFile $visaDocument = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $data, $paymentReceipt, $visaDocument) {
            $client = $this->reservationDossier->resolveOrCreateClientFromPayload($data, $reservation);
            $data = $this->enrichDataWithClientSnapshot($data, $client);
            $historyBefore = [
                'total_amount' => $reservation->total_amount,
                'paid_amount' => $reservation->paid_amount,
                'payment_status' => $reservation->payment_status,
                'dossier_status' => $reservation->dossier_status,
            ];

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

            if ($this->usesDepartureHotelRooms($data['hotel_rooms'] ?? []) && ! empty($reservation->departure_id)) {
                $this->syncReservationRooms($reservation, $data['hotel_rooms'] ?? []);
                $fresh = $reservation->fresh(['reservationRooms']);
                $this->reservationLifecycle->validateAvailabilityIfNeeded($fresh);
                $this->reservationLifecycle->commitAfterPersist($fresh);
            } elseif (! empty($reservation->travel_date_id) && $this->shouldAllocateProgressiveRooms($reservation)) {
                $this->allocateAndSyncReservationRooms($reservation);
            } else {
                if (! empty($reservation->travel_date_id)) {
                    $this->logProgressiveAllocationSkipped($reservation);
                }
                $this->syncReservationRooms($reservation, $data['hotel_rooms'] ?? []);
            }

            $this->syncExtras($reservation, $data['extras_payload'] ?? []);
            $this->reservationDossier->ensureDossierNumber($reservation);
            $this->reservationDossier->refreshReservationFinancials($reservation);
            $reservation->save();

            if (! empty($data['payment_payload']) && is_array($data['payment_payload'])) {
                $this->reservationDossier->addPayment($reservation, $data['payment_payload'], $paymentReceipt);
                $reservation->save();
            }

            if ($reservation->partner_id) {
                $this->commissionService->calculateAndSaveForReservation($reservation->fresh());
            }

            $this->reservationDossier->addHistory(
                $reservation,
                'reservation.updated',
                isset($data['updated_by']) ? (int) $data['updated_by'] : null,
                $historyBefore,
                [
                    'total_amount' => $reservation->total_amount,
                    'paid_amount' => $reservation->paid_amount,
                    'payment_status' => $reservation->payment_status,
                    'dossier_status' => $reservation->dossier_status,
                ]
            );

            return $reservation->fresh(['passengers', 'reservationRooms']);
        });
    }

    public function validateReservation(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation) {
            if (in_array($reservation->status, [
                Reservation::STATUS_CONFIRMED,
                Reservation::STATUS_PARTIALLY_PAID,
                Reservation::STATUS_PAID,
            ], true)) {
                return $reservation->fresh();
            }

            $this->reservationDossier->applyConfirmationState($reservation);
            $reservation->save();
            if ($reservation->partner_id) {
                $this->commissionService->validateCommissionForReservation($reservation);
            }
            $this->reservationLifecycle->commitAfterPersist($reservation->fresh());
            $this->reservationDossier->addHistory($reservation, 'reservation.confirmed', null, null, [
                'status' => $reservation->status,
                'dossier_status' => $reservation->dossier_status,
                'confirmed_at' => optional($reservation->confirmed_at)->toIso8601String(),
            ]);

            return $reservation->fresh();
        });
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
        if (array_key_exists('tour_id', $data)) {
            $rawTour = $data['tour_id'];
            if ($rawTour !== null && $rawTour !== '' && (int) $rawTour > 0) {
                $reservation->tour_id = Voyage::canonicalVoyageId((int) $rawTour);
            }
        }
        if (array_key_exists('departure_id', $data)) {
            $rawDep = $data['departure_id'];
            $reservation->departure_id = $rawDep !== null && $rawDep !== '' && $rawDep !== 'null'
                ? (int) $rawDep
                : null;
        }

        $travelDateId = $data['travel_date_id'] ?? null;
        if ($travelDateId !== null && $travelDateId !== '' && $travelDateId !== 'null') {
            $reservation->travel_date_id = (int) $travelDateId;
        } else {
            $reservation->travel_date_id = null;
        }

        if (! empty($reservation->departure_id)) {
            $dep = Departure::query()->find((int) $reservation->departure_id);
            if ($dep && $dep->wp_travel_date_id) {
                $reservation->travel_date_id = (int) $dep->wp_travel_date_id;
            }
        }

        if (! empty($reservation->tour_id)) {
            $reservation->voyage_id = Voyage::canonicalVoyageId((int) $reservation->tour_id);
        }
        if (array_key_exists('voyage_id', $data) && $data['voyage_id'] !== null && $data['voyage_id'] !== '') {
            $reservation->voyage_id = (int) $data['voyage_id'];
        }

        if (array_key_exists('created_by_user_id', $data)) {
            $rawCb = $data['created_by_user_id'];
            $reservation->created_by_user_id = $rawCb !== null && $rawCb !== '' ? (int) $rawCb : null;
        } elseif (! empty($reservation->created_by) && empty($reservation->created_by_user_id)) {
            $reservation->created_by_user_id = (int) $reservation->created_by;
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

        if (! empty($data['status'])) {
            $reservation->status = $data['status'];
        }

        $reservation->notes = $data['notes'] ?? $reservation->notes;

        if ($this->reservationsHasBasePriceColumn()) {
            $reservation->base_price = isset($data['base_price']) && $data['base_price'] !== '' ? (float) $data['base_price'] : null;
        }
        if (array_key_exists('total_base', $data)) {
            $reservation->total_base = $data['total_base'] !== '' && $data['total_base'] !== null
                ? (float) $data['total_base']
                : null;
        }
        if (array_key_exists('extras_total', $data)) {
            $reservation->extras_total = $data['extras_total'] !== '' && $data['extras_total'] !== null
                ? (float) $data['extras_total']
                : null;
        }
        if (array_key_exists('total_amount', $data)) {
            $reservation->total_amount = $data['total_amount'] !== '' && $data['total_amount'] !== null
                ? (float) $data['total_amount']
                : null;
        }
        if (array_key_exists('paid_amount', $data)) {
            $reservation->paid_amount = $data['paid_amount'] !== '' && $data['paid_amount'] !== null
                ? (float) $data['paid_amount']
                : null;
        }
        if (array_key_exists('remaining_amount', $data)) {
            $reservation->remaining_amount = $data['remaining_amount'] !== '' && $data['remaining_amount'] !== null
                ? (float) $data['remaining_amount']
                : null;
        }
        if (array_key_exists('payment_status', $data)) {
            $reservation->payment_status = $data['payment_status'] ?: null;
        }
        if (array_key_exists('dossier_status', $data)) {
            $reservation->dossier_status = $data['dossier_status'] ?: null;
        }
        if (array_key_exists('dossier_number', $data)) {
            $reservation->dossier_number = $data['dossier_number'] ?: null;
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
        if (array_key_exists('assigned_to', $data)) {
            $reservation->assigned_to = $data['assigned_to'];
        } elseif (array_key_exists('agent_id', $data)) {
            $reservation->assigned_to = $data['agent_id'];
        }
        if (array_key_exists('created_by', $data)) {
            $reservation->created_by = $data['created_by'];
        }
        if (array_key_exists('updated_by', $data)) {
            $reservation->updated_by = $data['updated_by'];
        }
        if (array_key_exists('wp_tour_post_id', $data)) {
            $wp = $data['wp_tour_post_id'];
            $reservation->wp_tour_post_id = $wp !== null && $wp !== '' ? (int) $wp : null;
        }
        if (array_key_exists('channel', $data) && $this->reservationsHasChannelColumn()) {
            $channel = $data['channel'];
            $reservation->channel = $channel !== null && $channel !== '' ? (string) $channel : null;
        }
        if (array_key_exists('catalog_source_code', $data)) {
            $c = $data['catalog_source_code'];
            $reservation->catalog_source_code = $c !== null && $c !== '' ? (string) $c : null;
        }
        if (array_key_exists('voyage_flight_id', $data)) {
            $vf = $data['voyage_flight_id'];
            $reservation->voyage_flight_id = $vf !== null && $vf !== '' ? (int) $vf : null;
        }
        if (array_key_exists('confirmed_at', $data)) {
            $reservation->confirmed_at = $data['confirmed_at'];
        }
        if (array_key_exists('cancelled_at', $data)) {
            $reservation->cancelled_at = $data['cancelled_at'];
        }
    }

    /**
     * Stocke un document visa dans un sous-dossier dédié.
     */
    private function storeVisaDocument(Reservation $reservation, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'pdf';
        $filename = 'visa-'.$reservation->id.'-'.time().'.'.$extension;
        $directory = 'reservation-visa/'.date('Y/m');

        Storage::disk('public')->putFileAs($directory, $file, $filename);

        return $directory.'/'.$filename;
    }

    /**
     * Stocke le reçu de paiement dans un sous-dossier dédié.
     */
    private function storeReceipt(Reservation $reservation, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'pdf';
        $filename = 'reservation-'.$reservation->id.'-'.time().'.'.$extension;
        $directory = 'reservation-receipts/'.date('Y/m');

        Storage::disk('public')->putFileAs($directory, $file, $filename);

        return $directory.'/'.$filename; // utilisé avec asset('storage/'.$path)
    }

    /**
     * @param  array<int,array<string,mixed>>  $passengersData
     */
    private function syncPassengers(Reservation $reservation, array $passengersData): void
    {
        $keepIds = [];

        foreach ($passengersData as $row) {
            if (! is_array($row)) {
                continue;
            }
            $hasContent = ($row['first_name'] ?? '') !== '' || ($row['last_name'] ?? '') !== '';
            if (! $hasContent) {
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

        if (! empty($keepIds)) {
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
     * @param  array<int, array<string, mixed>>  $extrasPayload
     */
    private function syncExtras(Reservation $reservation, array $extrasPayload): void
    {
        $reservation->extras()->delete();

        foreach ($extrasPayload as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $quantity = max(1, (int) ($row['quantity'] ?? 1));
            $unitPrice = isset($row['unit_price']) && is_numeric($row['unit_price'])
                ? (float) $row['unit_price']
                : (float) ($row['price'] ?? 0);
            $totalPrice = isset($row['total_price']) && is_numeric($row['total_price'])
                ? (float) $row['total_price']
                : round($unitPrice * $quantity, 2);

            $reservation->extras()->create([
                'voyage_extra_id' => ! empty($row['voyage_extra_id']) ? (int) $row['voyage_extra_id'] : null,
                'name' => $name,
                'description' => $row['description'] ?? null,
                'price' => $totalPrice,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'total_price' => $totalPrice,
                'application_scope' => $row['application_scope'] ?? null,
                'passenger_key' => $row['passenger_key'] ?? ($row['pax'] ?? null),
                'traveler_keys' => is_array($row['traveler_keys'] ?? null) ? $row['traveler_keys'] : null,
            ]);
        }
    }

    /**
     * Synchronise les chambres réservées et recalcule room_supplement_total.
     *
     * @param  array<int, array{tour_hotel_id?: int, tour_hotel_room_id?: int, room_count?: int}>  $hotelRooms
     */
    private function syncReservationRooms(Reservation $reservation, array $hotelRooms): void
    {
        $keepIds = [];
        $totalSupplement = 0.0;

        foreach ($hotelRooms as $row) {
            if (! is_array($row)) {
                continue;
            }
            $dhrId = isset($row['departure_hotel_room_id']) ? (int) $row['departure_hotel_room_id'] : 0;
            if ($dhrId > 0) {
                $roomCount = max(0, (int) ($row['room_count'] ?? 0));
                if ($roomCount < 1) {
                    continue;
                }
                $dhr = DepartureHotelRoom::find($dhrId);
                if (! $dhr) {
                    continue;
                }
                $supplementUnit = (float) $dhr->supplement;
                $supplementTotal = $supplementUnit * $roomCount;
                $totalSupplement += $supplementTotal;

                $existing = ReservationRoom::where('reservation_id', $reservation->id)
                    ->where('departure_hotel_room_id', $dhrId)
                    ->first();

                if ($existing) {
                    $existing->room_count = $roomCount;
                    $existing->departure_hotel_room_id = $dhrId;
                    $existing->departure_hotel_id = $dhr->departure_hotel_id;
                    $existing->room_type_snapshot = $dhr->room_type;
                    $existing->tour_hotel_id = null;
                    $existing->tour_hotel_room_id = null;
                    $existing->supplement_unit = $supplementUnit;
                    $existing->supplement_total = $supplementTotal;
                    $existing->save();
                    $keepIds[] = $existing->id;
                } else {
                    $created = $reservation->reservationRooms()->create([
                        'departure_hotel_room_id' => $dhrId,
                        'departure_hotel_id' => $dhr->departure_hotel_id,
                        'room_type_snapshot' => $dhr->room_type,
                        'tour_hotel_id' => null,
                        'tour_hotel_room_id' => null,
                        'room_count' => $roomCount,
                        'supplement_unit' => $supplementUnit,
                        'supplement_total' => $supplementTotal,
                    ]);
                    $keepIds[] = $created->id;
                }

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
        if ($this->reservationsHasRoomSupplementTotalColumn()) {
            $reservation->room_supplement_total = $totalSupplement;
        }
        $reservation->save();
    }

    /**
     * Tables sur la connexion Laravel par défaut (pas WP).
     */
    /**
     * @param  array<int, array<string, mixed>>  $hotelRooms
     */
    private function usesDepartureHotelRooms(array $hotelRooms): bool
    {
        foreach ($hotelRooms as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (! empty($row['departure_hotel_room_id'])) {
                return true;
            }
        }

        return false;
    }

    private function roomOccupancyTablesReady(): bool
    {
        try {
            return Schema::hasTable('tour_room_type_occupancies')
                && Schema::hasTable('reservation_room_allocations');
        } catch (\Throwable $e) {
            Log::debug('ReservationService: room occupancy schema check failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Allocation progressive + occupation concurrentielle : module avancé, non requis pour une réservation standard.
     */
    private function shouldAllocateProgressiveRooms(Reservation $reservation): bool
    {
        if (! $this->roomOccupancyTablesReady()) {
            return false;
        }

        return $reservation->getWpTourId() !== null;
    }

    private function logProgressiveAllocationSkipped(Reservation $reservation): void
    {
        $reason = ! $this->roomOccupancyTablesReady()
            ? 'occupancy_tables_not_migrated'
            : 'laravel_only_voyage_no_wp_tour';

        Log::info('ReservationService: réservation sans allocation progressive (chemin standard)', [
            'reservation_id' => $reservation->id,
            'travel_date_id' => $reservation->travel_date_id,
            'reason' => $reason,
        ]);
    }

    /**
     * Rollback : retire l'occupation (stock réel) créée par cette réservation.
     * Utilisé sur update/delete pour recalculer sans accumuler l'occupation.
     */
    private function rollbackReservationAllocations(int $reservationId): void
    {
        $reservation = Reservation::with('reservationRooms')->find($reservationId);
        if ($reservation) {
            $this->reservationLifecycle->releaseBeforeMutation($reservation);
        }

        if (! $this->roomOccupancyTablesReady()) {
            ReservationRoom::query()->where('reservation_id', $reservationId)->delete();

            return;
        }

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
        $grouped = $allocations->groupBy(fn ($a) => $a->travel_date_id.'_'.$a->tour_hotel_room_id);

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

            if (! $occ) {
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
        if (! $wpTourId) {
            Log::warning('ReservationService@allocate: appelé sans wp_post_id, repli sur sync basique', [
                'reservation_id' => $reservation->id,
            ]);
            $this->syncReservationRooms($reservation, []);

            return;
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

        // Allocation multi-sejours: agréger les chambres actives de tous les séjours hôtels.
        $rooms = collect();
        $roomHotelIds = [];
        foreach ($tourHotels as $tourHotel) {
            foreach ($tourHotel->rooms->where('is_active', true)->values() as $room) {
                $rooms->push($room);
                $roomHotelIds[(int) $room->id] = (int) $tourHotel->id;
            }
        }

        if ($rooms->isEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'hotel_rooms' => ['Aucune chambre active configurée pour les séjours hôtels.'],
            ]);
        }

        $validRoomCount = 0;
        $roomIds = [];
        $totalCapacitySeats = 0;
        foreach ($rooms as $room) {
            $cap = (int) ($room->capacity_total ?? 0);
            $count = (int) ($room->room_count ?? 0);
            if ($cap <= 0 || $count <= 0) {
                continue;
            }
            $validRoomCount++;
            $roomIds[] = (int) $room->id;
            $totalCapacitySeats += $count * $cap;
        }

        if ($validRoomCount === 0 || $totalCapacitySeats <= 0) {
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
            Log::warning('ReservationService@allocate: échec requête occupation', [
                'reservation_id' => $reservation->id,
                'travel_date_id' => $travelDateId,
                'error' => $e->getMessage(),
            ]);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'hotel_rooms' => [
                    config('app.debug')
                        ? $e->getMessage()
                        : 'La gestion avancée des chambres par date est temporairement indisponible. Réessayez plus tard ou enregistrez la réservation sans cette option.',
                ],
            ]);
        }

        $missing = array_values(array_diff($roomIds, $existingRoomIds->map(fn ($v) => (int) $v)->all()));
        foreach ($missing as $missingRoomId) {
            $room = $rooms->firstWhere('id', $missingRoomId);
            if (! $room) {
                continue;
            }

            DB::table('tour_room_type_occupancies')->insert([
                'travel_date_id' => $travelDateId,
                'tour_hotel_id' => (int) ($roomHotelIds[$missingRoomId] ?? 0),
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
        $sharedRoomPendingTypeIds = [];

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

            $isHalfDouble = ($cap === 2)
                && is_string($room->room_type ?? null)
                && mb_strtolower((string) $room->room_type) === 'double'
                && (int) $take === 1
                && $passengersCount === 1;

            // Default logic: any occupied seat opens the room (ceil).
            // Half-double logic (Double, 1 adult): do NOT consume a full room until the second seat is paired (floor).
            $roomsConsumedBefore = $seatsBefore > 0
                ? ($isHalfDouble ? intdiv($seatsBefore, $cap) : intdiv($seatsBefore + $cap - 1, $cap))
                : 0;
            $roomsConsumedAfter = $seatsAfter > 0
                ? ($isHalfDouble ? intdiv($seatsAfter, $cap) : intdiv($seatsAfter + $cap - 1, $cap))
                : 0;
            $roomsNewCount = max(0, $roomsConsumedAfter - $roomsConsumedBefore);

            // Persist occupation.
            DB::table('tour_room_type_occupancies')
                ->where('travel_date_id', $travelDateId)
                ->where('tour_hotel_room_id', $roomId)
                ->update(['seats_occupied_total' => $seatsAfter]);

            // Keep allocation for rollback/debug.
            $allocByRoomId[$roomId] = [
                'tour_hotel_id' => (int) ($roomHotelIds[$roomId] ?? 0),
                'tour_hotel_room_id' => $roomId,
                'seats_allocated' => (int) $take,
                'rooms_new_count' => (int) $roomsNewCount,
                'rooms_total_count_after' => (int) $roomsConsumedAfter,
                'supplement_unit' => (float) ($room->supplement ?? 0),
                'capacity_total' => (int) $cap,
                'room_type' => (string) ($room->room_type ?? ''),
            ];

            // Pricing rule (explicit): supplement is per physical room, split per seat.
            // For Double (cap=2), a half-double pays half the supplement.
            $totalSupplement += ((float) ($room->supplement ?? 0) / max(1, $cap)) * (int) $take;

            if ($isHalfDouble) {
                $sharedRoomPendingTypeIds[] = $roomId;
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

            $reservation->reservationRooms()->create([
                'tour_hotel_id' => (int) $alloc['tour_hotel_id'],
                'tour_hotel_room_id' => (int) $alloc['tour_hotel_room_id'],
                'room_type_snapshot' => (string) ($alloc['room_type'] ?? ''),
                'passenger_count' => (int) ($alloc['seats_allocated'] ?? 0),
                'room_count' => (int) $alloc['rooms_new_count'], // 0 for half-double seat
                'supplement_unit' => (float) $alloc['supplement_unit'],
                'supplement_total' => ((float) ($alloc['supplement_unit'] ?? 0) / max(1, (int) ($alloc['capacity_total'] ?? 1))) * (int) ($alloc['seats_allocated'] ?? 0),
            ]);
        }

        if ($this->reservationsHasRoomSupplementTotalColumn()) {
            $reservation->room_supplement_total = $totalSupplement;
        }

        // Shared-room status + pairing (Double, 1 adult).
        if ($sharedRoomPendingTypeIds !== []) {
            $reservation->status = Reservation::STATUS_SHARED_ROOM_PENDING;
        }
        $reservation->save();

        if ($sharedRoomPendingTypeIds !== []) {
            $this->tryPairSharedDoubleReservations($reservation, $travelDateId, $sharedRoomPendingTypeIds);
        }

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

    /**
     * Pair two compatible half-double reservations (same date + same room type).
     *
     * Source of truth: reservations.status + reservation_room_allocations (seat allocation).
     *
     * @param  list<int>  $tourHotelRoomIds
     */
    private function tryPairSharedDoubleReservations(Reservation $reservation, int $travelDateId, array $tourHotelRoomIds): void
    {
        foreach ($tourHotelRoomIds as $tourHotelRoomId) {
            $candidateId = (int) Reservation::query()
                ->where('id', '!=', $reservation->id)
                ->where('travel_date_id', $travelDateId)
                ->where('tour_id', $reservation->tour_id)
                ->where('status', Reservation::STATUS_SHARED_ROOM_PENDING)
                ->whereExists(function ($q) use ($tourHotelRoomId) {
                    $q->select(DB::raw(1))
                        ->from('reservation_room_allocations as rra')
                        ->whereColumn('rra.reservation_id', 'reservations.id')
                        ->where('rra.tour_hotel_room_id', $tourHotelRoomId)
                        ->where('rra.seats_allocated', 1);
                })
                ->orderBy('created_at')
                ->value('id');

            if ($candidateId <= 0) {
                continue;
            }

            // Pair: purely workflow/status (inventory already reserved as seats).
            Reservation::query()
                ->whereIn('id', [$reservation->id, $candidateId])
                ->update(['status' => Reservation::STATUS_SHARED_ROOM_PAIRED]);
        }
    }

    private function reservationsHasBasePriceColumn(): bool
    {
        return $this->reservationsHasBasePriceColumn
            ??= Schema::connection('mysql')->hasColumn('reservations', 'base_price');
    }

    private function reservationsHasRoomSupplementTotalColumn(): bool
    {
        return $this->reservationsHasRoomSupplementTotalColumn
            ??= Schema::connection('mysql')->hasColumn('reservations', 'room_supplement_total');
    }

    private function reservationsHasChannelColumn(): bool
    {
        return $this->reservationsHasChannelColumn
            ??= Schema::connection('mysql')->hasColumn('reservations', 'channel');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function enrichDataWithClientSnapshot(array $data, ?Client $client): array
    {
        if (! $client) {
            return $data;
        }

        $data['client_external_id'] = (int) $client->id;
        $data['client_first_name'] = $data['client_first_name'] ?? $client->first_name;
        $data['client_last_name'] = $data['client_last_name'] ?? $client->last_name;
        $data['client_email'] = $data['client_email'] ?? $client->email;
        $data['client_phone'] = $data['client_phone'] ?? $client->phone;

        if (empty($data['client_document_number'])) {
            $data['client_document_number'] = $client->national_id_number ?: $client->passport_number;
        }
        if (empty($data['client_document_type'])) {
            $data['client_document_type'] = $client->national_id_number ? 'cin' : ($client->passport_number ? 'passport' : null);
        }

        return $data;
    }
}
