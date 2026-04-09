<?php

namespace App\Services;

use App\Models\Voyage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Synchronise les thèmes Laravel vers la taxonomie WordPress `tours_cat`
 * et la méta `aj_catalog_destination` pour les filtres du catalogue public.
 */
class VoyageThemeWpSyncService
{
    private const TAXONOMY = 'tours_cat';

    private const DESTINATION_META = 'aj_catalog_destination';

    public function syncFromLaravelVoyage(Voyage $voyage): void
    {
        $wpPostId = $voyage->wp_post_id ? (int) $voyage->wp_post_id : 0;
        if ($wpPostId <= 0) {
            return;
        }

        try {
            $this->replaceToursCatTerms($wpPostId, $voyage);
            $this->syncDestinationMeta($wpPostId, $voyage);
        } catch (\Throwable $e) {
            Log::warning('VoyageThemeWpSyncService: sync failed', [
                'voyage_id' => $voyage->id,
                'wp_post_id' => $wpPostId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function replaceToursCatTerms(int $wpPostId, Voyage $voyage): void
    {
        $voyage->loadMissing('themes');
        $prefix = DB::connection('wp')->getTablePrefix();

        DB::connection('wp')->delete("
            DELETE tr FROM {$prefix}term_relationships tr
            INNER JOIN {$prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tr.object_id = ? AND tt.taxonomy = ?
        ", [$wpPostId, self::TAXONOMY]);

        foreach ($voyage->themes as $theme) {
            $ttId = $this->ensureTermTaxonomyId($theme->name, $theme->slug);
            if ($ttId === null) {
                continue;
            }

            DB::connection('wp')->table('term_relationships')->insert([
                'object_id' => $wpPostId,
                'term_taxonomy_id' => $ttId,
                'term_order' => 0,
            ]);
        }
    }

    private function ensureTermTaxonomyId(string $name, string $slug): ?int
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $row = DB::connection('wp')->table('terms as t')
            ->join('term_taxonomy as tt', 't.term_id', '=', 'tt.term_id')
            ->where('tt.taxonomy', self::TAXONOMY)
            ->where('t.slug', $slug)
            ->select('tt.term_taxonomy_id')
            ->first();

        if ($row) {
            return (int) $row->term_taxonomy_id;
        }

        $termId = DB::connection('wp')->table('terms')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'term_group' => 0,
        ]);

        return (int) DB::connection('wp')->table('term_taxonomy')->insertGetId([
            'term_id' => $termId,
            'taxonomy' => self::TAXONOMY,
            'description' => '',
            'parent' => 0,
            'count' => 0,
        ]);
    }

    private function syncDestinationMeta(int $wpPostId, Voyage $voyage): void
    {
        $dest = trim((string) ($voyage->destination ?? ''));
        if ($dest === '') {
            $raw = DB::connection('wp')->table('postmeta')
                ->where('post_id', $wpPostId)
                ->where('meta_key', 'address')
                ->value('meta_value');
            $dest = trim((string) ($raw ?? ''));
        }

        DB::connection('wp')->table('postmeta')
            ->where('post_id', $wpPostId)
            ->where('meta_key', self::DESTINATION_META)
            ->delete();

        if ($dest !== '') {
            DB::connection('wp')->table('postmeta')->insert([
                'post_id' => $wpPostId,
                'meta_key' => self::DESTINATION_META,
                'meta_value' => $dest,
            ]);
        }
    }
}
