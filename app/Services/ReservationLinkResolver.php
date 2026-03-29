<?php

namespace App\Services;

use App\Models\TravelDate;
use App\Models\Voyage;
use App\Models\VoyageFlight;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Liaison réservation ↔ voyage / départ : règles communes pour packages WP, voyages Laravel seuls, vols et hébergements.
 */
final class ReservationLinkResolver
{
    /**
     * Tous les {@see Voyage::id} à inclure dans un filtre SQL pour « ce voyage » (doublons même wp_post_id).
     *
     * @return list<int>
     */
    public static function physicalTourIdsForVoyage(int $voyageId): array
    {
        return Voyage::allIdsSharingWpTour($voyageId);
    }

    /**
     * Ensemble d’ids voyage pour les agrégats stats workspace : ne pas se limiter aux seuls voyages matchés WordPress,
     * sinon les vols / voyages sans wp_post_id restent à zéro alors que des réservations existent.
     *
     * @param  Collection<int, Voyage>  $voyagesByWp
     * @return list<int>
     */
    public static function workspaceStatsTourIdUniverse(Collection $voyagesByWp): array
    {
        $fromWp = $voyagesByWp->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $fromFlights = VoyageFlight::query()
            ->distinct()
            ->pluck('voyage_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $fromLaravelNative = Voyage::query()
            ->whereNull('wp_post_id')
            ->orderBy('id')
            ->limit(300)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return array_values(array_unique(array_merge($fromWp, $fromFlights, $fromLaravelNative)));
    }

    /**
     * Vérifie qu’une ligne {@see TravelDate} (wp.aj_travel_dates) correspond au voyage :
     * - avec tour WordPress : travel_id = wp_post_id ;
     * - sans tour WordPress : travel_id = id fiche Laravel (synchro alternative) ou pas de date exigible côté workspace.
     */
    public static function assertTravelDateBelongsToVoyage(Voyage $voyage, TravelDate $travelDate): void
    {
        if ($voyage->wp_post_id) {
            if ((int) $travelDate->travel_id !== (int) $voyage->wp_post_id) {
                throw ValidationException::withMessages([
                    'travel_date_id' => ['Cette date ne correspond pas au tour WordPress du voyage.'],
                ]);
            }

            return;
        }

        if ((int) $travelDate->travel_id !== (int) $voyage->id) {
            throw ValidationException::withMessages([
                'travel_date_id' => ['Cette date de départ n’est pas rattachée à ce voyage Laravel.'],
            ]);
        }
    }
}
