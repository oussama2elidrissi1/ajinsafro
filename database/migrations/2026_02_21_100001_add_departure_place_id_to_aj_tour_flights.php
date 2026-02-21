<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'wp';

    /**
     * Add departure_place_id to aj_tour_flights so WP/searchbar can show "flights from this place".
     */
    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if (!$schema->hasTable('aj_tour_flights')) {
            return;
        }
        if ($schema->hasColumn('aj_tour_flights', 'departure_place_id')) {
            return;
        }
        $schema->table('aj_tour_flights', function (Blueprint $table) {
            $table->unsignedBigInteger('departure_place_id')->nullable()->after('tour_id')
                ->comment('FK aj_travel_departure_places.id');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        if (!$schema->hasTable('aj_tour_flights')) {
            return;
        }
        if (!$schema->hasColumn('aj_tour_flights', 'departure_place_id')) {
            return;
        }
        $schema->table('aj_tour_flights', function (Blueprint $table) {
            $table->dropColumn('departure_place_id');
        });
    }
};
