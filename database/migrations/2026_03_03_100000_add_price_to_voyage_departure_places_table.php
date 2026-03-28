<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Prix par lieu de départ (chaque lieu peut avoir un prix différent).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('voyage_departure_places', 'price')) {
            Schema::table('voyage_departure_places', function (Blueprint $table) {
                $table->decimal('price', 12, 2)->nullable()->after('sort_order')->comment('Prix (MAD) pour ce lieu de départ');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('voyage_departure_places', 'price')) {
            Schema::table('voyage_departure_places', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
};
