<?php

namespace App\Services;

use App\Models\Departure;
use App\Models\DepartureHotel;
use App\Models\Reservation;
use App\Models\StockMovement;
use App\Models\TravelDate;
use App\Models\Voyage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class VoyageAvailabilityService
{
    /**
     * Synchronize Laravel departures from WP travel dates for a voyage.
     *
     * Rules:
     * - WP active travel dates are the source of truth for operational departures.
     * - Missing departures are created automatically.
     * - Duplicate departures (same date/wp id) are merged.
     * - Departures not present in active WP dates are archived (closed), not deleted.
     */
    public function syncFromWpDates(Voyage $voyage, array $context = []): Collection
    {
        $wpPostId = (int) ($voyage->wp_post_id ?? 0);
        if ($wpPostId <= 0) {
            return $this->departuresQuery($voyage)->get();
        }

        $travelDates = TravelDate::query()
            ->where('travel_id', $wpPostId)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        return $this->syncFromTravelDates($voyage, $travelDates, $context);
    }

    public function syncFromTravelDates(Voyage $voyage, Collection $travelDates, array $context = []): Collection
    {
        $durationDays = max(1, (int) ($context['duration_days'] ?? 1));
        $preferredDepartureId = isset($context['preferred_departure_id']) ? (int) $context['preferred_departure_id'] : 0;

        $today = Carbon::today('Africa/Casablanca');
        $activeTravelDates = $travelDates
            ->filter(fn ($td) => ($td instanceof TravelDate) && (bool) ($td->is_active ?? false) && $td->date && ! Carbon::parse($td->date)->lt($today))
            ->sortBy('date')
            ->values();

        $keptDepartureIds = [];
        $activeDateKeys = [];
        $activeWpTravelDateIds = [];

        foreach ($activeTravelDates as $travelDate) {
            $start = Carbon::parse($travelDate->date);
            $startDate = $start->format('Y-m-d');
            $endDate = $durationDays > 1 ? $start->copy()->addDays($durationDays - 1)->format('Y-m-d') : null;
            $wpTravelDateId = (int) ($travelDate->id ?? 0);

            $activeDateKeys[] = $startDate;
            if ($wpTravelDateId > 0) {
                $activeWpTravelDateIds[] = $wpTravelDateId;
            }

            $canonical = $this->resolveCanonicalDeparture($voyage, $startDate, $wpTravelDateId, $preferredDepartureId);
            if (! $canonical) {
                $canonical = new Departure([
                    'voyage_id' => (int) $voyage->id,
                ]);
            }

            $status = $this->resolveStatusForActiveDate($canonical);
            $totalCapacity = max(0, (int) ($travelDate->seats ?? 0));
            $reservedCapacity = max(0, (int) ($canonical->reserved_capacity ?? 0));
            $availableCapacity = max(0, $totalCapacity - $reservedCapacity);

            $canonical->fill([
                'voyage_id' => (int) $voyage->id,
                'wp_travel_date_id' => $wpTravelDateId > 0 ? $wpTravelDateId : null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
                'total_capacity' => $totalCapacity,
                'available_capacity' => $availableCapacity,
            ]);

            $canonical->notes = $this->removeArchiveMarker((string) ($canonical->notes ?? ''));
            $canonical->save();

            $this->mergeDuplicateDeparturesIntoCanonical($voyage, $canonical, $startDate, $wpTravelDateId, $preferredDepartureId);
            $keptDepartureIds[] = (int) $canonical->id;
        }

        $this->archiveMissingDepartures($voyage, $keptDepartureIds, $activeDateKeys, $activeWpTravelDateIds);

        Log::info('[AVAILABILITY_SYNC_SERVICE] departures synchronized', [
            'voyage_id' => (int) $voyage->id,
            'wp_post_id' => (int) ($voyage->wp_post_id ?? 0),
            'wp_dates_total' => $travelDates->count(),
            'wp_dates_active' => count($activeDateKeys),
            'departures_kept' => count($keptDepartureIds),
        ]);

        return $this->departuresQuery($voyage)->get();
    }

    private function departuresQuery(Voyage $voyage)
    {
        return Departure::query()
            ->where('voyage_id', (int) $voyage->id)
            ->orderBy('start_date')
            ->orderBy('id');
    }

    private function resolveCanonicalDeparture(Voyage $voyage, string $startDate, int $wpTravelDateId, int $preferredDepartureId = 0): ?Departure
    {
        $query = Departure::query()
            ->where('voyage_id', (int) $voyage->id)
            ->where(function ($q) use ($startDate, $wpTravelDateId) {
                $q->whereDate('start_date', $startDate);
                if ($wpTravelDateId > 0) {
                    $q->orWhere('wp_travel_date_id', $wpTravelDateId);
                }
            });

        if ($preferredDepartureId > 0) {
            $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$preferredDepartureId]);
        }

        return $query
            ->orderByRaw('CASE WHEN wp_travel_date_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('id')
            ->first();
    }

    private function resolveStatusForActiveDate(?Departure $departure): string
    {
        if (! $departure) {
            return Departure::STATUS_OPEN;
        }

        $current = (string) ($departure->status ?? '');
        if ($current === '') {
            return Departure::STATUS_OPEN;
        }

        if (in_array($current, [Departure::STATUS_CLOSED, Departure::STATUS_CANCELED, Departure::STATUS_CANCELLED], true)) {
            $notes = (string) ($departure->notes ?? '');
            if (str_contains($notes, '[WP_SYNC_ARCHIVED]')) {
                return Departure::STATUS_OPEN;
            }
        }

        return $current;
    }

    private function mergeDuplicateDeparturesIntoCanonical(Voyage $voyage, Departure $canonical, string $startDate, int $wpTravelDateId, int $preferredDepartureId = 0): void
    {
        $dupes = Departure::query()
            ->where('voyage_id', (int) $voyage->id)
            ->where('id', '!=', (int) $canonical->id)
            ->where(function ($q) use ($startDate, $wpTravelDateId) {
                $q->whereDate('start_date', $startDate);
                if ($wpTravelDateId > 0) {
                    $q->orWhere('wp_travel_date_id', $wpTravelDateId);
                }
            })
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$preferredDepartureId])
            ->orderBy('id')
            ->get();

        foreach ($dupes as $dup) {
            $this->mergeDepartureReferences($dup, $canonical);
        }
    }

    private function mergeDepartureReferences(Departure $from, Departure $into): void
    {
        if ((int) $from->id === (int) $into->id) {
            return;
        }

        DepartureHotel::query()->where('departure_id', (int) $from->id)->update(['departure_id' => (int) $into->id]);
        Reservation::query()->where('departure_id', (int) $from->id)->update(['departure_id' => (int) $into->id]);

        if (Schema::hasTable('stock_movements')) {
            StockMovement::query()->where('departure_id', (int) $from->id)->update(['departure_id' => (int) $into->id]);
        }

        $from->delete();
    }

    private function archiveMissingDepartures(Voyage $voyage, array $keptDepartureIds, array $activeDateKeys, array $activeWpTravelDateIds): void
    {
        $query = Departure::query()->where('voyage_id', (int) $voyage->id);

        if (! empty($keptDepartureIds)) {
            $query->whereNotIn('id', $keptDepartureIds);
        }

        $toArchive = $query->get();

        foreach ($toArchive as $departure) {
            $startDate = $departure->start_date ? Carbon::parse((string) $departure->start_date)->format('Y-m-d') : null;
            $wpTravelDateId = (int) ($departure->wp_travel_date_id ?? 0);

            $stillActiveByDate = $startDate !== null && in_array($startDate, $activeDateKeys, true);
            $stillActiveByWpId = $wpTravelDateId > 0 && in_array($wpTravelDateId, $activeWpTravelDateIds, true);

            if ($stillActiveByDate || $stillActiveByWpId) {
                continue;
            }

            if ($departure->status !== Departure::STATUS_CLOSED) {
                $departure->status = Departure::STATUS_CLOSED;
            }

            $note = trim((string) ($departure->notes ?? ''));
            if (! str_contains($note, '[WP_SYNC_ARCHIVED]')) {
                $timestamp = Carbon::now()->format('Y-m-d H:i:s');
                $marker = '[WP_SYNC_ARCHIVED] Auto-closed because WP date is inactive or removed at '.$timestamp;
                $note = $note === '' ? $marker : ($note."\n".$marker);
                $departure->notes = $note;
            }

            $departure->save();
        }
    }

    private function removeArchiveMarker(string $notes): ?string
    {
        $lines = preg_split('/\r\n|\r|\n/', $notes) ?: [];
        $kept = [];
        foreach ($lines as $line) {
            if (! str_contains($line, '[WP_SYNC_ARCHIVED]')) {
                $kept[] = $line;
            }
        }
        $out = trim(implode("\n", $kept));

        return $out === '' ? null : $out;
    }
}
