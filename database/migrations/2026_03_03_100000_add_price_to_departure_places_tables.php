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
        // Connexion Laravel principale
        if (! Schema::hasColumn('voyage_departure_places', 'price')) {
            Schema::table('voyage_departure_places', function (Blueprint $table) {
                $table->decimal('price', 12, 2)
                    ->nullable()
                    ->after('sort_order')
                    ->comment('Prix (MAD) pour ce lieu de départ');
            });
        }

        // Connexion WordPress (optionnelle) – on ne tente rien si la connexion n'est pas configurée
        $wpConfig = config('database.connections.wp');
        if (!empty($wpConfig['database']) && !empty($wpConfig['username'])) {
            if (Schema::connection('wp')->hasTable('aj_travel_departure_places')
                && ! Schema::connection('wp')->hasColumn('aj_travel_departure_places', 'price')) {
                Schema::connection('wp')->table('aj_travel_departure_places', function (Blueprint $table) {
                    $table->decimal('price', 12, 2)
                        ->nullable()
                        ->after('sort_order')
                        ->comment('Prix (MAD) pour ce lieu de départ');
                });
            }
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
