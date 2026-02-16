<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table pour les vols aller associés à chaque lieu de départ
        Schema::connection('wp')->create('aj_travel_departure_flights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('departure_place_id')->comment('FK vers aj_travel_departure_places.id');
            $table->string('airline', 100)->nullable()->comment('Nom de la compagnie aérienne');
            $table->string('flight_number', 50)->nullable()->comment('Numéro de vol (ex: AT123)');
            $table->string('from_airport', 100)->nullable()->comment('Aéroport de départ (ex: Casablanca - CMN)');
            $table->string('to_airport', 100)->nullable()->comment('Aéroport d\'arrivée');
            $table->time('depart_time')->nullable()->comment('Heure de départ (HH:MM)');
            $table->time('arrive_time')->nullable()->comment('Heure d\'arrivée (HH:MM)');
            $table->text('notes')->nullable()->comment('Remarques ou informations supplémentaires');
            $table->integer('sort_order')->default(0)->comment('Ordre d\'affichage');
            $table->timestamps();

            $table->index('departure_place_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wp')->dropIfExists('aj_travel_departure_flights');
    }
};
