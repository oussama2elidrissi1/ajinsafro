<?php

namespace App\Services;

use App\Models\Departure;
use App\Models\ReservationRoomAllocation;
use App\Models\TourHotel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartureRoomAllocationEditor
{
    public function hotels(Departure $departure)
    {
        return TourHotel::query()->where('tour_id', $departure->voyage->wp_post_id ?: $departure->voyage_id)
            ->orderBy('sort_order')->get(['id', 'hotel_name']);
    }

    public function snapshot(Departure $departure): array
    {
        $rows = $departure->roomAllocations()->get()->map(fn ($row) => $row->only([
            'id', 'hotel_id', 'room_type', 'quantity', 'capacity_per_room', 'supplement', 'sort_order',
        ]))->all();

        return [
            'departure' => $departure->only(['id', 'voyage_id', 'start_date', 'total_capacity', 'reserved_capacity', 'available_capacity', 'status']),
            'rooms' => $rows,
            'revision' => hash('sha256', json_encode($rows)),
        ];
    }

    public function update(Departure $departure, array $data): void
    {
        $hotelIds = $this->hotels($departure)->modelKeys();
        DB::transaction(function () use ($departure, $data, $hotelIds) {
            $departure = Departure::query()->lockForUpdate()->findOrFail($departure->id);
            abort_unless(hash_equals($this->snapshot($departure)['revision'], $data['revision']), 409,
                'Les chambres ont été modifiées ailleurs. Rechargez la fenêtre avant de réessayer.');
            $existing = $departure->roomAllocations()->lockForUpdate()->get()->keyBy('id');
            // Keep source IDs stable: saved reservations refer to these rows.
            $used = ReservationRoomAllocation::query()
                ->where('room_source_type', 'departure_room_allocation')
                ->whereIn('room_source_id', $existing->keys())
                ->whereHas('reservation', fn ($q) => $q->where('departure_id', $departure->id)
                    ->whereNotIn('status', ['cancelled', 'canceled']))
                ->get()->groupBy('room_source_id');
            $kept = [];
            foreach ($data['rooms'] as $index => $values) {
                $id = $values['id'] ?? null;
                if ($id && ! $existing->has($id)) {
                    throw ValidationException::withMessages(["rooms.$index.id" => 'Cette chambre ne fait pas partie du départ.']);
                }
                $hotelId = $values['hotel_id'] ?? null;
                if ($hotelId && ! in_array((int) $hotelId, $hotelIds, true)) {
                    throw ValidationException::withMessages(["rooms.$index.hotel_id" => 'Choisissez un hôtel de ce voyage.']);
                }
                $bookings = $used->get($id, collect());
                $required = $bookings->sum(fn ($row) => max(1, (int) $row->rooms_total_count));
                if ($values['quantity'] < $required) {
                    throw ValidationException::withMessages(["rooms.$index.quantity" => "Au moins $required chambre(s) sont déjà utilisées par des réservations."]);
                }
                $row = $id ? $existing->get($id) : $departure->roomAllocations()->make();
                if ($bookings->isNotEmpty() && ((int) $row->capacity_per_room !== (int) $values['capacity_per_room']
                    || (int) $row->hotel_id !== (int) $hotelId || $row->room_type !== trim($values['room_type']))) {
                    throw ValidationException::withMessages(["rooms.$index.room_type" => 'Le type, la capacité et l’hôtel d’une chambre réservée doivent être conservés.']);
                }
                $row->fill(collect($values)->only(['hotel_id', 'quantity', 'capacity_per_room', 'supplement'])->all());
                $row->room_type = trim($values['room_type']);
                $row->sort_order = $index;
                $row->save();
                $kept[] = $row->id;
            }
            $removed = $existing->keys()->diff($kept);
            if ($removed->intersect($used->keys())->isNotEmpty()) {
                throw ValidationException::withMessages(['rooms' => 'Une chambre utilisée par une réservation ne peut pas être supprimée.']);
            }
            $departure->roomAllocations()->whereIn('id', $removed)->delete();
        });
    }
}
