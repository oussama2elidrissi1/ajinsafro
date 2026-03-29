<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables Laravel (connexion par défaut) pour l’allocation progressive des chambres
     * et le suivi des sièges occupés par date de départ — distinctes du catalogue WP (aj_tour_hotels).
     */
    public function up(): void
    {
        if (! Schema::hasTable('tour_room_type_occupancies')) {
            Schema::create('tour_room_type_occupancies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('travel_date_id');
                $table->unsignedBigInteger('tour_hotel_id');
                $table->unsignedBigInteger('tour_hotel_room_id');
                $table->unsignedInteger('seats_occupied_total')->default(0);
                $table->timestamps();

                $table->unique(['travel_date_id', 'tour_hotel_room_id'], 'tour_room_occ_date_room_unique');
                $table->index('travel_date_id', 'tour_room_occ_travel_date_idx');
            });
        }

        if (! Schema::hasTable('reservation_room_allocations')) {
            Schema::create('reservation_room_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
                $table->unsignedBigInteger('travel_date_id');
                $table->unsignedBigInteger('tour_hotel_id');
                $table->unsignedBigInteger('tour_hotel_room_id');
                $table->unsignedInteger('seats_allocated')->default(0);
                $table->unsignedInteger('rooms_new_count')->default(0);
                $table->unsignedInteger('rooms_total_count')->default(0);
                $table->timestamps();

                $table->index('reservation_id', 'res_room_alloc_reservation_idx');
                $table->index(['travel_date_id', 'tour_hotel_room_id'], 'res_room_alloc_date_room_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_room_allocations');
        Schema::dropIfExists('tour_room_type_occupancies');
    }
};
