<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le champ « Prix a partir de » (hajj_omra_packages.adult_price) redevient saisissable
 * et prioritaire sur le calcul automatique : un prix saisi s'affiche tel quel, un champ
 * vide continue de suivre le tarif actif le plus bas.
 *
 * Tant que le champ etait desactive dans l'editeur, les offres possedant des tarifs ou des
 * formules gardaient en base une valeur heritee qui n'etait plus affichee nulle part.
 * Sans ce nettoyage, ces valeurs mortes deviendraient soudainement le prix public.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('hajj_omra_packages')
            ->whereNotNull('adult_price')
            ->where(function ($query) {
                $query->whereExists(function ($sub) {
                    $sub->selectRaw('1')->from('hajj_omra_room_prices')
                        ->whereColumn('hajj_omra_room_prices.package_id', 'hajj_omra_packages.id');
                })->orWhereExists(function ($sub) {
                    $sub->selectRaw('1')->from('hajj_omra_formulas')
                        ->whereColumn('hajj_omra_formulas.package_id', 'hajj_omra_packages.id');
                });
            })
            ->update(['adult_price' => null]);
    }

    public function down(): void
    {
        // Les valeurs effacees etaient deja ignorees par l'affichage : rien a restaurer.
    }
};
