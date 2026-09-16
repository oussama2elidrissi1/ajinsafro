<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chaque offre peut router ses demandes WhatsApp vers un conseiller different.
 * Vide : le site public retombe sur le numero general de l'agence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hajj_omra_packages', function (Blueprint $table) {
            $table->string('whatsapp_phone', 30)->nullable()->after('departure_city');
        });
    }

    public function down(): void
    {
        Schema::table('hajj_omra_packages', function (Blueprint $table) {
            $table->dropColumn('whatsapp_phone');
        });
    }
};
