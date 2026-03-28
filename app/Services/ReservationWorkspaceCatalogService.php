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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Catalogue « workspace » : une ligne package par voyage Laravel, plus vols & hébergements liés.
 * Aligné sur la table voyages (circuits) — pas de filtre « départs futurs uniqués » pour la liste des voyages.
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
        $today = Carbon::today();
        $rows = collect();

        $voyages = $this->queryVoyagesForCatalog()->get();
        if ($voyages->isEmpty()) {
            $voyages = Voyage::query()
                ->with([
                    'departures' => function ($q) {
                        $q->whereIn('status', [Departure::STATUS_OPEN, Departure::STATUS_FULL])
                            ->orderBy('start_date');
                    },
                ])
                ->orderBy('name')
                ->get();
        }

        $tourIds = $voyages->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $statsByTour = $this->batchReservationStatsByTour($user, $tourIds);

        foreach ($voyages as $voyage) {
            $departures = $voyage->departures instanceof EloquentCollection
                ? $voyage->departures
                : collect($voyage->departures ?? []);
            $pick = $this->pickDepartureForDisplay($departures, $today);
            /** @var Departure|null $dep */
            $dep = $pick['departure'];
            $departureId = $dep?->id;
            $startDate = $dep?->start_date;
            $travelDateId = $this->resolveTravelDateId($voyage, $startDate);
            $tourId = (int) $voyage->id;

            $rows->push([
                'type' => 'package',
                'code' => 'PKG-'.$tourId,
                'name' => $voyage->name ?: 'Voyage #'.$tourId,
                'subtitle' => $pick['is_past'] && $dep ? 'Dernier départ enregistré' : null,
                'voyage_id' => $tourId,
                'departure_id' => $departureId,
                'flight_id' => null,
                'tour_hotel_wp_id' => null,
                'travel_date_id' => $travelDateId,
                'departure_date' => $startDate,
                'departure_is_past' => $pick['is_past'],
                'stats' => $statsByTour[$tourId] ?? $this->emptyStats(),
            ]);
        }

        $flightQuery = VoyageFlight::query()
            ->with('voyage')
            ->whereIn('voyage_id', $tourIds ?: [0])
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
                'departure_id' => null,
                'flight_id' => (int) $flight->id,
                'tour_hotel_wp_id' => null,
                'travel_date_id' => $this->resolveTravelDateId($voyage, $flight->departure_date),
                'departure_date' => $flight->departure_date,
                'departure_is_past' => $flight->departure_date && $flight->departure_date->lt($today),
                'stats' => $statsByTour[$tourId] ?? $this->emptyStats(),
            ]);
        }

        foreach ($voyages as $voyage) {
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

        return $rows->unique(fn ($r) => $r['type'].'-'.$r['code'])->values();
    }

    /**
     * Même périmètre que la gestion circuits : tous les enregistrements voyages utiles à la réservation.
     */
    private function queryVoyagesForCatalog()
    {
        return Voyage::query()
            ->with([
                'departures' => function ($q) {
                    $q->whereIn('status', [Departure::STATUS_OPEN, Departure::STATUS_FULL])
                        ->orderBy('start_date');
                },
            ])
            ->where(function ($q) {
                $q->whereIn('status', ['publish', 'actif', 'draft', 'pending'])
                    ->orWhereNull('status')
                    ->orWhere('status', '');
            })
            ->orderBy('name');
    }

    /**
     * Prochain départ ouvert / complet à venir ; sinon dernier départ passé (pour contexte).
     *
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
     * Statistiques agrégées par voyage (toutes dates) — évite des compteurs vides liés à travel_date_id.
     *
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
