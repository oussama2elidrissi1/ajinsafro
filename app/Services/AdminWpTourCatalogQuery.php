<?php

namespace App\Services;

use App\Models\Wp\WpPost;
use Illuminate\Database\Eloquent\Builder;

/**
 * Source unique de vérité pour la liste des tours affichés dans l’admin « Circuits / voyages »
 * (WordPress `st_tours`) et le catalogue réservation workspace.
 *
 * La page /admin/circuits/voyages lit cette query (paginée) ; le workspace la consomme en liste complète.
 * Les réservations Laravel restent liées à {@see \App\Models\Voyage} via `wp_post_id` → `posts.ID`.
 */
final class AdminWpTourCatalogQuery
{
    /**
     * Requête de base : identique à VoyageController@index (sans pagination).
     * Aucun filtre « publish only » : draft, pending, publish comme dans la liste admin WP.
     */
    public static function baseQuery(): Builder
    {
        return WpPost::query()
            ->tours()
            ->orderByDesc('ID');
    }
}
