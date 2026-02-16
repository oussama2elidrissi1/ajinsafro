<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table pour les dates disponibles pour chaque voyage/tour
        Schema::connection('wp')->create('aj_travel_dates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('travel_id')->comment('ID du voyage/tour WordPress (wp_posts.ID)');
            $table->date('date')->comment('Date disponible (YYYY-MM-DD)');
            $table->boolean('is_active')->default(true)->comment('Actif ou non');
            $table->integer('seats')->nullable()->comment('Nombre de places disponibles (optionnel)');
            $table->decimal('price_override', 10, 2)->nullable()->comment('Prix spécifique pour cette date (optionnel)');
            $table->timestamps();

            $table->index('travel_id');
            $table->index(['travel_id', 'is_active']);
            $table->index(['travel_id', 'date']);
            $table->unique(['travel_id', 'date'], 'unique_travel_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wp')->dropIfExists('aj_travel_dates');
    }
};
