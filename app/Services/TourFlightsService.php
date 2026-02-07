<?php

namespace App\Services;

use App\Models\AjAirline;
use App\Models\TourFlight;
use Carbon\Carbon;

class TourFlightsService
{
    /**
     * Sync tour flights (max 2 segments). Exactly one is_default when 2 exist.
     *
     * @param int $tourId wp_posts.ID
     * @param array $flights [ ['airline_id'=>, 'cabin_class'=>, 'depart_date'=>, ...], ... ]
     */
    public function syncFlights(int $tourId, array $flights): void
    {
        $flights = array_values(array_filter($flights, function ($f) {
            return ! empty($f['airline_id']) || ! empty($f['flight_number']) || ! empty($f['depart_airport']) || ! empty($f['depart_city']);
        }));

        if (count($flights) > 2) {
            $flights = array_slice($flights, 0, 2);
        }

        $existing = TourFlight::where('tour_id', $tourId)->orderBy('segment_number')->get();

        foreach ($flights as $index => $payload) {
            $segmentNumber = $index + 1;
            $isDefault = $this->resolveDefault($flights, $index);
            $airlineId = isset($payload['airline_id']) && $payload['airline_id'] !== '' ? (int) $payload['airline_id'] : null;
            $cabinClass = in_array($payload['cabin_class'] ?? '', ['economy', 'business', 'first'], true)
                ? $payload['cabin_class']
                : TourFlight::CABIN_ECONOMY;

            $flight = $existing->firstWhere('segment_number', $segmentNumber);

            $data = [
                'tour_id' => $tourId,
                'segment_number' => $segmentNumber,
                'airline_id' => $airlineId,
                'cabin_class' => $cabinClass,
                'flight_number' => $payload['flight_number'] ?? null,
                'depart_date' => $this->parseDate($payload['depart_date'] ?? null),
                'depart_city' => $payload['depart_city'] ?? null,
                'depart_airport' => $payload['depart_airport'] ?? null,
                'arrive_date' => $this->parseDate($payload['arrive_date'] ?? null),
                'arrive_city' => $payload['arrive_city'] ?? null,
                'arrive_airport' => $payload['arrive_airport'] ?? null,
                'cabin_baggage' => $payload['cabin_baggage'] ?? null,
                'checkin_baggage' => $payload['checkin_baggage'] ?? null,
                'is_tentative' => ! empty($payload['is_tentative']) && (string) $payload['is_tentative'] === '1',
                'is_default' => $isDefault,
                'sort_order' => $segmentNumber,
            ];

            if ($flight) {
                $flight->update($data);
            } else {
                TourFlight::create($data);
            }
        }

        $keepSegments = array_map(fn ($i) => $i + 1, array_keys($flights));
        TourFlight::where('tour_id', $tourId)->whereNotIn('segment_number', $keepSegments)->delete();

        $this->ensureSingleDefault($tourId);
    }

    protected function resolveDefault(array $flights, int $index): bool
    {
        $explicitDefault = null;
        foreach ($flights as $i => $f) {
            if (! empty($f['is_default']) && (string) $f['is_default'] === '1') {
                $explicitDefault = $i;
                break;
            }
        }
        if (count($flights) === 1) {
            return true;
        }
        return $explicitDefault === $index;
    }

    protected function ensureSingleDefault(int $tourId): void
    {
        $flights = TourFlight::where('tour_id', $tourId)->orderBy('segment_number')->get();
        if ($flights->isEmpty()) {
            return;
        }
        $defaultCount = $flights->where('is_default', true)->count();
        if ($defaultCount === 0) {
            $flights->first()->update(['is_default' => true]);
        } elseif ($defaultCount > 1) {
            $first = $flights->first();
            TourFlight::where('tour_id', $tourId)->where('id', '!=', $first->id)->update(['is_default' => false]);
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

    /**
     * Get flights for a tour (for admin edit form).
     */
    public function getFlightsForTour(int $tourId)
    {
        return TourFlight::where('tour_id', $tourId)->with('airline')->orderBy('segment_number')->get();
    }
}
