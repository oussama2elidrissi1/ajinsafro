<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationPassenger;
use App\Services\WordPressMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReservationService
{
    public function __construct(
        private readonly WordPressMediaService $mediaService,
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

            return $reservation->fresh(['passengers']);
        });
    }

    /**
     * Mise à jour d'une réservation.
     */
    public function update(Reservation $reservation, array $data, ?UploadedFile $paymentReceipt = null, ?UploadedFile $visaDocument = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $data, $paymentReceipt, $visaDocument) {
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

            return $reservation->fresh(['passengers']);
        });
    }

    public function validateReservation(Reservation $reservation): Reservation
    {
        $reservation->status = Reservation::STATUS_VALIDEE;
        $reservation->save();

        return $reservation;
    }

    public function delete(Reservation $reservation): void
    {
        $reservation->delete();
    }

    private function fillReservation(Reservation $reservation, array $data): void
    {
        $reservation->tour_id = $data['tour_id'] ?? $reservation->tour_id;
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

        $reservation->visa_ok = isset($data['visa_ok']) ? (bool) $data['visa_ok'] : $reservation->visa_ok;
        $reservation->visa_notes = $data['visa_notes'] ?? $reservation->visa_notes;
        $reservation->visa_status = $data['visa_status'] ?? $reservation->visa_status;
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
}


