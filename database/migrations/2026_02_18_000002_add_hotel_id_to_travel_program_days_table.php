<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_program_days', function (Blueprint $table) {
            // Ajouter hotel_id (nullable, relie à aj_tour_hotels.id de la base 'wp')
            // Pas de foreign key puisqu'elle est sur une autre base de données
            $table->unsignedBigInteger('hotel_id')->nullable()->after('day_type');
        });
    }

    public function down(): void
    {
        Schema::table('travel_program_days', function (Blueprint $table) {
            $table->dropColumn('hotel_id');
        });
    }
};
