<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une offre propose souvent le meme type de chambre a plusieurs hebergements
 * (quadruple chez l'hotel A, quadruple chez l'hotel B). Sans libelle, les deux lignes
 * sont indistinguables au moment de les rattacher a une formule : on ajoute un intitule
 * libre, purement interne a l'editeur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hajj_omra_room_prices', function (Blueprint $table) {
            $table->string('label', 120)->nullable()->after('room_type');
        });
    }

    public function down(): void
    {
        Schema::table('hajj_omra_room_prices', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
