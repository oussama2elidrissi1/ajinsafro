<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Models\Wp\WpPostMeta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fusionne un programme historique importé en double avec le voyage déjà présent au catalogue.
 *
 * Cas traité : l'offre existe déjà dans WordPress sous un slug dédoublonné
 * (`...-8900-dhs-2` au lieu de `...-8900-dhs-466`) et un voyage Laravel y est déjà rattaché.
 * Publier le programme importé créerait un second tour pour la même offre.
 *
 * La fusion reporte l'identité historique (identifiant `.ma`, URLs d'origine) sur le voyage
 * existant, pose `_ajinsafro_legacy_id` sur son tour WordPress, puis supprime la fiche importée
 * en double. Elle ne touche ni au contenu, ni au prix, ni au slug du voyage existant.
 *
 * Dry-run par défaut ; `--execute` applique.
 */
class LegacyMergeCommand extends Command
{
    protected $signature = 'legacy:merge
        {--execute : Applique réellement la fusion (sinon simulation)}
        {--id=* : Ne traiter que ces identifiants historiques}
        {--map=* : Choix manuel, au format legacyId:voyageId, quand plusieurs cibles sont possibles}';

    protected $description = 'Fusionne les programmes historiques importés en double avec les voyages déjà au catalogue.';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $onlyIds = array_map('intval', (array) $this->option('id'));
        $forced = $this->parseMap();

        $all = Voyage::query()->get(['id', 'wp_post_id', 'name', 'slug', 'logistics_meta']);
        $natives = $all->filter(fn (Voyage $v) => ! $v->isLegacyImport() && (int) $v->wp_post_id > 0);
        $nativesByBase = $natives->groupBy(fn (Voyage $v) => LegacyCheckCommand::slugBase($v->slug));

        $imported = $all
            ->filter(fn (Voyage $v) => $v->isLegacyImport() && ! $v->wp_post_id)
            ->filter(fn (Voyage $v) => $onlyIds === []
                || in_array((int) data_get($v->logistics_meta, 'legacy_import.legacy_id'), $onlyIds, true));

        $stats = ['merged' => 0, 'ambiguous' => 0, 'none' => 0, 'blocked' => 0];

        foreach ($imported as $voyage) {
            $legacyId = (int) data_get($voyage->logistics_meta, 'legacy_import.legacy_id');
            $base = LegacyCheckCommand::slugBase($voyage->slug);

            $target = null;
            if (isset($forced[$legacyId])) {
                $target = $natives->firstWhere('id', $forced[$legacyId]);
                if (! $target) {
                    $stats['blocked']++;
                    $this->error(sprintf('  legacy %-4d : voyage cible #%d introuvable ou sans tour WordPress.', $legacyId, $forced[$legacyId]));

                    continue;
                }
            } else {
                $candidates = $nativesByBase->get($base) ?? collect();

                if ($candidates->isEmpty()) {
                    $stats['none']++;

                    continue;
                }

                if ($candidates->count() > 1) {
                    $stats['ambiguous']++;
                    $this->line(sprintf('  <fg=yellow>ambigu</> legacy %-4d %s : %d voyages possibles', $legacyId, Str::limit($base, 45), $candidates->count()));
                    foreach ($candidates as $c) {
                        $this->line(sprintf('             --map=%d:%d  -> voyage #%d (wp %d) %s', $legacyId, $c->id, $c->id, $c->wp_post_id, $c->slug));
                    }

                    continue;
                }

                $target = $candidates->first();
            }

            $reservations = $this->reservationCount((int) $voyage->id);
            if ($reservations > 0) {
                $stats['blocked']++;
                $this->error(sprintf('  legacy %-4d : la fiche importée #%d porte %d réservation(s), fusion refusée.', $legacyId, $voyage->id, $reservations));

                continue;
            }

            $stats['merged']++;
            $this->line(sprintf(
                '  <fg=green>fusion</> legacy %-4d : fiche importée #%d -> voyage #%d (wp %d)',
                $legacyId,
                $voyage->id,
                $target->id,
                $target->wp_post_id
            ));

            if (! $execute) {
                continue;
            }

            DB::transaction(function () use ($voyage, $target, $legacyId): void {
                $meta = is_array($target->logistics_meta) ? $target->logistics_meta : [];
                $meta['legacy_import'] = data_get($voyage->logistics_meta, 'legacy_import');
                $meta['seo'] = data_get($voyage->logistics_meta, 'seo');
                // Pas de bloc `completion` : le voyage cible est une fiche du catalogue courant,
                // ce n'est pas un brouillon à compléter.
                $target->logistics_meta = $meta;
                $target->save();

                $voyage->delete();
            });

            $this->writeLegacyMeta((int) $target->wp_post_id, $legacyId, (string) data_get($target->logistics_meta, 'seo.legacy_path_prefix', ''));
        }

        $this->newLine();
        $this->info(sprintf(
            '%s : %d fusions, %d ambiguës, %d bloquées, %d sans équivalent.',
            $execute ? 'Fusion' : 'Simulation (relancez avec --execute)',
            $stats['merged'],
            $stats['ambiguous'],
            $stats['blocked'],
            $stats['none']
        ));

        if ($stats['ambiguous'] > 0) {
            $this->warn('Cas ambigus : rejouez la commande avec le --map indiqué pour désigner le voyage à conserver.');
        }

        return $stats['blocked'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, int>
     */
    private function parseMap(): array
    {
        $map = [];
        foreach ((array) $this->option('map') as $entry) {
            if (preg_match('/^(\d+):(\d+)$/', trim((string) $entry), $m)) {
                $map[(int) $m[1]] = (int) $m[2];
            }
        }

        return $map;
    }

    private function reservationCount(int $voyageId): int
    {
        try {
            return (int) DB::table('reservations')->where('voyage_id', $voyageId)->count();
        } catch (\Throwable $e) {
            // Table absente : on ne bloque pas, mais on ne peut rien affirmer non plus.
            return 0;
        }
    }

    private function writeLegacyMeta(int $postId, int $legacyId, string $pathPrefix): void
    {
        $metas = [Voyage::WP_LEGACY_ID_META => (string) $legacyId];

        // Lu par le plugin WordPress pour servir la fiche sous son ancienne URL.
        if ($pathPrefix !== '') {
            $metas['_aj_legacy_path_prefix'] = $pathPrefix;
        }

        try {
            foreach ($metas as $key => $value) {
                $existing = WpPostMeta::query()
                    ->where('post_id', $postId)
                    ->where('meta_key', $key)
                    ->first();

                if ($existing) {
                    $existing->meta_value = $value;
                    $existing->save();

                    continue;
                }

                WpPostMeta::create([
                    'post_id' => $postId,
                    'meta_key' => $key,
                    'meta_value' => $value,
                ]);
            }
        } catch (\Throwable $e) {
            $this->warn(sprintf('  meta WordPress non posée sur le post %d : %s', $postId, $e->getMessage()));
        }
    }
}
