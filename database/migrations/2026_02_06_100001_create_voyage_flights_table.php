<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voyage_flights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voyage_id')->comment('Tour/voyage ID (e.g. WP post ID)');
            $table->foreignId('airline_id')->nullable()->constrained('airlines')->nullOnDelete();
            $table->string('cabin_class', 30)->default('economy');
            $table->string('flight_number')->nullable();
            $table->string('departure_airport')->nullable();
            $table->string('arrival_airport')->nullable();
            $table->dateTime('departure_at')->nullable();
            $table->dateTime('arrival_at')->nullable();
            $table->string('baggage')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->nullable()->default('MAD');
            $table->boolean('is_default')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['voyage_id', 'sort_order']);
            $table->index('voyage_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voyage_flights');
    }
};
