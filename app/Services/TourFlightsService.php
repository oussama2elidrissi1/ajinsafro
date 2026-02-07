<?php

namespace App\Services;

use App\Models\AjAirline;
use App\Models\TourFlight;
use Carbon\Carbon;

class TourFlightsService
{
    /**
     * Sync tour flights: exactly one outbound (Jour 1) and one inbound (last day).
     * Payload keys: flights.outbound, flights.inbound (each optional).
     *
     * @param int $tourId wp_posts.ID
     * @param array $flights ['outbound' => [...], 'inbound' => [...]]
     */
    public function syncFlights(int $tourId, array $flights): void
    {
        foreach (['outbound', 'inbound'] as $flightType) {
            $payload = $flights[$flightType] ?? [];
            $isEmpty = empty($payload['airline_id']) && empty($payload['from_city']) && empty($payload['to_city'])
                && empty($payload['depart_date']) && empty($payload['arrive_date']);

            $flight = TourFlight::where('tour_id', $tourId)->where('flight_type', $flightType)->first();

            if ($isEmpty) {
                if ($flight) {
                    $flight->update([
                        'airline_id' => null,
                        'cabin_class' => TourFlight::CABIN_ECONOMY,
                        'from_city' => null,
                        'to_city' => null,
                        'depart_date' => null,
                        'depart_time' => null,
                        'arrive_date' => null,
                        'arrive_time' => null,
                        'baggage_cabin_kg' => null,
                        'baggage_checkin_kg' => null,
                        'is_tentative' => true,
                        'notes' => null,
                    ]);
                }
                continue;
            }

            $airlineId = isset($payload['airline_id']) && $payload['airline_id'] !== '' ? (int) $payload['airline_id'] : null;
            $cabinClass = in_array($payload['cabin_class'] ?? '', ['economy', 'business', 'first'], true)
                ? $payload['cabin_class']
                : TourFlight::CABIN_ECONOMY;

            $data = [
                'tour_id' => $tourId,
                'flight_type' => $flightType,
                'airline_id' => $airlineId,
                'cabin_class' => $cabinClass,
                'from_city' => $payload['from_city'] ?? null,
                'to_city' => $payload['to_city'] ?? null,
                'depart_date' => $this->parseDate($payload['depart_date'] ?? null),
                'depart_time' => $this->parseTime($payload['depart_time'] ?? null),
                'arrive_date' => $this->parseDate($payload['arrive_date'] ?? null),
                'arrive_time' => $this->parseTime($payload['arrive_time'] ?? null),
                'baggage_cabin_kg' => isset($payload['baggage_cabin_kg']) && $payload['baggage_cabin_kg'] !== '' ? (int) $payload['baggage_cabin_kg'] : null,
                'baggage_checkin_kg' => isset($payload['baggage_checkin_kg']) && $payload['baggage_checkin_kg'] !== '' ? (int) $payload['baggage_checkin_kg'] : null,
                'is_tentative' => !empty($payload['is_tentative']) && (string) $payload['is_tentative'] === '1',
                'notes' => isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ];

            if ($flight) {
                $flight->update($data);
            } else {
                TourFlight::create($data);
            }
        }
    }

    protected function parseDate($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function parseTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim((string) $value);
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value)) {
            return $value;
        }
        try {
            $dt = Carbon::parse($value);
            return $dt->format('H:i');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get flights for a tour (for admin edit form). Keyed by flight_type.
     */
    public function getFlightsForTour(int $tourId)
    {
        return TourFlight::where('tour_id', $tourId)->with('airline')->orderBy('flight_type')->get()->keyBy('flight_type');
    }
}
