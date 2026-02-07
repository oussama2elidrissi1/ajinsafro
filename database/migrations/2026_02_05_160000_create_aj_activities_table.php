<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Table name without prefix (connexion wp ajoute le préfixe automatiquement). */
    private const TABLE = 'aj_activities';

    /**
     * Run the migrations.
     * Table: {wp_prefix}aj_activities (catalogue of activities).
     * Idempotent: ne crée pas si la table existe déjà (préfixe WP géré par la connexion).
     */
    public function up(): void
    {
        $schema = Schema::connection('wp');

        if ($schema->hasTable(self::TABLE)) {
            return;
        }

        $schema->create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('default_duration_minutes')->nullable();
            $table->string('location_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * dropIfExists utilise le préfixe de la connexion wp.
     */
    public function down(): void
    {
        Schema::connection('wp')->dropIfExists(self::TABLE);
    }
};
