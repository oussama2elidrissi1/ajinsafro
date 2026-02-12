<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multi-flight support: outbound options, return options, segment flights by day.
     * Replaces single outbound/inbound with N options per voyage.
     */
    public function up(): void
    {
        Schema::create('voyage_flight_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
            $table->string('type', 20)->comment('outbound|return|segment');
            $table->unsignedTinyInteger('day_number')->nullable()->comment('Jour attaché (1=aller, N=retour, ou jour segment)');
            $table->string('from_city')->nullable();
            $table->string('to_city')->nullable();
            $table->dateTime('depart_at')->nullable();
            $table->dateTime('arrive_at')->nullable();
            $table->foreignId('airline_id')->nullable()->constrained('airlines')->nullOnDelete();
            $table->string('flight_number')->nullable();
            $table->string('cabin', 30)->default('economy');
            $table->unsignedSmallInteger('baggage_cabin_kg')->nullable();
            $table->unsignedSmallInteger('baggage_checkin_kg')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedTinyInteger('stops')->nullable();
            $table->decimal('price', 10, 2)->nullable()->comment('Supplément si optionnel payant');
            $table->string('currency', 3)->nullable()->default('MAD');
            $table->boolean('is_included')->default(true);
            $table->boolean('is_optional')->default(false)->comment('Client peut choisir parmi les options');
            $table->string('group_key', 64)->nullable()->comment('OUTBOUND, RETURN, SEGMENT_DAY_3');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_tentative')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['voyage_id', 'type']);
            $table->index(['voyage_id', 'day_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voyage_flight_options');
    }
};
