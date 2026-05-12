<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Reservation;
use App\Models\ReservationDossier;
use App\Models\ReservationHistory;

class BackfillReservationDossiers extends Command
{
    protected $signature = 'reservations:backfill-dossiers {--dry-run} {--force}';

    protected $description = 'Backfill reservation_dossiers from existing reservations (idempotent).';

    public function handle(): int
    {
        $dry = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Scanning reservations for backfill...');

        $total = Reservation::query()->count();
        $toProcess = Reservation::query()->whereNull('reservation_dossier_id')->get();
        $alreadyAttached = $total - $toProcess->count();

        $this->line("Total reservations: {$total}");
        $this->line("Already attached to dossier: {$alreadyAttached}");
        $this->line("To attach: {$toProcess->count()}");

        if ($toProcess->isEmpty()) {
            $this->info('Nothing to do.');
            return 0;
        }

        $examples = $toProcess->take(10)->map(function (Reservation $r) {
            return [
                'reservation_id' => $r->id,
                'client_id' => $r->client_external_id,
                'tour_id' => $r->tour_id,
                'departure_id' => $r->departure_id,
                'total_amount' => $r->total_amount,
                'paid_amount' => $r->paid_amount,
            ];
        })->values()->all();

        $this->line('Examples (first 10 to be created):');
        $this->table(['reservation_id','client_id','tour_id','departure_id','total_amount','paid_amount'], $examples);

        if ($dry && ! $force) {
            $this->info('Dry run complete. No changes applied.');
            return 0;
        }

        if (! $force) {
            if (! $this->confirm('Run the backfill and create dossiers? This will modify the database.')) {
                $this->info('Aborted.');
                return 0;
            }
        }

        $created = 0;
        foreach ($toProcess as $reservation) {
            // Idempotency: check again
            if ($reservation->reservation_dossier_id) {
                continue;
            }

            DB::transaction(function () use ($reservation, &$created) {
                // Re-check inside transaction
                $reservation->refresh();
                if ($reservation->reservation_dossier_id) {
                    return;
                }

                $year = date('Y');
                $dossierNumber = sprintf('RES-%s-%06d', $year, $reservation->id);

                // Map dossier_status from reservation status
                $statusMap = [
                    Reservation::STATUS_CONFIRMED => Reservation::DOSSIER_CONFIRMED,
                    Reservation::STATUS_PENDING => Reservation::DOSSIER_PENDING,
                    Reservation::STATUS_CANCELLED => Reservation::DOSSIER_CANCELLED,
                ];

                $dossierStatus = $statusMap[$reservation->status] ?? Reservation::DOSSIER_PENDING;

                // Payment status mapping
                $total = (float) ($reservation->total_amount ?? 0);
                $paid = (float) ($reservation->paid_amount ?? 0);
                $remaining = $reservation->remaining_amount !== null ? (float) $reservation->remaining_amount : max(0.0, $total - $paid);

                if ($total <= 0 || $paid <= 0) {
                    $paymentStatus = Reservation::PAYMENT_STATUS_NON_PAID;
                } elseif ($paid > 0 && $paid < $total) {
                    $paymentStatus = Reservation::PAYMENT_STATUS_PARTIAL;
                } else {
                    $paymentStatus = Reservation::PAYMENT_STATUS_PAID;
                }

                $dossier = ReservationDossier::create([
                    'dossier_number' => $dossierNumber,
                    'client_id' => $reservation->client_external_id ?? null,
                    'main_reservation_id' => $reservation->id,
                    'total_base' => $reservation->total_base ?? 0,
                    'room_supplement_total' => $reservation->room_supplement_total ?? 0,
                    'extras_total' => $reservation->extras_total ?? 0,
                    'total_amount' => $total,
                    'paid_amount' => $paid,
                    'remaining_amount' => $remaining,
                    'payment_status' => $paymentStatus,
                    'dossier_status' => $dossierStatus,
                    'created_by' => $reservation->created_by_user_id ?? $reservation->created_by ?? null,
                    'assigned_to' => $reservation->assigned_to ?? null,
                    'confirmed_at' => $reservation->confirmed_at ?? null,
                    'cancelled_at' => $reservation->cancelled_at ?? null,
                ]);

                // Attach reservation
                $reservation->reservation_dossier_id = $dossier->id;
                $reservation->save();

                // History entry
                ReservationHistory::create([
                    'reservation_dossier_id' => $dossier->id,
                    'reservation_id' => $reservation->id,
                    'user_id' => null,
                    'action' => 'backfill_created',
                    'note' => 'Dossier généré automatiquement depuis une ancienne réservation',
                ]);

                $created++;
            });
        }

        $this->info("Backfill complete. Dossiers created: {$created}");
        return 0;
    }
}
