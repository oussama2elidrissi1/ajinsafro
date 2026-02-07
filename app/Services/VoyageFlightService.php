<?php

namespace App\Services;

use App\Models\Voyage;
use App\Models\VoyageFlight;
use Illuminate\Support\Facades\DB;

class VoyageFlightService
{
    /**
     * Sync voyage flights (outbound + inbound). Create/update or delete per direction.
     * voyage_id = Laravel Voyage id.
     * Also syncs to WP aj_tour_flights so the front plugin can read from DB (no API).
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

        $voyage = Voyage::find($voyageId);
        if ($voyage && $voyage->wp_post_id) {
            $this->syncFlightsToWp($voyageId, (int) $voyage->wp_post_id);
        }
    }

    /**
     * Sync voyage_flights to WP table aj_tour_flights (tour_id = wp_post_id).
     * So the WP plugin can read Vol Aller / Vol Retour directly from DB.
     */
    public function syncFlightsToWp(int $voyageId, int $wpPostId): void
    {
        try {
            $wp = DB::connection('wp');
            $table = 'aj_tour_flights';

            foreach (['outbound', 'inbound'] as $direction) {
                $flight = VoyageFlight::where('voyage_id', $voyageId)->where('direction', $direction)->first();
                $depDate = $flight && $flight->departure_date ? $flight->departure_date->format('Y-m-d') : null;

                $row = [
                    'tour_id' => $wpPostId,
                    'flight_type' => $direction,
                    'airline_id' => $flight ? $flight->airline_id : null,
                    'cabin_class' => $flight ? ($flight->cabin ?? 'economy') : 'economy',
                    'from_city' => $flight ? $flight->from_city : null,
                    'to_city' => $flight ? $flight->to_city : null,
                    'depart_date' => $depDate,
                    'depart_time' => null,
                    'arrive_date' => $depDate,
                    'arrive_time' => null,
                    'baggage_cabin_kg' => $flight ? $flight->baggage_cabin_kg : null,
                    'baggage_checkin_kg' => $flight ? $flight->baggage_checkin_kg : null,
                    'is_tentative' => $flight ? $flight->is_tentative : false,
                    'notes' => null,
                    'updated_at' => now(),
                ];

                $existing = $wp->table($table)->where('tour_id', $wpPostId)->where('flight_type', $direction)->first();

                if ($existing) {
                    if ($flight) {
                        $wp->table($table)->where('id', $existing->id)->update($row);
                    } else {
                        $wp->table($table)->where('id', $existing->id)->delete();
                    }
                } elseif ($flight) {
                    $row['created_at'] = now();
                    $wp->table($table)->insert($row);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('VoyageFlightService::syncFlightsToWp failed', [
                'voyage_id' => $voyageId,
                'wp_post_id' => $wpPostId,
                'message' => $e->getMessage(),
            ]);
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
