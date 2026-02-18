<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table pivot dans la base 'default' (puisque TravelProgramDay est en 'default')
        // Elle relie TravelProgramDay (travel_program_days.id) avec TourTransfer (aj_tour_transfers.id, qui est sur 'wp')
        Schema::create('program_day_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_day_id')->index();
            $table->unsignedBigInteger('transfer_id')->index();
            $table->unique(['program_day_id', 'transfer_id']);
            
            // Foreign key vers travel_program_days dans la connexion 'default'
            $table->foreign('program_day_id')
                ->references('id')
                ->on('travel_program_days')
                ->onDelete('cascade');
            
            // Note: Pas de foreign key vers aj_tour_transfers car elle est sur une autre connexion (wp).
            // La validation de l'existence du transfer doit se faire au niveau applicatif.
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_day_transfers');
    }
};
