<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Reservation;
use App\Models\ReservationExtra;
use App\Models\Voyage;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VoyageReservationController extends Controller
{
    public function store(Request $request, string $slug, ReservationService $reservationService): JsonResponse
    {
        $voyage = Voyage::query()->where('slug', $slug)->first();
        if (! $voyage) {
            abort(404);
        }

        $data = $request->validate([
            'departure_id' => 'required|integer|exists:departures,id',
            'travel_date_id' => 'required|integer',
            'departure_hotel_room_id' => 'nullable|integer',

            'client_mode' => 'required|in:existing,new',
            'client_external_id' => 'required_if:client_mode,existing|nullable|integer|exists:clients,id',
            'client_first_name' => 'required_if:client_mode,new|nullable|string|max:100',
            'client_last_name' => 'required_if:client_mode,new|nullable|string|max:100',
            'client_phone' => 'nullable|string|max:50',
            'client_email' => 'nullable|email|max:190',
            'client_document_type' => 'nullable|string|max:50',
            'client_document_number' => 'nullable|string|max:100',

            'passengers' => 'nullable|array',
            'passengers.*.first_name' => 'nullable|string|max:100',
            'passengers.*.last_name' => 'nullable|string|max:100',
            'passengers.*.type' => 'nullable|in:adult,child,infant',
            'passengers.*.birth_date' => 'nullable|date',
            'passengers.*.document_type' => 'nullable|string|max:50',
            'passengers.*.document_number' => 'nullable|string|max:100',

            'extras_json' => 'nullable|string',
        ]);

        $dep = Departure::query()->find((int) $data['departure_id']);
        if (! $dep || (int) $dep->voyage_id !== (int) $voyage->id) {
            throw ValidationException::withMessages([
                'departure_id' => ['Départ incohérent pour ce voyage.'],
            ]);
        }

        $createPayload = [
            'tour_id' => (int) $voyage->id,
            'voyage_id' => (int) $voyage->id,
            'departure_id' => (int) $dep->id,
            'travel_date_id' => (int) $data['travel_date_id'],
            'client_mode' => (string) $data['client_mode'],
            'client_external_id' => $data['client_external_id'] ?? null,
            'client_first_name' => $data['client_first_name'] ?? null,
            'client_last_name' => $data['client_last_name'] ?? null,
            'client_phone' => $data['client_phone'] ?? null,
            'client_email' => $data['client_email'] ?? null,
            'client_document_type' => $data['client_document_type'] ?? null,
            'client_document_number' => $data['client_document_number'] ?? null,
            'passengers' => $data['passengers'] ?? [],
            'status' => Reservation::STATUS_PENDING,
            'wp_tour_post_id' => $voyage->wp_post_id ? (int) $voyage->wp_post_id : null,
            'catalog_source_code' => 'front_kiosk',
            'extras_json' => $data['extras_json'] ?? null,
        ];

        if (! empty($data['departure_hotel_room_id'])) {
            $createPayload['hotel_rooms'] = [[
                'departure_hotel_room_id' => (int) $data['departure_hotel_room_id'],
                'room_count' => 1,
            ]];
        } else {
            $createPayload['hotel_rooms'] = [];
        }

        $reservation = $reservationService->create($createPayload);

        // Same as admin reservation store: persist extras lines (per traveler) from extras_json.
        $extrasPayload = [];
        if (! empty($data['extras_json'])) {
            $decoded = json_decode((string) $data['extras_json'], true);
            $extrasPayload = is_array($decoded) ? $decoded : [];
        }

        foreach ($extrasPayload as $extra) {
            if (! is_array($extra)) {
                continue;
            }
            $name = trim((string) ($extra['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            ReservationExtra::query()->create([
                'reservation_id' => $reservation->id,
                'name' => $name,
                'price' => isset($extra['price']) ? (float) $extra['price'] : 0,
                'passenger_key' => isset($extra['pax']) && $extra['pax'] !== '' ? (string) $extra['pax'] : null,
            ]);
        }

        return response()->json([
            'ok' => true,
            'reservation_id' => (int) $reservation->id,
            'status' => (string) $reservation->status,
        ]);
    }
}
