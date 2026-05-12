<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Departure;
use App\Models\DepartureHotelRoom;
use App\Models\Reservation;
use App\Models\ReservationDossier;
use App\Models\ReservationDocument;
use App\Models\ReservationHistory;
use App\Models\ReservationPayment;
use App\Models\ReservationRoom;
use App\Models\TourHotelRoom;
use App\Models\Voyage;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReservationDossierService
{
    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_NON_PAID = 'non_paid';
    public const PAYMENT_DEPOSIT = 'deposit';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID = 'paid';

    public const DOSSIER_DRAFT = 'draft';
    public const DOSSIER_PENDING = 'pending';
    public const DOSSIER_CONFIRMED = 'confirmed';
    public const DOSSIER_CANCELLED = 'cancelled';
    public const DOSSIER_COMPLETED = 'completed';

    /**
     * @param  array<int, array<string, mixed>>  $hotelRooms
     * @param  array<int, array<string, mixed>>  $extrasPayload
     * @return array{base_unit_price: float,total_base: float,room_supplement_total: float,extras_total: float,total_amount: float,selected_room_capacity: int,selected_room_count: int}
     */
    public function summarizeForReservationPayload(
        int $voyageId,
        ?int $departureId,
        int $travelerCount,
        array $hotelRooms,
        array $extrasPayload,
        ?float $manualBasePrice = null,
    ): array {
        $voyage = Voyage::query()->find($voyageId);
        $departure = $departureId ? Departure::query()->find($departureId) : null;

        $baseUnitPrice = 0.0;
        if ($departure) {
            $baseUnitPrice = (float) ($departure->sale_price ?? $departure->base_price ?? 0);
        }
        if ($baseUnitPrice <= 0 && $voyage?->price_from !== null) {
            $baseUnitPrice = (float) $voyage->price_from;
        }
        if ($baseUnitPrice <= 0 && $manualBasePrice !== null) {
            $baseUnitPrice = max(0, (float) $manualBasePrice);
        }

        $travelerCount = max(1, $travelerCount);
        $totalBase = round($baseUnitPrice * $travelerCount, 2);

        $roomSupplementTotal = 0.0;
        $selectedRoomCapacity = 0;
        $selectedRoomCount = 0;

        foreach ($hotelRooms as $row) {
            if (! is_array($row)) {
                continue;
            }

            $roomCount = max(0, (int) ($row['room_count'] ?? 0));
            if ($roomCount < 1) {
                continue;
            }

            $selectedRoomCount += $roomCount;

            $departureHotelRoomId = (int) ($row['departure_hotel_room_id'] ?? 0);
            if ($departureHotelRoomId > 0) {
                $room = DepartureHotelRoom::query()->find($departureHotelRoomId);
                if (! $room) {
                    continue;
                }
                $availableRooms = max(0, (int) ($room->available_rooms ?? 0));
                if ($roomCount > $availableRooms) {
                    throw ValidationException::withMessages([
                        'hotel_rooms' => ['Le stock disponible est dépassé pour la chambre '.$room->room_type.'.'],
                    ]);
                }

                $roomSupplementTotal += $roomCount * (float) ($room->supplement ?? 0);
                $selectedRoomCapacity += $roomCount * max(1, (int) ($room->capacity_total ?? 1));

                continue;
            }

            $tourHotelRoomId = (int) ($row['tour_hotel_room_id'] ?? 0);
            if ($tourHotelRoomId > 0) {
                $room = TourHotelRoom::query()->find($tourHotelRoomId);
                if (! $room) {
                    continue;
                }
                $roomSupplementTotal += $roomCount * (float) ($room->supplement ?? 0);
                $selectedRoomCapacity += $roomCount * max(1, (int) ($room->capacity_total ?? 1));
            }
        }

        if ($departureId && $selectedRoomCapacity < $travelerCount) {
            throw ValidationException::withMessages([
                'hotel_rooms' => ['La capacité des chambres sélectionnées est insuffisante pour ce dossier.'],
            ]);
        }

        $extrasTotal = $this->computeExtrasTotalFromPayload($extrasPayload);
        $totalAmount = round($totalBase + $roomSupplementTotal + $extrasTotal, 2);

        return [
            'base_unit_price' => round($baseUnitPrice, 2),
            'total_base' => round($totalBase, 2),
            'room_supplement_total' => round($roomSupplementTotal, 2),
            'extras_total' => round($extrasTotal, 2),
            'total_amount' => $totalAmount,
            'selected_room_capacity' => $selectedRoomCapacity,
            'selected_room_count' => $selectedRoomCount,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $extrasPayload
     */
    public function computeExtrasTotalFromPayload(array $extrasPayload): float
    {
        $total = 0.0;

        foreach ($extrasPayload as $row) {
            if (! is_array($row)) {
                continue;
            }

            $quantity = max(1, (int) ($row['quantity'] ?? 1));
            $lineTotal = $row['total_price'] ?? $row['line_total'] ?? null;
            if ($lineTotal !== null && is_numeric($lineTotal)) {
                $total += (float) $lineTotal;

                continue;
            }

            $unitPrice = $row['unit_price'] ?? $row['price'] ?? 0;
            $travelerMultiplier = is_array($row['traveler_keys'] ?? null) ? max(1, count($row['traveler_keys'])) : 1;
            $scope = (string) ($row['application_scope'] ?? '');

            if ($scope === 'traveler_selection') {
                $total += ((float) $unitPrice * $quantity * $travelerMultiplier);
            } else {
                $total += ((float) $unitPrice * $quantity);
            }
        }

        return round($total, 2);
    }

    /**
     * @return array{total_base: float,room_supplement_total: float,extras_total: float,total_amount: float,paid_amount: float,remaining_amount: float,payment_status: string}
     */
    public function computeFinancialSummary(
        float $totalBase,
        float $roomSupplementTotal,
        float $extrasTotal,
        float $paidAmount,
        bool $strict = true,
    ): array {
        $totalAmount = round(max(0, $totalBase) + max(0, $roomSupplementTotal) + max(0, $extrasTotal), 2);
        $paidAmount = round(max(0, $paidAmount), 2);

        if ($strict && $paidAmount > $totalAmount + 0.009) {
            throw ValidationException::withMessages([
                'payment_amount' => ['Le montant payé ne peut pas dépasser le total du dossier.'],
            ]);
        }

        $remainingAmount = round(max(0, $totalAmount - $paidAmount), 2);

        return [
            'total_base' => round(max(0, $totalBase), 2),
            'room_supplement_total' => round(max(0, $roomSupplementTotal), 2),
            'extras_total' => round(max(0, $extrasTotal), 2),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'payment_status' => $this->derivePaymentStatus($totalAmount, $paidAmount),
        ];
    }

    public function derivePaymentStatus(float $totalAmount, float $paidAmount): string
    {
        $totalAmount = round(max(0, $totalAmount), 2);
        $paidAmount = round(max(0, $paidAmount), 2);

        if ($paidAmount <= 0.0) {
            return self::PAYMENT_NON_PAID;
        }

        if ($totalAmount > 0.0 && abs($paidAmount - $totalAmount) < 0.01) {
            return self::PAYMENT_PAID;
        }

        if ($paidAmount > 0.0 && $totalAmount > 0.0 && $paidAmount < $totalAmount) {
            return self::PAYMENT_PARTIAL;
        }

        return self::PAYMENT_PARTIAL;
    }

    public function ensureDossierNumber(Reservation $reservation): void
    {
        if (! empty($reservation->dossier_number)) {
            return;
        }

        $reservation->dossier_number = $this->generateDossierNumber((int) $reservation->id, $reservation->created_at);
    }

    public function generateDossierNumber(int $sequence, CarbonInterface|string|null $date = null): string
    {
        $year = $date instanceof CarbonInterface
            ? (int) $date->format('Y')
            : (is_string($date) && trim($date) !== '' ? (int) date('Y', strtotime($date)) : (int) now()->format('Y'));

        $baseSeq = max(1, $sequence);
        $candidate = sprintf('RES-%d-%06d', $year, $baseSeq);

        // If candidate already exists, find the max sequence used for the year and increment it.
        if (ReservationDossier::query()->where('dossier_number', $candidate)->exists()) {
            $like = 'RES-' . $year . '-%';
            $maxSeq = ReservationDossier::query()
                ->where('dossier_number', 'like', $like)
                ->select(DB::raw("MAX(CAST(SUBSTRING_INDEX(dossier_number, '-', -1) AS UNSIGNED)) as max_seq"))
                ->value('max_seq');

            $next = ($maxSeq ? (int) $maxSeq + 1 : $baseSeq + 1);
            $candidate = sprintf('RES-%d-%06d', $year, $next);
        }

        return $candidate;
    }

    /**
     * Assign a unique dossier_number to the given dossier, retrying on duplicate key.
     * This method persists the dossier with the resolved unique number.
     *
     * @param  ReservationDossier  $dossier
     */
    public function assignUniqueDossierNumber(ReservationDossier $dossier, CarbonInterface|string|null $date = null, int $maxAttempts = 5): void
    {
        $attempt = 0;
        $sequence = (int) ($dossier->id ?: 1);
        do {
            $attempt++;
            $candidate = $this->generateDossierNumber($sequence + ($attempt - 1), $date);
            $dossier->dossier_number = $candidate;
            try {
                $dossier->save();
                return;
            } catch (\Illuminate\Database\QueryException $e) {
                // Duplicate key? try again with higher sequence
                $sqlState = $e->errorInfo[0] ?? null;
                $errorCode = $e->errorInfo[1] ?? null;
                if ($sqlState === '23000' || $errorCode === 1062) {
                    // continue loop to try next sequence
                    continue;
                }
                throw $e;
            }
        } while ($attempt < max(1, $maxAttempts));

        // If we exhausted retries, throw an exception to surface the issue.
        throw new \RuntimeException('Unable to assign unique dossier number after ' . $maxAttempts . ' attempts.');
    }

    public function applyCancellationState(Reservation $reservation, ?CarbonInterface $when = null): Reservation
    {
        $reservation->status = Reservation::STATUS_CANCELLED;
        $reservation->dossier_status = self::DOSSIER_CANCELLED;
        $reservation->cancelled_at = $when ?? now();

        return $reservation;
    }

    public function applyConfirmationState(Reservation $reservation, ?CarbonInterface $when = null): Reservation
    {
        $reservation->status = Reservation::STATUS_CONFIRMED;
        $reservation->dossier_status = self::DOSSIER_CONFIRMED;
        $reservation->confirmed_at = $when ?? now();

        return $reservation;
    }

    public function refreshReservationFinancials(Reservation $reservation, bool $strict = false): void
    {
        $roomSupplementTotal = round((float) ($reservation->reservationRooms()->sum('supplement_total') ?? 0), 2);
        $extrasTotal = round((float) ($reservation->extras()->sum('total_price') ?: $reservation->extras()->sum('price')), 2);
        $paymentsSum = round((float) ($reservation->payments()->sum('amount') ?? 0), 2);
        $paidAmount = $paymentsSum > 0 ? $paymentsSum : (float) ($reservation->paid_amount ?? 0);
        $totalBase = (float) ($reservation->total_base ?? $reservation->base_price ?? 0);

        $summary = $this->computeFinancialSummary($totalBase, $roomSupplementTotal, $extrasTotal, $paidAmount, $strict);
        $this->ensureDossierNumber($reservation);

        $reservation->base_price = $summary['total_base'];
        $reservation->total_base = $summary['total_base'];
        $reservation->room_supplement_total = $summary['room_supplement_total'];
        $reservation->extras_total = $summary['extras_total'];
        $reservation->total_amount = $summary['total_amount'];
        $reservation->paid_amount = $summary['paid_amount'];
        $reservation->remaining_amount = $summary['remaining_amount'];
        $reservation->payment_status = $summary['payment_status'];
        $reservation->dossier_status = $reservation->dossier_status ?: self::DOSSIER_PENDING;

        if ($reservation->relationLoaded('dossier') ? $reservation->dossier : $reservation->dossier()->exists()) {
            $this->syncDossierFromReservation($reservation);
        }
    }

    public function syncDossierFromReservation(Reservation $reservation): ?ReservationDossier
    {
        $dossier = $reservation->relationLoaded('dossier') ? $reservation->dossier : $reservation->dossier()->first();
        if (! $dossier) {
            return null;
        }

        if (empty($dossier->dossier_number)) {
            $dossier->dossier_number = $reservation->dossier_number ?: $this->generateDossierNumber((int) $dossier->id, $reservation->created_at);
        }

        $dossier->client_id = $reservation->client_external_id ?: $dossier->client_id;
        $dossier->main_reservation_id = $dossier->main_reservation_id ?: $reservation->id;
        $dossier->created_by = $dossier->created_by ?: ($reservation->created_by_user_id ?: $reservation->created_by);
        $dossier->assigned_to = $reservation->assigned_to ?: $reservation->agent_id ?: $dossier->assigned_to;
        $dossier->total_base = (float) ($reservation->total_base ?? $reservation->base_price ?? 0);
        $dossier->room_supplement_total = (float) ($reservation->room_supplement_total ?? 0);
        $dossier->extras_total = (float) ($reservation->extras_total ?? 0);
        $dossier->total_amount = (float) ($reservation->total_amount ?? 0);
        $dossier->paid_amount = (float) ($reservation->paid_amount ?? 0);
        $dossier->remaining_amount = (float) ($reservation->remaining_amount ?? max(0, $dossier->total_amount - $dossier->paid_amount));
        $dossier->payment_status = $reservation->payment_status ?: $dossier->payment_status ?: self::PAYMENT_NON_PAID;
        $dossier->dossier_status = $reservation->dossier_status ?: $dossier->dossier_status ?: self::DOSSIER_PENDING;
        $dossier->confirmed_at = $reservation->confirmed_at ?: $dossier->confirmed_at;
        $dossier->cancelled_at = $reservation->cancelled_at ?: $dossier->cancelled_at;
        $dossier->save();

        return $dossier;
    }

    /**
     * @param  array{payment_date?: mixed,payment_method?: mixed,amount?: mixed,reference?: mixed,note?: mixed,created_by?: mixed}  $payload
     */
    public function addPayment(Reservation $reservation, array $payload, ?UploadedFile $proofFile = null): ?ReservationPayment
    {
        $amount = round(max(0, (float) ($payload['amount'] ?? 0)), 2);
        if ($amount <= 0) {
            return null;
        }

        $currentPaid = round((float) ($reservation->payments()->sum('amount') ?: $reservation->paid_amount ?: 0), 2);
        $totalAmount = round((float) ($reservation->total_amount ?? 0), 2);
        if ($totalAmount > 0 && ($currentPaid + $amount) > $totalAmount + 0.009) {
            throw ValidationException::withMessages([
                'payment_amount' => ['Le montant payé ne peut pas dépasser le total du dossier.'],
            ]);
        }

        $payment = $reservation->payments()->create([
            'reservation_dossier_id' => $reservation->reservation_dossier_id,
            'payment_date' => $payload['payment_date'] ?? now()->toDateString(),
            'payment_method' => (string) ($payload['payment_method'] ?? 'Autre'),
            'amount' => $amount,
            'reference' => $payload['reference'] ?? null,
            'note' => $payload['note'] ?? null,
            'created_by' => isset($payload['created_by']) ? (int) $payload['created_by'] : null,
        ]);

        if ($proofFile instanceof UploadedFile) {
            $payment->proof_file = $this->storePaymentProof($reservation, $payment, $proofFile);
            $payment->save();

            $this->addDocument(
                $reservation,
                'payment_proof',
                'Justificatif paiement '.$payment->payment_date?->format('d/m/Y'),
                $payment->proof_file,
                $proofFile->getMimeType(),
                isset($payload['created_by']) ? (int) $payload['created_by'] : null
            );
        }

        $this->refreshReservationFinancials($reservation, true);
        $reservation->save();
        $this->syncDossierFromReservation($reservation);

        return $payment;
    }

    public function addUploadedDocument(
        Reservation $reservation,
        string $type,
        string $title,
        UploadedFile $file,
        ?int $createdBy = null,
    ): ReservationDocument {
        $path = $this->storeDocumentFile($reservation, $file);

        return $this->addDocument($reservation, $type, $title, $path, $file->getMimeType(), $createdBy);
    }

    public function addDocument(
        Reservation $reservation,
        string $type,
        string $title,
        string $filePath,
        ?string $mimeType = null,
        ?int $createdBy = null,
    ): ReservationDocument {
        return $reservation->documents()->create([
            'reservation_dossier_id' => $reservation->reservation_dossier_id,
            'type' => $type,
            'title' => $title,
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $oldValue
     * @param  array<string, mixed>|null  $newValue
     */
    public function addHistory(
        Reservation $reservation,
        string $action,
        ?int $userId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?string $note = null,
    ): ReservationHistory {
        return $reservation->histories()->create([
            'reservation_dossier_id' => $reservation->reservation_dossier_id,
            'user_id' => $userId,
            'action' => $action,
            'old_value' => $oldValue ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : null,
            'new_value' => $newValue ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : null,
            'note' => $note,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function resolveOrCreateClientFromPayload(array $data, ?Reservation $reservation = null): ?Client
    {
        $mode = (string) ($data['client_mode'] ?? 'new');
        if ($mode === 'existing' && ! empty($data['client_external_id'])) {
            return Client::query()->find((int) $data['client_external_id']);
        }

        $existing = $reservation?->client;
        $payload = [
            'first_name' => trim((string) ($data['client_first_name'] ?? '')),
            'last_name' => trim((string) ($data['client_last_name'] ?? '')),
            'full_name' => trim((string) (($data['client_first_name'] ?? '').' '.($data['client_last_name'] ?? ''))),
            'phone' => $data['client_phone'] ?? null,
            'email' => $data['client_email'] ?? null,
            'nationality' => $data['client_nationality'] ?? null,
            'address_line_1' => $data['client_address'] ?? null,
            'national_id_number' => (($data['client_document_type'] ?? '') === 'cin') ? ($data['client_document_number'] ?? null) : null,
            'passport_number' => (($data['client_document_type'] ?? '') === 'passport') ? ($data['client_document_number'] ?? null) : null,
            'status' => $existing?->status ?: 'active',
            'branch_id' => $data['branch_id'] ?? $existing?->branch_id,
            'partner_id' => $data['partner_id'] ?? $existing?->partner_id,
        ];

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return $existing;
        }

        if ($payload['first_name'] === '' && $payload['last_name'] === '') {
            return null;
        }

        return Client::query()->create($payload);
    }

    public function storeDocumentFile(Reservation $reservation, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
        $filename = 'reservation-'.$reservation->id.'-doc-'.time().'-'.mt_rand(100, 999).'.'.$extension;
        $directory = 'reservation-documents/'.date('Y/m');

        Storage::disk('public')->putFileAs($directory, $file, $filename);

        return $directory.'/'.$filename;
    }

    public function storePaymentProof(Reservation $reservation, ReservationPayment $payment, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'pdf';
        $filename = 'reservation-'.$reservation->id.'-payment-'.$payment->id.'-'.time().'.'.$extension;
        $directory = 'reservation-payments/'.date('Y/m');

        Storage::disk('public')->putFileAs($directory, $file, $filename);

        return $directory.'/'.$filename;
    }
}
