<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Reservation;
use App\Models\Voyage;
use App\Models\VoyageDeparturePlace;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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
            'client_first_name' => 'required|string|max:100',
            'client_last_name' => 'required|string|max:100',
            'client_phone' => 'required|string|max:50',
            'client_email' => 'nullable|email|max:190',
            'passengers' => 'nullable|array',
            'passengers.*.first_name' => 'nullable|string|max:100',
            'passengers.*.last_name' => 'nullable|string|max:100',
            'passengers.*.type' => 'nullable|in:adult,child',
            'passengers.*.birth_date' => 'nullable|date',
            'extras_json' => 'nullable|string',
            'notes' => 'nullable|string|max:4000',
            'room_preference' => 'nullable|string|max:1000',
            'departure_place_id' => 'nullable|integer',
            'departure_place_name' => 'nullable|string|max:190',
            'accept_terms' => 'accepted',
        ]);

        $departure = Departure::query()->find((int) $data['departure_id']);
        if (! $departure || (int) $departure->voyage_id !== (int) $voyage->id) {
            throw ValidationException::withMessages([
                'departure_id' => ['Départ incohérent pour ce voyage.'],
            ]);
        }

        $passengers = collect($data['passengers'] ?? [])
            ->filter(function ($passenger) {
                if (! is_array($passenger)) {
                    return false;
                }

                return trim((string) ($passenger['first_name'] ?? '')) !== ''
                    || trim((string) ($passenger['last_name'] ?? '')) !== '';
            })
            ->map(function (array $passenger) {
                return [
                    'first_name' => trim((string) ($passenger['first_name'] ?? '')),
                    'last_name' => trim((string) ($passenger['last_name'] ?? '')),
                    'type' => ($passenger['type'] ?? 'adult') === 'child' ? 'child' : 'adult',
                    'birth_date' => ! empty($passenger['birth_date']) ? $passenger['birth_date'] : null,
                ];
            })
            ->values()
            ->all();

        $extrasPayload = [];
        if (! empty($data['extras_json'])) {
            $decoded = json_decode((string) $data['extras_json'], true);
            $extrasPayload = is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
        }

        $adultsCount = 1 + collect($passengers)->where('type', 'adult')->count();
        $childrenCount = collect($passengers)->where('type', 'child')->count();
        $travelersCount = max(1, $adultsCount + $childrenCount);

        $placePrice = 0.0;
        if (! empty($data['departure_place_id'])) {
            $placePrice = (float) (VoyageDeparturePlace::query()
                ->where('voyage_id', $voyage->id)
                ->whereKey((int) $data['departure_place_id'])
                ->value('price') ?? 0);
        }

        $unitPrice = (float) ($departure->sale_price ?: $departure->base_price ?: $voyage->price_from ?: 0) + $placePrice;
        $totalBase = round($unitPrice * $travelersCount, 2);

        $notes = [];
        if (! empty($data['notes'])) {
            $notes[] = trim((string) $data['notes']);
        }
        if (! empty($data['room_preference'])) {
            $notes[] = 'Préférence chambre / hébergement : '.trim((string) $data['room_preference']);
        }
        if (! empty($data['departure_place_name'])) {
            $notes[] = 'Ville de départ souhaitée : '.trim((string) $data['departure_place_name']);
        }

        $reservation = $reservationService->create([
            'tour_id' => (int) $voyage->id,
            'voyage_id' => (int) $voyage->id,
            'departure_id' => (int) $departure->id,
            'travel_date_id' => (int) $data['travel_date_id'],
            'client_mode' => 'new',
            'client_first_name' => trim((string) $data['client_first_name']),
            'client_last_name' => trim((string) $data['client_last_name']),
            'client_phone' => trim((string) $data['client_phone']),
            'client_email' => $data['client_email'] ?? null,
            'passengers' => $passengers,
            'status' => Reservation::STATUS_PENDING,
            'dossier_status' => Reservation::DOSSIER_PENDING,
            'payment_status' => Reservation::PAYMENT_STATUS_NON_PAID,
            'passengers_count' => $travelersCount,
            'adults_count' => $adultsCount,
            'children_count' => $childrenCount,
            'infants_count' => 0,
            'total_base' => $totalBase,
            'unit_price_before_discount' => $unitPrice,
            'unit_price_after_discount' => $unitPrice,
            'notes' => implode("\n\n", array_filter($notes)),
            'wp_tour_post_id' => $voyage->wp_post_id ? (int) $voyage->wp_post_id : null,
            'channel' => 'client',
            'catalog_source_code' => 'front_public_2step',
            'extras_payload' => $extrasPayload,
            'hotel_rooms' => [],
        ]);

        $reference = (string) ($reservation->dossier_number ?: 'RES-'.$reservation->id);

        return response()->json([
            'ok' => true,
            'reservation_id' => (int) $reservation->id,
            'status' => (string) $reservation->status,
            'reference' => $reference,
            'redirect_url' => route('front.voyages.reserve.success', [
                'slug' => $voyage->slug,
                'reference' => $reference,
            ]),
        ]);
    }

    public function success(string $slug, string $reference): View
    {
        $voyage = Voyage::query()->where('slug', $slug)->firstOrFail();
        $reservationQuery = Reservation::query()->where('voyage_id', $voyage->id);
        $reservation = (clone $reservationQuery)
            ->where('dossier_number', $reference)
            ->first();

        if (! $reservation && preg_match('/^RES-(\d+)$/', $reference, $matches)) {
            $reservation = (clone $reservationQuery)->whereKey((int) $matches[1])->first();
        }

        abort_if(! $reservation, 404);

        return view('front.voyages.reservation-success', [
            'voyage' => $voyage,
            'reservation' => $reservation,
        ]);
    }
}
