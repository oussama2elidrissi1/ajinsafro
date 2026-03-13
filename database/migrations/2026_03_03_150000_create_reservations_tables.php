<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            // Lien optionnel vers le tour / voyage (wp_posts.ID ou voyages.id selon besoin futur)
            $table->unsignedBigInteger('tour_id')->nullable()->comment('ID du tour (st_tours / wp_posts.ID)');

            // Client existant (API externe) ou nouveau client saisi dans le formulaire
            $table->string('client_mode', 20)->default('existing'); // existing|new
            $table->unsignedBigInteger('client_external_id')->nullable()->comment('Identifiant client retourné par l\'API Client');

            // Snapshot des infos client au moment de la réservation (toujours rempli, même pour client existant)
            $table->string('client_first_name', 100)->nullable();
            $table->string('client_last_name', 100)->nullable();
            $table->string('client_email', 190)->nullable();
            $table->string('client_phone', 50)->nullable();
            $table->string('client_document_type', 50)->nullable();
            $table->string('client_document_number', 100)->nullable();

            // Paiement
            $table->string('payment_type', 20)->nullable()->comment('CASHPLUS, VIREMENT, ESPECE, etc.');
            $table->string('payment_receipt_path', 255)->nullable()->comment('Chemin du fichier de reçu');

            // Statut de réservation
            $table->string('status', 30)->default('EN_COURS');

            // Métadonnées diverses
            $table->unsignedInteger('passengers_count')->default(1);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('tour_id');
            $table->index('client_external_id');
            $table->index('status');
            $table->index('payment_type');
        });

        Schema::create('reservation_passengers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservation_id');

            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('type', 20)->nullable()->comment('adult|child|infant, etc.');
            $table->date('birth_date')->nullable();
            $table->string('document_type', 50)->nullable();
            $table->string('document_number', 100)->nullable();

            $table->timestamps();

            $table->foreign('reservation_id')
                ->references('id')->on('reservations')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_passengers');
        Schema::dropIfExists('reservations');
    }
};

