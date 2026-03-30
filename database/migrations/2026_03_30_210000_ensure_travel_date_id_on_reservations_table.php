<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réparation : si `2026_03_20_100000_add_travel_date_id_to_reservations` est marquée
 * comme exécutée mais la colonne n’existe pas (import DB, rollback partiel, etc.),
 * cette migration recrée la colonne de façon idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql');
        if ($schema->hasTable('reservations') && ! $schema->hasColumn('reservations', 'travel_date_id')) {
            $schema->table('reservations', function (Blueprint $table) {
                $table->unsignedBigInteger('travel_date_id')->nullable()->after('tour_id')
                    ->comment('Date de départ choisie (aj_travel_dates.id, connexion wp)');
            });
        }
    }

    public function down(): void
    {
        // Ne pas supprimer : la colonne peut avoir été créée par la migration d’origine.
    }
};
