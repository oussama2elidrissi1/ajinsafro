<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\ReservationRoom;
use Illuminate\Console\Command;

class SyncSharedRoomStatuses extends Command
{
    protected $signature = 'reservations:sync-shared-room-statuses';
    protected $description = 'Met Ã  jour les statuts shared_room_status/pending et reservation.status pour les anciens dossiers demi-double partiels.';

    public function handle(): int
    {
        $updatedReservations = 0;
        $updatedRooms = 0;

        $query = ReservationRoom::query()
            ->whereIn('room_mode', ['half_male', 'half_female', 'shared_double'])
            ->whereRaw('COALESCE(passenger_count, 0) < COALESCE(capacity, 2)')
            ->whereRaw('COALESCE(passenger_count, 0) > 0')
            ->where(function ($q) {
                $q->whereNull('shared_room_status')
                    ->orWhere('shared_room_status', 'pending')
                    ->orWhere('shared_room_status', '');
            })
            ->where(function ($q) {
                $q->whereNull('paired_reservation_id')
                    ->orWhere('paired_reservation_id', 0);
            });

        $roomIds = $query->pluck('reservation_id')->unique()->values()->all();

        if (empty($roomIds)) {
            $this->info('Aucune chambre demi-double partielle trouvÃ©e Ã  mettre Ã  jour.');
            return self::SUCCESS;
        }

        foreach (array_chunk($roomIds, 200) as $chunk) {
            $reservations = Reservation::query()
                ->with('reservationRooms')
                ->whereIn('id', $chunk)
                ->get();

            foreach ($reservations as $reservation) {
                $hasPartial = false;
                foreach ($reservation->reservationRooms as $rr) {
                    $mode = (string) ($rr->room_mode ?? '');
                    if (! in_array($mode, ['half_male', 'half_female', 'shared_double'], true)) {
                        continue;
                    }
                    $occupied = (int) ($rr->passenger_count ?? 0);
                    $capacity = (int) ($rr->capacity ?? 2);
                    $paired = (int) ($rr->paired_reservation_id ?? 0);
                    if ($occupied > 0 && $occupied < $capacity && $paired <= 0) {
                        $hasPartial = true;
                        $state = (string) ($rr->shared_room_status ?? '');
                        if ($state === '' || $state === 'pending') {
                            $rr->shared_room_status = 'pending';
                            $rr->save();
                            $updatedRooms++;
                        }
                    }
                }

                if ($hasPartial && ! in_array((string) $reservation->status, [Reservation::STATUS_SHARED_ROOM_PENDING, Reservation::STATUS_SHARED_ROOM_PAIRED], true)) {
                    $reservation->status = Reservation::STATUS_SHARED_ROOM_PENDING;
                    $reservation->save();
                    $updatedReservations++;
                }
            }
        }

        $this->info("Mise Ã  jour terminÃ©e : {$updatedReservations} rÃ©servations et {$updatedRooms} lignes chambres mises Ã  jour.");
        return self::SUCCESS;
    }
}
