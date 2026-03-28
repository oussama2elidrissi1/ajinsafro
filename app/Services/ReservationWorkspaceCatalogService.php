<?php

namespace App\Services;

use App\Models\Departure;
use App\Models\Reservation;
use App\Models\TourHotel;
use App\Models\TravelDate;
use App\Models\User;
use App\Models\Voyage;
use App\Models\VoyageFlight;
use App\Models\Wp\WpPost;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Catalogue workspace : une ligne « package » par tour WordPress (même liste que /admin/circuits/voyages),
 * enrichie par la fiche Laravel {@see Voyage} quand `wp_post_id` correspond.
 * Vols & hébergements : uniquement pour les voyages Laravel liés.
 */
class ReservationWorkspaceCatalogService
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    /**
     * meta inclut notamment : wp_tour_count, laravel_voyage_matched, package_rows, vol_rows, hebergement_rows, total_rows.
     *
     * @return array{rows: Collection<int, array<string, mixed>>, meta: array<string, int|bool>}
     */
    public function buildRows(User $user): array
    {
        $today = Carbon::today();
        $rows = collect();
        $meta = [
            'wp_connection_failed' => false,
            'wp_tour_count' => 0,
            'laravel_voyage_matched' => 0,
            'package_rows' => 0,
            'vol_rows' => 0,
            'hebergement_rows' => 0,
            'total_rows' => 0,
        ];

        try {
            $wpTours = AdminWpTourCatalogQuery::baseQuery()->get();
        } catch (\Throwable $e) {
            Log::warning('ReservationWorkspaceCatalog: WP indisponible', ['error' => $e->getMessage()]);
            $meta['wp_connection_failed'] = true;

            return ['rows' => collect(), 'meta' => $meta];
        }

        $meta['wp_tour_count'] = $wpTours->count();

        $wpIds = $wpTours->pluck('ID')->map(fn ($id) => (int) $id)->unique()->values()->all();

        $voyagesByWp = collect();
        if ($wpIds !== []) {
            $voyagesByWp = Voyage::query()
                ->whereIn('wp_post_id', $wpIds)
                ->with([
                    'departures' => function ($q) {
                        $q->whereIn('status', [Departure::STATUS_OPEN, Departure::STATUS_FULL])
                            ->orderBy('start_date');
                    },
                ])
                ->orderBy('name')
                ->get()
                ->unique('wp_post_id')
                ->keyBy(fn (Voyage $v) => (int) $v->wp_post_id);
        }

        $meta['laravel_voyage_matched'] = $voyagesByWp->count();

        $laravelIdsForExtras = $voyagesByWp->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $statsByTour = $this->batchReservationStatsByTour($user, $laravelIdsForExtras);

        foreach ($wpTours as $wp) {
            if (! $wp instanceof WpPost) {
                continue;
            }
            $wpId = (int) $wp->ID;
            /** @var Voyage|null $voyage */
            $voyage = $voyagesByWp->get($wpId);

            $departures = $voyage
                ? ($voyage->departures instanceof EloquentCollection ? $voyage->departures : collect($voyage->departures ?? []))
                : collect();
            $pick = $voyage ? $this->pickDepartureForDisplay($departures, $today) : ['departure' => null, 'is_past' => false];
            $dep = $pick['departure'];
            $departureId = $dep?->id;
            $startDate = $dep?->start_date;
            $travelDateId = $voyage ? $this->resolveTravelDateId($voyage, $startDate) : null;
            $tourId = $voyage ? (int) $voyage->id : null;

            $rows->push([
                'type' => 'package',
                'code' => $voyage ? 'PKG-'.$tourId : 'WP-'.$wpId,
                'name' => $voyage?->name ?: (trim((string) $wp->post_title) ?: 'Tour #'.$wpId),
                'subtitle' => $voyage ? ($pick['is_past'] && $dep ? 'Dernier départ enregistré' : null) : 'Pas de fiche Laravel — liez wp_post_id dans la table voyages pour réserver',
                'voyage_id' => $tourId,
                'wp_post_id' => $wpId,
                'laravel_synced' => $voyage !== null,
                'departure_id' => $departureId,
                'flight_id' => null,
                'tour_hotel_wp_id' => null,
                'travel_date_id' => $travelDateId,
                'departure_date' => $startDate,
                'departure_is_past' => $pick['is_past'],
                'stats' => $tourId ? ($statsByTour[$tourId] ?? $this->emptyStats()) : $this->emptyStats(),
            ]);
        }

        $meta['package_rows'] = $rows->count();

        $flightQuery = VoyageFlight::query()
            ->with('voyage')
            ->whereIn('voyage_id', $laravelIdsForExtras ?: [0])
            ->orderBy('departure_date');

        foreach ($flightQuery->get() as $flight) {
            $voyage = $flight->voyage;
            if (! $voyage) {
                continue;
            }
            $tourId = (int) $voyage->id;
            $label = trim(($flight->flight_number ?: 'Vol').' · '.$flight->from_label.' → '.$flight->to_label);
            $rows->push([
                'type' => 'vol',
                'code' => 'VOL-'.$flight->id,
                'name' => $label,
                'subtitle' => $voyage->name,
                'voyage_id' => $tourId,
                'wp_post_id' => $voyage->wp_post_id ? (int) $voyage->wp_post_id : null,
                'laravel_synced' => true,
                'departure_id' => null,
                'flight_id' => (int) $flight->id,
                'tour_hotel_wp_id' => null,
                'travel_date_id' => $this->resolveTravelDateId($voyage, $flight->departure_date),
                'departure_date' => $flight->departure_date,
                'departure_is_past' => $flight->departure_date && $flight->departure_date->lt($today),
                'stats' => $statsByTour[$tourId] ?? $this->emptyStats(),
            ]);
        }

        foreach ($voyagesByWp as $voyage) {
            if (! $voyage->wp_post_id) {
                continue;
            }
            $tourId = (int) $voyage->id;
            $departures = $voyage->departures instanceof EloquentCollection
                ? $voyage->departures
                : collect($voyage->departures ?? []);
            $pick = $this->pickDepartureForDisplay($departures, $today);
            $startDate = $pick['departure']?->start_date;

            foreach (TourHotel::getAllForTour((int) $voyage->wp_post_id) as $hotel) {
                $rows->push([
                    'type' => 'hebergement',
                    'code' => 'HOT-'.$hotel->id,
                    'name' => $hotel->hotel_name ?: 'Hébergement',
                    'subtitle' => $voyage->name,
                    'voyage_id' => $tourId,
                    'wp_post_id' => (int) $voyage->wp_post_id,
                    'laravel_synced' => true,
                    'departure_id' => $pick['departure']?->id,
                    'flight_id' => null,
                    'tour_hotel_wp_id' => (int) $hotel->id,
                    'travel_date_id' => $this->resolveTravelDateId($voyage, $startDate),
                    'departure_date' => $startDate,
                    'departure_is_past' => $pick['is_past'],
                    'stats' => $statsByTour[$tourId] ?? $this->emptyStats(),
                ]);
            }
        }

        $finalRows = $rows->unique(fn ($r) => $r['type'].'-'.$r['code'])->values();

        $meta['vol_rows'] = $finalRows->where('type', 'vol')->count();
        $meta['hebergement_rows'] = $finalRows->where('type', 'hebergement')->count();
        $meta['total_rows'] = $finalRows->count();

        if (config('app.debug')) {
            Log::debug('ReservationWorkspaceCatalog built', [
                'wp_tour_count' => $meta['wp_tour_count'],
                'laravel_voyage_matched' => $meta['laravel_voyage_matched'],
                'package_rows' => $meta['package_rows'],
                'vol_rows' => $meta['vol_rows'],
                'hebergement_rows' => $meta['hebergement_rows'],
                'total_rows' => $meta['total_rows'],
            ]);
        }

        return ['rows' => $finalRows, 'meta' => $meta];
    }

    /**
     * @return array{departure: ?Departure, is_past: bool}
     */
    private function pickDepartureForDisplay(Collection $departures, Carbon $today): array
    {
        $eligible = $departures->filter(fn ($d) => $d instanceof Departure
            && in_array($d->status, [Departure::STATUS_OPEN, Departure::STATUS_FULL], true)
            && $d->start_date
        )->sortBy(fn (Departure $d) => $d->start_date->timestamp);

        $future = $eligible->first(fn (Departure $d) => ! $d->start_date->lt($today));
        if ($future) {
            return ['departure' => $future, 'is_past' => false];
        }

        $past = $eligible->filter(fn (Departure $d) => $d->start_date->lt($today))->sortByDesc(fn (Departure $d) => $d->start_date->timestamp)->first();

        return [
            'departure' => $past,
            'is_past' => $past !== null,
        ];
    }

    /**
     * @param  array<int>  $tourIds
     * @return array<int, array{validee: int, en_cours: int, annulee: int}>
     */
    private function batchReservationStatsByTour(User $user, array $tourIds): array
    {
        $base = array_fill_keys($tourIds, $this->emptyStats());
        if ($tourIds === []) {
            return $base;
        }

        $q = Reservation::query()->whereIn('tour_id', $tourIds);
        $this->branchScope->scopeReservations($q, $user);
        $this->branchScope->constrainReservationQueryForPortalUser($q, $user);

        $aggregates = (clone $q)
            ->selectRaw('tour_id, status, COUNT(*) as aggregate')
            ->groupBy('tour_id', 'status')
            ->get();

        foreach ($aggregates as $row) {
            $tid = (int) $row->tour_id;
            if (! isset($base[$tid])) {
                continue;
            }
            $n = (int) $row->aggregate;
            match ($row->status) {
                Reservation::STATUS_VALIDEE => $base[$tid]['validee'] += $n,
                Reservation::STATUS_EN_COURS => $base[$tid]['en_cours'] += $n,
                Reservation::STATUS_ANNULEE => $base[$tid]['annulee'] += $n,
                default => null,
            };
        }

        return $base;
    }

    /**
     * @return array{validee: int, en_cours: int, annulee: int}
     */
    private function emptyStats(): array
    {
        return ['validee' => 0, 'en_cours' => 0, 'annulee' => 0];
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
