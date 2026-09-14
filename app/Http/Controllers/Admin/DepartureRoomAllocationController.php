<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Voyage;
use App\Services\DepartureRoomAllocationEditor;
use App\Services\Reservations\ReservationPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartureRoomAllocationController extends Controller
{
    public function show(Request $request, Voyage $voyage, Departure $departure, DepartureRoomAllocationEditor $editor): JsonResponse
    {
        $this->checkAccess($request, $voyage, $departure);

        return response()->json($editor->snapshot($departure) + ['hotels' => $editor->hotels($departure)]);
    }

    public function update(Request $request, Voyage $voyage, Departure $departure, DepartureRoomAllocationEditor $editor, ReservationPricingService $pricing): JsonResponse
    {
        $this->checkAccess($request, $voyage, $departure);
        $data = $request->validate([
            'revision' => ['required', 'string', 'size:64'],
            'rooms' => ['required', 'array', 'min:1', 'max:100'],
            'rooms.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'rooms.*.room_type' => ['required', 'string', 'max:100'],
            'rooms.*.quantity' => ['required', 'integer', 'min:0', 'max:10000'],
            'rooms.*.capacity_per_room' => ['required', 'integer', 'min:1', 'max:50'],
            'rooms.*.supplement' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'rooms.*.hotel_id' => ['nullable', 'integer', 'min:1'],
        ]);
        return DB::transaction(function () use ($editor, $departure, $data, $pricing, $voyage) {
            $editor->update($departure, $data);

            return response()->json($editor->snapshot($departure) + [
                'availability' => $pricing->previewDepartureRooms(['tour_id' => $voyage->id, 'departure_id' => $departure->id]),
            ]);
        });
    }

    private function checkAccess(Request $request, Voyage $voyage, Departure $departure): void
    {
        abort_unless($request->user()?->can('circuits.voyages.view'), 403);
        abort_unless((int) $departure->voyage_id === (int) $voyage->id, 404);
    }
}
