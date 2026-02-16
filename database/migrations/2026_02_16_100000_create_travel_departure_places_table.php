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
        // Table pour les lieux de départ (places) dans la connexion WP
        Schema::connection('wp')->create('aj_travel_departure_places', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('travel_id')->comment('ID du voyage/tour WordPress (wp_posts.ID)');
            $table->string('name', 255)->comment('Nom du lieu de départ (ex: Casablanca, Paris)');
            $table->string('code', 50)->nullable()->comment('Code IATA ou autre code (ex: CMN, CDG)');
            $table->boolean('is_active')->default(true)->comment('Actif ou non');
            $table->integer('sort_order')->default(0)->comment('Ordre d\'affichage');
            $table->timestamps();

            $table->index('travel_id');
            $table->index(['travel_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wp')->dropIfExists('aj_travel_departure_places');
    }
};
