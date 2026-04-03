<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('wp');

        if ($schema->hasTable('aj_tour_hotel_room_date_availabilities')) {
            return;
        }

        $schema->create('aj_tour_hotel_room_date_availabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_id')->comment('ID du voyage/tour WordPress (wp_posts.ID)');
            $table->unsignedBigInteger('tour_hotel_id')->comment('aj_tour_hotels.id');
            $table->unsignedBigInteger('tour_hotel_room_id')->comment('aj_tour_hotel_rooms.id');
            $table->unsignedBigInteger('travel_date_id')->comment('aj_travel_dates.id');
            $table->unsignedInteger('available_rooms')->default(0);
            $table->unsignedInteger('available_places')->default(0);
            $table->string('status', 32)->default('available')->comment('available, limited, full, closed');
            $table->decimal('supplement', 12, 2)->default(0);
            $table->timestamps();

            // Short index names: MySQL max identifier length is 64 chars (prefix + auto names exceed it).
            $table->unique(['tour_hotel_room_id', 'travel_date_id'], 'aj_thrda_uniq_room_date');
            $table->index(['tour_id', 'travel_date_id'], 'aj_thrda_idx_tour_tdate');
            $table->index('tour_hotel_id', 'aj_thrda_idx_thotel');
            $table->index('tour_hotel_room_id', 'aj_thrda_idx_throom');
        });
    }

    public function down(): void
    {
        Schema::connection('wp')->dropIfExists('aj_tour_hotel_room_date_availabilities');
    }
};
