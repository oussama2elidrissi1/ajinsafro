<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_program_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voyage_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number');
            $table->string('title');
            $table->string('city')->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('nights')->default(0);
            $table->string('day_type')->default('visite'); // arrivee, visite, transfert, libre
            $table->boolean('meal_breakfast')->default(false);
            $table->boolean('meal_lunch')->default(false);
            $table->boolean('meal_dinner')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_program_days');
    }
};
