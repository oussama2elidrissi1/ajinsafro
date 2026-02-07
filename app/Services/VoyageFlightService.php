<?php

namespace App\Services;

use App\Models\Airline;
use App\Models\VoyageFlight;
use Illuminate\Support\Carbon;

class VoyageFlightService
{
    /**
     * Sync flights for a voyage. Max 2 flights. Exactly one is_default when 2 exist.
     *
     * @param int $voyageId Tour/voyage ID (e.g. WP post ID)
     * @param array $flights Array of flight data: [ ['airline_id'=>, 'cabin_class'=>, ...], ... ]
     */
    public function syncFlights(int $voyageId, array $flights): void
    {
        $flights = array_values(array_filter($flights, function ($f) {
            return !empty($f['airline_id']) || !empty($f['flight_number']) || !empty($f['departure_airport']);
        }));

        if (count($flights) > 2) {
            $flights = array_slice($flights, 0, 2);
        }

        $existing = VoyageFlight::where('voyage_id', $voyageId)->orderBy('sort_order')->get();

        foreach ($flights as $index => $payload) {
            $sortOrder = $index + 1;
            $isDefault = $this->resolveDefault($flights, $index);
            $airlineId = isset($payload['airline_id']) && $payload['airline_id'] !== '' ? (int) $payload['airline_id'] : null;
            $cabinClass = in_array($payload['cabin_class'] ?? '', ['economy', 'premium_economy', 'business', 'first'], true)
                ? $payload['cabin_class']
                : VoyageFlight::CABIN_ECONOMY;

            $flight = $existing->firstWhere('sort_order', $sortOrder);

            $data = [
                'voyage_id' => $voyageId,
                'airline_id' => $airlineId,
                'cabin_class' => $cabinClass,
                'flight_number' => $payload['flight_number'] ?? null,
                'departure_airport' => $payload['departure_airport'] ?? null,
                'arrival_airport' => $payload['arrival_airport'] ?? null,
                'departure_at' => $this->parseDateTime($payload['departure_at'] ?? null),
                'arrival_at' => $this->parseDateTime($payload['arrival_at'] ?? null),
                'baggage' => $payload['baggage'] ?? null,
                'price' => isset($payload['price']) && $payload['price'] !== '' ? (float) $payload['price'] : null,
                'currency' => !empty($payload['currency']) ? substr($payload['currency'], 0, 3) : 'MAD',
                'is_default' => $isDefault,
                'sort_order' => $sortOrder,
            ];

            if ($flight) {
                $flight->update($data);
            } else {
                VoyageFlight::create($data);
            }
        }

        $keepSortOrders = array_map(fn ($i) => $i + 1, array_keys($flights));
        VoyageFlight::where('voyage_id', $voyageId)->whereNotIn('sort_order', $keepSortOrders)->delete();

        $this->ensureSingleDefault($voyageId);
    }

    protected function resolveDefault(array $flights, int $index): bool
    {
        $explicitDefault = null;
        foreach ($flights as $i => $f) {
            if (!empty($f['is_default']) && (string) $f['is_default'] === '1') {
                $explicitDefault = $i;
                break;
            }
        }
        if (count($flights) === 1) {
            return true;
        }
        return $explicitDefault === $index;
    }

    protected function ensureSingleDefault(int $voyageId): void
    {
        $flights = VoyageFlight::where('voyage_id', $voyageId)->orderBy('sort_order')->get();
        if ($flights->isEmpty()) {
            return;
        }
        $defaultCount = $flights->where('is_default', true)->count();
        if ($defaultCount === 0) {
            $flights->first()->update(['is_default' => true]);
        } elseif ($defaultCount > 1) {
            $first = $flights->first();
            VoyageFlight::where('voyage_id', $voyageId)->where('id', '!=', $first->id)->update(['is_default' => false]);
        }
    }

    protected function parseDateTime($value): ?Carbon
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

    /**
     * Get flights for a voyage (for edit form).
     *
     * @param int $voyageId
     * @return \Illuminate\Support\Collection<int, VoyageFlight>
     */
    public function getFlightsForVoyage(int $voyageId)
    {
        return VoyageFlight::where('voyage_id', $voyageId)->with('airline')->orderBy('sort_order')->get();
    }
}
