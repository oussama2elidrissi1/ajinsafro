<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run on WP connection. Max 2 segments per tour (segment_number 1 or 2).
     */
    public function up(): void
    {
        if (Schema::connection('wp')->hasTable('aj_tour_flights')) {
            return;
        }

        Schema::connection('wp')->create('aj_tour_flights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_id')->comment('wp_posts.ID');
            $table->unsignedTinyInteger('segment_number')->comment('1 or 2');
            $table->unsignedBigInteger('airline_id')->nullable();
            $table->foreign('airline_id')->references('id')->on('aj_airlines')->nullOnDelete();
            $table->string('cabin_class', 30)->default('economy');
            $table->string('flight_number')->nullable();
            $table->date('depart_date')->nullable();
            $table->string('depart_city')->nullable();
            $table->string('depart_airport')->nullable();
            $table->date('arrive_date')->nullable();
            $table->string('arrive_city')->nullable();
            $table->string('arrive_airport')->nullable();
            $table->string('cabin_baggage')->nullable();
            $table->string('checkin_baggage')->nullable();
            $table->boolean('is_tentative')->default(false);
            $table->boolean('is_default')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['tour_id', 'segment_number']);
            $table->index('tour_id');
        });
    }

    public function down(): void
    {
        Schema::connection('wp')->dropIfExists('aj_tour_flights');
    }
};
