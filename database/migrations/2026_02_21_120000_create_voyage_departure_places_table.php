<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Lieux de départ stockés en Laravel (connexion par défaut) pour affichage et ajout depuis l'admin.
     */
    public function up(): void
    {
        Schema::create('voyage_departure_places', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voyage_id')->comment('FK voyages.id (Laravel)');
            $table->string('name', 255);
            $table->string('code', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('voyage_id');
            $table->foreign('voyage_id')->references('id')->on('voyages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voyage_departure_places');
    }
};
