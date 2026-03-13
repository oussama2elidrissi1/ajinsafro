<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationPassenger;
use App\Services\WordPressMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

class ReservationService
{
    public function __construct(
        protected WordPressMediaService $mediaService,
    ) {
    }

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

    public function create(array $data): Reservation
    {
        $reservation = new Reservation();
        $this->fillFromArray($reservation, $data);
        $reservation->status = $data['status'] ?? Reservation::STATUS_EN_COURS;

        if (!empty($data['payment_receipt']) && $data['payment_receipt'] instanceof UploadedFile) {
            $reservation->payment_receipt_path = $this->storeReceipt($data['payment_receipt']);
        }

        $reservation->save();

        $this->syncPassengers($reservation, $data['passengers'] ?? []);

        return $reservation->fresh(['passengers']);
    }

    public function update(Reservation $reservation, array $data): Reservation
    {
        $this->fillFromArray($reservation, $data);

        if (!empty($data['payment_receipt']) && $data['payment_receipt'] instanceof UploadedFile) {
            $reservation->payment_receipt_path = $this->storeReceipt($data['payment_receipt']);
        }

        $reservation->save();

        $this->syncPassengers($reservation, $data['passengers'] ?? []);

        return $reservation->fresh(['passengers']);
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

    protected function fillFromArray(Reservation $reservation, array $data): void
    {
        $reservation->tour_id = $data['tour_id'] ?? null;

        $reservation->client_mode = $data['client_mode'] ?? 'existing';
        $reservation->client_external_id = $data['client_mode'] === 'existing'
            ? ($data['client_external_id'] ?? null)
            : null;

        // Snapshot client – toujours rempli
        $reservation->client_first_name = $data['client_first_name'] ?? null;
        $reservation->client_last_name = $data['client_last_name'] ?? null;
        $reservation->client_email = $data['client_email'] ?? null;
        $reservation->client_phone = $data['client_phone'] ?? null;
        $reservation->client_document_type = $data['client_document_type'] ?? null;
        $reservation->client_document_number = $data['client_document_number'] ?? null;

        $reservation->payment_type = $data['payment_type'] ?? null;
        $reservation->notes = $data['notes'] ?? null;

        $passengers = $data['passengers'] ?? [];
        $reservation->passengers_count = is_array($passengers) ? max(count($passengers), 1) : 1;

        if (!empty($data['status'])) {
            $reservation->status = $data['status'];
        }
    }

    protected function syncPassengers(Reservation $reservation, array $rows): void
    {
        $reservation->passengers()->delete();

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $hasData = trim((string) ($row['first_name'] ?? '')) !== ''
                || trim((string) ($row['last_name'] ?? '')) !== '';
            if (!$hasData) {
                continue;
            }

            $reservation->passengers()->create([
                'first_name' => $row['first_name'] ?? null,
                'last_name' => $row['last_name'] ?? null,
                'type' => $row['type'] ?? null,
                'birth_date' => $row['birth_date'] ?? null,
                'document_type' => $row['document_type'] ?? null,
                'document_number' => $row['document_number'] ?? null,
            ]);
        }
    }

    protected function storeReceipt(UploadedFile $file): string
    {
        // On réutilise WordPressMediaService pour stocker dans le répertoire uploads/ajinsafro/reservations
        $path = $this->mediaService->store($file, 'reservations');

        return $path;
    }
}

<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationPassenger;
use App\Services\WordPressMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function __construct(
        private readonly WordPressMediaService $mediaService,
    ) {
    }

    /**
     * @param array $data Données validées (StoreReservationRequest)
     */
    public function create(array $data, ?UploadedFile $paymentReceipt = null): Reservation
    {
        return DB::transaction(function () use ($data, $paymentReceipt) {
            $reservation = new Reservation();
            $this->fillReservation($reservation, $data);

            // Statut par défaut
            if (empty($reservation->status)) {
                $reservation->status = Reservation::STATUS_EN_COURS;
            }

            $reservation->save();

            if ($paymentReceipt instanceof UploadedFile) {
                $reservation->payment_receipt_path = $this->storeReceipt($reservation, $paymentReceipt);
                $reservation->save();
            }

            $this->syncPassengers($reservation, $data['passengers'] ?? []);

            return $reservation->fresh(['passengers']);
        });
    }

    public function update(Reservation $reservation, array $data, ?UploadedFile $paymentReceipt = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $data, $paymentReceipt) {
            $this->fillReservation($reservation, $data);
            $reservation->save();

            if ($paymentReceipt instanceof UploadedFile) {
                $reservation->payment_receipt_path = $this->storeReceipt($reservation, $paymentReceipt);
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
    }

    private function storeReceipt(Reservation $reservation, UploadedFile $file): string
    {
        // On réutilise le WordPressMediaService comme helper de stockage disque,
        // mais dans un sous-dossier spécifique aux reçus de réservations.
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'pdf';

        $filename = 'reservation-' . $reservation->id . '-' . time() . '.' . $extension;
        $relativePath = 'reservation-receipts/' . date('Y/m') . '/' . $filename;

        $fullPath = $this->mediaService->path($relativePath);
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0775, true);
        }
        $file->move(dirname($fullPath), basename($fullPath));

        return $relativePath;
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

