<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Source unique pour les listes admin : même périmètre d’accès (agence / portail)
 * et mêmes filtres voyage / date de départ que la vue participants workspace.
 */
final class ReservationListQueryService
{
    public function __construct(
        private BranchScopeService $branchScope,
    ) {}

    public function baseQuery(User $user): Builder
    {
        $q = Reservation::query();
        $this->branchScope->scopeReservations($q, $user);
        $this->branchScope->constrainReservationQueryForPortalUser($q, $user);

        return $q;
    }

    /**
     * @param  Builder<\App\Models\Reservation>  $q
     * @return Builder<\App\Models\Reservation>
     */
    public function applyTourFilter(Builder $q, int $tourId): Builder
    {
        if ($tourId > 0) {
            $q->where('tour_id', $tourId);
        }

        return $q;
    }

    /**
     * Filtre sur {@see TravelDate} id (wp.aj_travel_dates). Null ou 0 = toutes les dates du voyage.
     *
     * @param  Builder<\App\Models\Reservation>  $q
     * @return Builder<\App\Models\Reservation>
     */
    public function applyTravelDateFilter(Builder $q, ?int $travelDateId): Builder
    {
        if ($travelDateId !== null && $travelDateId > 0) {
            $q->where('travel_date_id', $travelDateId);
        }

        return $q;
    }
}
