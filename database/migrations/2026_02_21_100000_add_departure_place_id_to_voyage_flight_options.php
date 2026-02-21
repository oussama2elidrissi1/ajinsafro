<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add departure_place_id to voyage_flight_options to link outbound/return flights to a place.
     * Single source of truth: flights in voyage_flight_options (+ aj_tour_flights); places in aj_travel_departure_places.
     */
    public function up(): void
    {
        if (!Schema::hasTable('voyage_flight_options')) {
            return;
        }
        Schema::table('voyage_flight_options', function (Blueprint $table) {
            if (!Schema::hasColumn('voyage_flight_options', 'departure_place_id')) {
                $table->unsignedBigInteger('departure_place_id')->nullable()->after('voyage_id')
                    ->comment('FK aj_travel_departure_places.id (WP connection)');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('voyage_flight_options')) {
            return;
        }
        Schema::table('voyage_flight_options', function (Blueprint $table) {
            if (Schema::hasColumn('voyage_flight_options', 'departure_place_id')) {
                $table->dropColumn('departure_place_id');
            }
        });
    }
};
