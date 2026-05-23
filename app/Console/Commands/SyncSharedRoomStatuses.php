<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\ReservationRoom;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncSharedRoomStatuses extends Command
{
    protected $signature = 'reservations:sync-shared-room-statuses {--dry-run : Afficher uniquement, ne pas modifier}';
    protected $description = 'Met Ã  jour les statuts shared_room_status/pending et reservation.status pour les anciens dossiers demi-double partiels.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $updatedReservations = 0;
        $updatedRooms = 0;
        $skipped = 0;
        $ignoredReasons = [];

        $schema = Schema::connection('mysql');
        $hasCapacity = $schema->hasColumn('reservation_rooms', 'capacity');
        $hasPairedId = $schema->hasColumn('reservation_rooms', 'paired_reservation_id');

        $query = ReservationRoom::query()
            ->where(function ($q) {
                $q->whereIn('room_mode', ['half_male', 'half_female', 'shared_double'])
                    ->orWhere(function ($qq) {
                        $qq->where(function ($qqq) {
                            $qqq->whereNull('room_mode')
                                ->orWhere('room_mode', '');
                        })
                            ->where(function ($qqq) {
                                $qqq->where('source_room_type', 'double')
                                    ->orWhereRaw('LOWER(COALESCE(room_type_snapshot, "")) LIKE ?', ['%double%']);
                            });
                    });
            })
            ->whereRaw('COALESCE(passenger_count, 0) > 0')
            ->where(function ($q) {
                $q->whereNull('shared_room_status')
                    ->orWhere('shared_room_status', 'pending')
                    ->orWhere('shared_room_status', '');
            });

        if ($hasPairedId) {
            $query->where(function ($q) {
                $q->whereNull('paired_reservation_id')
                    ->orWhere('paired_reservation_id', 0);
            });
        }

        // Si capacity existe en DB, on peut filtrer directement
        if ($hasCapacity) {
            $query->whereRaw('COALESCE(passenger_count, 0) < COALESCE(capacity, 2)');
        }

        $roomIds = $query->pluck('reservation_id')->unique()->values()->all();

        if (empty($roomIds)) {
            $this->warn('Aucune chambre demi-double partielle trouvÃ©e Ã  mettre Ã  jour.');
            return self::SUCCESS;
        }

        $this->info("Dossiers candidats trouvÃ©s : " . count($roomIds));

        foreach (array_chunk($roomIds, 200) as $chunk) {
            $reservations = Reservation::query()
                ->with(['reservationRooms.departureHotelRoom', 'reservationRooms.tourHotelRoom'])
                ->whereIn('id', $chunk)
                ->get();

            foreach ($reservations as $reservation) {
                $hasPartial = false;
                foreach ($reservation->reservationRooms as $rr) {
                    $mode = (string) ($rr->room_mode ?? '');
                    $occupied = (int) ($rr->passenger_count ?? 0);
                    $paired = (int) ($rr->paired_reservation_id ?? 0);
                    $state = (string) ($rr->shared_room_status ?? '');
                    $capacity = $reservation->resolveRoomCapacity($rr);

                    $isExplicit = in_array($mode, ['half_male', 'half_female', 'shared_double'], true);
                    $normalized = str_replace(['-', '_'], '', strtolower($mode));
                    $isVariant = in_array($normalized, ['halfmale','halffemale','shareddouble','demidoublehomme','demidoublefemme','halfdoublemale','halfdoublefemale'], true);

                    $sourceType = (string) ($rr->source_room_type ?? '');
                    $roomSnapshot = strtolower((string) ($rr->room_type_snapshot ?? ''));
                    $isDoubleRoom = str_contains($roomSnapshot, 'double') || str_contains($roomSnapshot, 'demi-double') || $sourceType === 'double' || $sourceType === 'tour_hotel_room';
                    $looksLikeHalfDouble = ($isDoubleRoom && $occupied > 0 && $occupied < $capacity && $capacity === 2);

                    $isHalfDouble = $isExplicit || $isVariant || $looksLikeHalfDouble;

                    if (! $isHalfDouble) {
                        $ignoredReasons[] = "R#{$reservation->id} RR#{$rr->id} : mode='{$mode}' non reconnu";
                        $skipped++;
                        continue;
                    }

                    if ($occupied <= 0 || $occupied >= $capacity || $paired > 0 || $state === 'paired') {
                        $ignoredReasons[] = "R#{$reservation->id} RR#{$rr->id} : occupÃ©s={$occupied} cap={$capacity} paired={$paired} state={$state}";
                        $skipped++;
                        continue;
                    }

                    $hasPartial = true;
                    if ($state === '' || $state === 'pending') {
                        if (! $dryRun) {
                            $rr->shared_room_status = 'pending';
                            $rr->save();
                        }
                        $updatedRooms++;
                    }
                }

                if ($hasPartial) {
                    $resStatus = (string) $reservation->status;
                    if (! in_array($resStatus, [Reservation::STATUS_SHARED_ROOM_PENDING, Reservation::STATUS_SHARED_ROOM_PAIRED], true)) {
                        if (! $dryRun) {
                            $reservation->status = Reservation::STATUS_SHARED_ROOM_PENDING;
                            $reservation->save();
                        }
                        $updatedReservations++;
                        $this->info("RÃ©servation #{$reservation->id} passÃ©e en shared_room_pending" . ($dryRun ? ' (simulation)' : ''));
                    }
                } else {
                    $ignoredReasons[] = "R#{$reservation->id} : aucune chambre partielle dÃ©tectÃ©e parmi " . $reservation->reservationRooms->count() . " ligne(s)";
                }
            }
        }

        $this->info("Mise Ã  jour terminÃ©e : {$updatedReservations} rÃ©servations et {$updatedRooms} lignes chambres mises Ã  jour.");
        if ($dryRun) {
            $this->warn("Mode simulation (dry-run) : aucune modification enregistrÃ©e.");
        }
        if ($skipped > 0) {
            $this->warn("{$skipped} lignes ignorÃ©es.");
            foreach (array_slice($ignoredReasons, 0, 20) as $reason) {
                $this->line("  - {$reason}");
            }
        }
        return self::SUCCESS;
    }
}
