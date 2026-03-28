<?php

namespace App\Services;

use App\Models\Departure;
use App\Models\Reservation;
use App\Models\TourHotel;
use App\Models\TravelDate;
use App\Models\User;
use App\Models\Voyage;
use App\Models\VoyageFlight;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Construit les lignes du catalogue « workspace » (packages / vols / hébergements liés aux voyages).
 */
class ReservationWorkspaceCatalogService
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function buildRows(User $user): Collection
    {
        $rows = collect();
        $today = Carbon::today();

        $voyages = Voyage::query()
            ->where(function ($q) {
                $q->where('status', 'publish')->orWhere('status', 'actif');
            })
            ->with([
                'departures' => fn ($q) => $q->where('status', Departure::STATUS_OPEN)
                    ->whereDate('start_date', '>=', $today)
                    ->orderBy('start_date'),
            ])
            ->orderBy('name')
            ->limit(200)
            ->get();

        foreach ($voyages as $voyage) {
            $departures = $voyage->departures;
            if ($departures->isEmpty()) {
                $departures = collect([(object) ['id' => null, 'start_date' => null]]);
            }

            foreach ($departures as $departure) {
                $departureId = $departure->id ?? null;
                $startDate = $departure->start_date ?? null;
                $travelDateId = $this->resolveTravelDateId($voyage, $startDate);

                $stats = $this->reservationStats($user, (int) $voyage->id, $travelDateId);

                $rows->push([
                    'type' => 'package',
                    'code' => 'PKG-'.$voyage->id.($departureId ? '-'.$departureId : ''),
                    'name' => $voyage->name,
                    'subtitle' => null,
                    'voyage_id' => (int) $voyage->id,
                    'departure_id' => $departureId,
                    'flight_id' => null,
                    'tour_hotel_wp_id' => null,
                    'travel_date_id' => $travelDateId,
                    'departure_date' => $startDate,
                    'stats' => $stats,
                ]);
            }
        }

        $flights = VoyageFlight::query()
            ->with('voyage')
            ->whereNotNull('departure_date')
            ->whereDate('departure_date', '>=', $today)
            ->orderBy('departure_date')
            ->limit(150)
            ->get();

        foreach ($flights as $flight) {
            $voyage = $flight->voyage;
            if (! $voyage) {
                continue;
            }
            $stats = $this->reservationStats($user, (int) $voyage->id, null);
            $label = trim(($flight->flight_number ?: 'Vol').' · '.$flight->from_label.' → '.$flight->to_label);
            $rows->push([
                'type' => 'vol',
                'code' => 'VOL-'.$flight->id,
                'name' => $label,
                'subtitle' => $voyage->name,
                'voyage_id' => (int) $voyage->id,
                'departure_id' => null,
                'flight_id' => (int) $flight->id,
                'tour_hotel_wp_id' => null,
                'travel_date_id' => $this->resolveTravelDateId($voyage, $flight->departure_date),
                'departure_date' => $flight->departure_date,
                'stats' => $stats,
            ]);
        }

        foreach ($voyages as $voyage) {
            if (! $voyage->wp_post_id) {
                continue;
            }
            $hotels = TourHotel::getAllForTour((int) $voyage->wp_post_id);
            $firstDep = $voyage->departures->first();
            $startDate = $firstDep?->start_date;
            foreach ($hotels as $hotel) {
                $stats = $this->reservationStats($user, (int) $voyage->id, null);
                $rows->push([
                    'type' => 'hebergement',
                    'code' => 'HOT-'.$hotel->id,
                    'name' => $hotel->hotel_name ?: 'Hébergement',
                    'subtitle' => $voyage->name,
                    'voyage_id' => (int) $voyage->id,
                    'departure_id' => $firstDep?->id,
                    'flight_id' => null,
                    'tour_hotel_wp_id' => (int) $hotel->id,
                    'travel_date_id' => $this->resolveTravelDateId($voyage, $startDate),
                    'departure_date' => $startDate,
                    'stats' => $stats,
                ]);
            }
        }

        return $rows->unique(fn ($r) => $r['type'].'-'.$r['code'])->values();
    }

    /**
     * @return array{validee: int, en_cours: int, annulee: int}
     */
    public function reservationStats(User $user, int $tourId, ?int $travelDateId): array
    {
        $q = Reservation::query()->where('tour_id', $tourId);
        if ($travelDateId) {
            $q->where('travel_date_id', $travelDateId);
        }
        $this->branchScope->scopeReservations($q, $user);
        $this->branchScope->constrainReservationQueryForPortalUser($q, $user);

        return [
            'validee' => (clone $q)->where('status', Reservation::STATUS_VALIDEE)->count(),
            'en_cours' => (clone $q)->where('status', Reservation::STATUS_EN_COURS)->count(),
            'annulee' => (clone $q)->where('status', Reservation::STATUS_ANNULEE)->count(),
        ];
    }

    private function resolveTravelDateId(Voyage $voyage, $date): ?int
    {
        if (! $voyage->wp_post_id || $date === null) {
            return null;
        }
        $d = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return TravelDate::query()
            ->where('travel_id', (int) $voyage->wp_post_id)
            ->whereDate('date', $d)
            ->where('is_active', true)
            ->value('id');
    }
}
