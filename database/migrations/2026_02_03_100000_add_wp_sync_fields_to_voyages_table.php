<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vérifier l'existence des colonnes AVANT le callback Schema::table
        $hasWpPostId = Schema::hasColumn('voyages', 'wp_post_id');
        $hasWpSyncedAt = Schema::hasColumn('voyages', 'wp_synced_at');
        $hasWpSyncHash = Schema::hasColumn('voyages', 'wp_sync_hash');

        Schema::table('voyages', function (Blueprint $table) use ($hasWpPostId, $hasWpSyncedAt, $hasWpSyncHash) {
            // Ajouter wp_post_id seulement si elle n'existe pas (avec index)
            if (!$hasWpPostId) {
                $table->unsignedBigInteger('wp_post_id')->nullable()->after('id')->index();
            }

            // Ajouter wp_synced_at seulement si elle n'existe pas
            if (!$hasWpSyncedAt) {
                $table->timestamp('wp_synced_at')->nullable()->after('updated_at');
            }

            // Ajouter wp_sync_hash seulement si elle n'existe pas
            if (!$hasWpSyncHash) {
                $table->string('wp_sync_hash', 64)->nullable()->after('wp_synced_at');
            }
        });
    }

    public function down(): void
    {
        // Vérifier l'existence des colonnes AVANT le callback Schema::table
        $hasWpPostId = Schema::hasColumn('voyages', 'wp_post_id');
        $hasWpSyncedAt = Schema::hasColumn('voyages', 'wp_synced_at');
        $hasWpSyncHash = Schema::hasColumn('voyages', 'wp_sync_hash');

        Schema::table('voyages', function (Blueprint $table) use ($hasWpPostId, $hasWpSyncedAt, $hasWpSyncHash) {
            // Construire la liste des colonnes à supprimer
            $columnsToDrop = [];
            
            if ($hasWpPostId) {
                $columnsToDrop[] = 'wp_post_id';
            }
            if ($hasWpSyncedAt) {
                $columnsToDrop[] = 'wp_synced_at';
            }
            if ($hasWpSyncHash) {
                $columnsToDrop[] = 'wp_sync_hash';
            }

            // Supprimer les colonnes (Laravel supprimera automatiquement les index associés)
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
