<?php

namespace App\Services\Booking;

use App\Models\Departure;
use App\Models\DepartureHotelRoom;
use Illuminate\Support\Facades\DB;

class DepartureLifecycleService
{
    public function recalculateRoomRowStatus(DepartureHotelRoom $dhr): void
    {
        if (in_array($dhr->status, [DepartureHotelRoom::STATUS_CLOSED, DepartureHotelRoom::STATUS_INACTIVE], true)) {
            return;
        }

        $avail = max(0, (int) $dhr->available_rooms);
        $threshold = (int) config('booking_lifecycle.room_limited_threshold', 3);

        if ($avail <= 0) {
            $dhr->status = DepartureHotelRoom::STATUS_FULL;
        } elseif ($avail <= $threshold) {
            $dhr->status = DepartureHotelRoom::STATUS_LIMITED;
        } else {
            $dhr->status = DepartureHotelRoom::STATUS_AVAILABLE;
        }

        $dhr->save();
    }

    public function recomputeDepartureAggregates(int $departureId): void
    {
        $departure = Departure::query()->find($departureId);
        if (! $departure) {
            return;
        }

        $row = DB::connection($departure->getConnectionName())
            ->table('departure_hotel_rooms as dhr')
            ->join('departure_hotels as dh', 'dh.id', '=', 'dhr.departure_hotel_id')
            ->where('dh.departure_id', $departureId)
            ->where('dh.is_active', true)
            ->whereNotIn('dhr.status', [DepartureHotelRoom::STATUS_CLOSED, DepartureHotelRoom::STATUS_INACTIVE])
            ->selectRaw('COALESCE(SUM(dhr.reserved_places), 0) as rp, COALESCE(SUM(dhr.available_places), 0) as ap')
            ->first();

        $reservedPlaces = (int) ($row->rp ?? 0);
        $availablePlaces = (int) ($row->ap ?? 0);

        $departure->reserved_capacity = $reservedPlaces;
        $departure->available_capacity = max(0, $availablePlaces);

        $threshold = (int) config('booking_lifecycle.departure_limited_threshold_places', 5);

        if (in_array($departure->status, [Departure::STATUS_CLOSED, Departure::STATUS_CANCELED, Departure::STATUS_CANCELLED, Departure::STATUS_DRAFT], true)) {
            $departure->save();

            return;
        }

        if ($availablePlaces <= 0) {
            $departure->status = Departure::STATUS_FULL;
        } elseif ($availablePlaces <= $threshold) {
            $departure->status = Departure::STATUS_LIMITED;
        } else {
            $departure->status = Departure::STATUS_OPEN;
        }

        $departure->save();
    }
}
