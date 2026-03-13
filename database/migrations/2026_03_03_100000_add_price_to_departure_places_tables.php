<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add price (MAD) per departure place: Laravel + WP tables.
     */
    public function up(): void
    {
        Schema::table('voyage_departure_places', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->after('sort_order')->comment('Prix (MAD) pour ce lieu de départ');
        });

        if (Schema::connection('wp')->hasTable('aj_travel_departure_places')) {
            Schema::connection('wp')->table('aj_travel_departure_places', function (Blueprint $table) {
                $table->decimal('price', 12, 2)->nullable()->after('sort_order')->comment('Prix (MAD) pour ce lieu de départ');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voyage_departure_places', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        if (Schema::connection('wp')->hasTable('aj_travel_departure_places')) {
            Schema::connection('wp')->table('aj_travel_departure_places', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
};
