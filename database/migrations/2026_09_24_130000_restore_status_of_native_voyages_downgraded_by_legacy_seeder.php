<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rend leur statut aux fiches natives que le seeder du catalogue historique avait rétrogradées.
 *
 * `LegacyContentSeeder` appliquait `status => 'draft'` aussi en mise à jour, et son garde-fou
 * `isStillIncomplete()` répond `true` quand le bloc `completion` est absent — ce qui est le cas de
 * toute fiche antérieure à la migration. Les fiches que l'agence pilotait déjà se sont donc
 * retrouvées en brouillon dans l'admin alors que leur tour WordPress restait publié.
 *
 * Le seeder ne pose plus le statut qu'à la création ; il reste à réparer l'existant.
 *
 * Périmètre volontairement étroit : une fiche n'est relevée que si elle réunit les trois signes
 * d'une fiche native rétrogradée — créée avant la migration, en brouillon, et rattachée à un tour
 * WordPress publié. Les 190 fiches réellement importées restent en brouillon, conformément au
 * choix de ne rien publier.
 */
return new class extends Migration
{
    /** Date de la reprise du catalogue historique : au-delà, la fiche est née de l'import. */
    private const MIGRATION_DATE = '2026-09-22 00:00:00';

    public function up(): void
    {
        if (! Schema::hasTable('voyages')) {
            return;
        }

        $candidates = DB::table('voyages')
            ->where('status', 'draft')
            ->whereNotNull('wp_post_id')
            ->where('created_at', '<', self::MIGRATION_DATE)
            ->where('logistics_meta', 'like', '%"legacy_import"%')
            ->pluck('wp_post_id', 'id');

        if ($candidates->isEmpty()) {
            return;
        }

        // Seul un tour encore publié atteste que la fiche était bien en service.
        $published = DB::connection('wp')
            ->table('posts')
            ->whereIn('ID', $candidates->values()->all())
            ->where('post_status', 'publish')
            ->pluck('ID')
            ->all();

        if ($published === []) {
            return;
        }

        $ids = $candidates
            ->filter(static fn ($wpPostId) => in_array((int) $wpPostId, array_map('intval', $published), true))
            ->keys()
            ->all();

        if ($ids !== []) {
            DB::table('voyages')->whereIn('id', $ids)->update(['status' => 'actif']);
        }
    }

    public function down(): void
    {
        // Le statut d'origine n'est pas conservé : rejouer la rétrogradation ferait plus de dégâts
        // que de ne rien faire.
    }
};
