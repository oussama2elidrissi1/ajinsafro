<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'wp';

    /**
     * Add departure_place_name and departure_place_code to aj_tour_flights
     * so the WP plugin can display the place without joining aj_travel_departure_places
     * (Laravel sync fills these from voyage_departure_places).
     */
    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if (!$schema->hasTable('aj_tour_flights')) {
            return;
        }
        $hasPlaceId = $schema->hasColumn('aj_tour_flights', 'departure_place_id');
        $hasName = $schema->hasColumn('aj_tour_flights', 'departure_place_name');
        $hasCode = $schema->hasColumn('aj_tour_flights', 'departure_place_code');
        $after = $hasPlaceId ? 'departure_place_id' : 'tour_id';
        $schema->table('aj_tour_flights', function (Blueprint $table) use ($hasName, $hasCode, $after) {
            if (!$hasName) {
                $table->string('departure_place_name', 255)->nullable()->after($after);
            }
            if (!$hasCode) {
                $table->string('departure_place_code', 50)->nullable()->after('departure_place_name');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        if (!$schema->hasTable('aj_tour_flights')) {
            return;
        }
        $schema->table('aj_tour_flights', function (Blueprint $table) {
            if ($schema->hasColumn('aj_tour_flights', 'departure_place_name')) {
                $table->dropColumn('departure_place_name');
            }
            if ($schema->hasColumn('aj_tour_flights', 'departure_place_code')) {
                $table->dropColumn('departure_place_code');
            }
        });
    }
};
