<?php

namespace App\Services;

use App\Models\Voyage;
use App\Models\VoyageFlight;

class VoyageFlightService
{
    /**
     * Sync voyage flights (outbound + inbound). Create/update or delete per direction.
     * voyage_id = Laravel Voyage id.
     */
    public function syncFlights(int $voyageId, array $flights): void
    {
        foreach (['outbound', 'inbound'] as $direction) {
            $payload = $flights[$direction] ?? [];
            $filled = !empty($payload['airline_id']) || !empty($payload['from_city']) || !empty($payload['to_city'])
                || !empty($payload['departure_date']) || !empty($payload['flight_number']);

            $flight = VoyageFlight::where('voyage_id', $voyageId)->where('direction', $direction)->first();

            if (!$filled) {
                if ($flight) {
                    $flight->delete();
                }
                continue;
            }

            $data = [
                'voyage_id' => $voyageId,
                'direction' => $direction,
                'airline_id' => isset($payload['airline_id']) && $payload['airline_id'] !== '' ? (int) $payload['airline_id'] : null,
                'cabin' => in_array($payload['cabin'] ?? '', ['economy', 'business', 'first'], true) ? $payload['cabin'] : VoyageFlight::CABIN_ECONOMY,
                'flight_number' => isset($payload['flight_number']) ? trim((string) $payload['flight_number']) : null,
                'from_city' => isset($payload['from_city']) ? trim((string) $payload['from_city']) : null,
                'to_city' => isset($payload['to_city']) ? trim((string) $payload['to_city']) : null,
                'departure_date' => $this->parseDate($payload['departure_date'] ?? null),
                'baggage_cabin_kg' => isset($payload['baggage_cabin_kg']) && $payload['baggage_cabin_kg'] !== '' ? (int) $payload['baggage_cabin_kg'] : null,
                'baggage_checkin_kg' => isset($payload['baggage_checkin_kg']) && $payload['baggage_checkin_kg'] !== '' ? (int) $payload['baggage_checkin_kg'] : null,
                'is_tentative' => !empty($payload['is_tentative']) && (string) $payload['is_tentative'] === '1',
            ];

            if ($flight) {
                $flight->update($data);
            } else {
                VoyageFlight::create($data);
            }
        }
    }

    protected function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            $d = \Carbon\Carbon::parse($value);
            return $d->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get flights for a voyage (for admin form). Keyed by direction.
     */
    public function getFlightsForVoyage(int $voyageId)
    {
        return VoyageFlight::where('voyage_id', $voyageId)->with('airline')->orderBy('direction')->get()->keyBy('direction');
    }
}
