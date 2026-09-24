<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Models\Wp\WpPost;
use App\Models\Wp\WpPostMeta;
use App\Services\Wp\WpPostPayloadBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Publie les programmes historiques importés par {@see \Database\Seeders\LegacyProgramsSeeder}
 * vers WordPress, afin qu'ils apparaissent dans le catalogue admin (piloté par WP).
 *
 * Le tour est créé en `draft` avec `post_name` = slug Laravel, c'est-à-dire le slug historique :
 * l'URL publique reste celle de l'ancien site, seul le domaine change.
 *
 * Passe par la connexion `wp` ({@see WpPost}), le même accès que le catalogue admin.
 *
 * Protection anti-doublon : un programme dont un tour WordPress porte déjà le même slug « de base »
 * (suffixe numérique retiré : `...-8900-dhs-2` ≡ `...-8900-dhs-466`) n'est jamais recréé. Il est
 * signalé, et `--adopt` permet de rattacher le voyage Laravel au tour existant.
 *
 * Dry-run par défaut ; `--execute` écrit réellement.
 */
class LegacyPushToWpCommand extends Command
{
    protected $signature = 'legacy:push-wp
        {--execute : Écrit réellement dans WordPress (sinon simulation)}
        {--adopt : Rattache au tour WordPress existant quand un slug proche est trouvé}
        {--limit=0 : Nombre maximum de programmes traités}
        {--id=* : Ne traiter que ces identifiants historiques}';

    protected $description = 'Crée (ou rattache) les tours WordPress des programmes du catalogue historique.';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $adopt = (bool) $this->option('adopt');
        $limit = max(0, (int) $this->option('limit'));
        $onlyIds = array_map('intval', (array) $this->option('id'));

        $voyages = Voyage::query()
            ->where('logistics_meta', 'like', '%"legacy_import"%')
            ->orderBy('id')
            ->get()
            ->filter(fn (Voyage $v) => $v->isLegacyImport())
            ->filter(fn (Voyage $v) => $onlyIds === []
                || in_array((int) data_get($v->logistics_meta, 'legacy_import.legacy_id'), $onlyIds, true));

        if ($voyages->isEmpty()) {
            $this->warn('Aucun programme historique trouvé. Lancez d\'abord db:seed --class=LegacyProgramsSeeder.');

            return self::SUCCESS;
        }

        try {
            $tours = WpPost::query()->tours()->get(['ID', 'post_name', 'post_title', 'post_status']);
        } catch (\Throwable $e) {
            $this->error('Lecture des tours WordPress impossible : '.$e->getMessage());
            $this->line('Lancez `php artisan legacy:check` pour diagnostiquer l\'accès WordPress.');

            return self::FAILURE;
        }

        $byExactName = $tours->keyBy(fn ($t) => (string) $t->post_name);
        $byBaseName = $tours->groupBy(fn ($t) => LegacyCheckCommand::slugBase((string) $t->post_name));
        $linkedPostIds = Voyage::query()->whereNotNull('wp_post_id')->pluck('wp_post_id')->map(fn ($id) => (int) $id)->all();
        $existingTourIds = $tours->keyBy(fn ($t) => (int) $t->ID);

        $stats = ['created' => 0, 'linked' => 0, 'already' => 0, 'stale' => 0, 'conflict' => 0, 'failed' => 0];
        $processed = 0;

        foreach ($voyages as $voyage) {
            if ($limit > 0 && $processed >= $limit) {
                break;
            }

            $legacyId = (int) data_get($voyage->logistics_meta, 'legacy_import.legacy_id');

            if ($voyage->wp_post_id && $existingTourIds->has((int) $voyage->wp_post_id)) {
                $stats['already']++;

                continue;
            }

            // Lien mort : le tour WordPress a été supprimé depuis. Sans cette reprise le programme
            // reste absent du catalogue et son ancienne URL répond 404 — une URL indexée perdue.
            if ($voyage->wp_post_id) {
                $stats['stale']++;
                $this->line(sprintf('  <fg=yellow>lien mort</> legacy %-4d : tour WP %d supprimé, la fiche est recréée', $legacyId, (int) $voyage->wp_post_id));
                if ($execute) {
                    $voyage->update(['wp_post_id' => null]);
                }
                $voyage->wp_post_id = null;
            }

            $exact = $byExactName->get($voyage->slug);
            $candidates = $exact
                ? collect([$exact])
                : ($byBaseName->get(LegacyCheckCommand::slugBase($voyage->slug)) ?? collect());

            // Plusieurs tours WordPress portent le même slug de base : impossible de trancher
            // automatiquement sans risquer de rattacher la fiche au mauvais tour.
            if ($candidates->count() > 1) {
                $processed++;
                $stats['conflict']++;
                $this->line(sprintf('  <fg=yellow>ambigu</>   legacy %-4d %s : %d tours WP candidats', $legacyId, Str::limit($voyage->slug, 45), $candidates->count()));
                foreach ($candidates as $c) {
                    $this->line(sprintf('                 -> WP %d (%s)', $c->ID, $c->post_name));
                }

                continue;
            }

            $match = $candidates->first();

            if ($match) {
                $processed++;
                $takenByAnother = in_array((int) $match->ID, $linkedPostIds, true);

                if (! $adopt) {
                    $stats['conflict']++;
                    $this->line(sprintf(
                        '  <fg=yellow>doublon</>  legacy %-4d %s -> tour WP existant %d (%s)%s',
                        $legacyId,
                        Str::limit($voyage->slug, 50),
                        $match->ID,
                        $match->post_name,
                        $takenByAnother ? ' [déjà lié à un autre voyage]' : ''
                    ));

                    continue;
                }

                if ($takenByAnother) {
                    $stats['conflict']++;
                    $this->line(sprintf('  <fg=yellow>ignoré</>   legacy %-4d : tour WP %d déjà lié à un autre voyage Laravel', $legacyId, $match->ID));

                    continue;
                }

                $stats['linked']++;
                $this->line(sprintf('  <fg=green>rattaché</> legacy %-4d -> tour WP %d (%s)', $legacyId, $match->ID, $match->post_name));

                if ($execute) {
                    $voyage->update(['wp_post_id' => (int) $match->ID]);
                    $this->writeMeta((int) $match->ID, Voyage::WP_LEGACY_ID_META, (string) $legacyId);
                    $this->writeMeta((int) $match->ID, '_aj_laravel_voyage_id', (string) $voyage->id);
                    $this->writeLegacyPathPrefix((int) $match->ID, $voyage);
                    $linkedPostIds[] = (int) $match->ID;
                }

                continue;
            }

            $processed++;

            if (! $execute) {
                $stats['created']++;
                $this->line(sprintf('  <fg=green>création</> legacy %-4d %s (draft)', $legacyId, Str::limit($voyage->slug, 60)));

                continue;
            }

            try {
                $postId = $this->createTour($voyage, $legacyId);
                $stats['created']++;
                $linkedPostIds[] = $postId;
                $this->line(sprintf('  <fg=green>créé</>     legacy %-4d -> tour WP %d', $legacyId, $postId));
            } catch (\Throwable $e) {
                $stats['failed']++;
                $this->error(sprintf('  legacy %-4d %s -> échec : %s', $legacyId, $voyage->slug, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s : %d créés, %d rattachés, %d déjà liés, %d liens morts repris, %d doublons non traités, %d en échec.',
            $execute ? 'Publication WordPress' : 'Simulation (relancez avec --execute)',
            $stats['created'],
            $stats['linked'],
            $stats['already'],
            $stats['stale'],
            $stats['conflict'],
            $stats['failed']
        ));

        if ($stats['conflict'] > 0 && ! $adopt) {
            $this->warn('Doublons détectés : relancez avec --adopt pour rattacher le voyage Laravel au tour WordPress existant.');
        }

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Crée le tour WordPress en brouillon et pose les métas commerciales et de liaison.
     */
    private function createTour(Voyage $voyage, int $legacyId): int
    {
        $now = Carbon::now();
        $nowGmt = Carbon::now('GMT');

        $post = WpPost::create(WpPostPayloadBuilder::buildWpPostPayload([
            'post_type' => 'st_tours',
            'post_title' => (string) $voyage->name,
            'post_name' => (string) $voyage->slug,
            'post_content' => (string) ($voyage->description ?? ''),
            'post_excerpt' => (string) ($voyage->accroche ?? ''),
            // Brouillon : la fiche historique n'est pas vendable tant qu'elle est « À compléter ».
            'post_status' => 'draft',
        ], [
            'post_author' => 0,
            'post_date' => $now->toDateTimeString(),
            'post_date_gmt' => $nowGmt->toDateTimeString(),
            'post_modified' => $now->toDateTimeString(),
            'post_modified_gmt' => $nowGmt->toDateTimeString(),
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        ]));

        $postId = (int) $post->ID;

        $voyage->update(['wp_post_id' => $postId]);

        $metas = [
            '_aj_laravel_voyage_id' => (string) $voyage->id,
            Voyage::WP_LEGACY_ID_META => (string) $legacyId,
            'tour_price_by' => (string) ($voyage->tour_price_by ?: 'person'),
            'tours_include' => implode("\n", (array) ($voyage->tours_include ?? [])),
            'tours_exclude' => implode("\n", (array) ($voyage->tours_exclude ?? [])),
        ];

        if ($voyage->price_from !== null && (int) $voyage->price_from > 0) {
            $metas['adult_price'] = (string) (int) $voyage->price_from;
            $metas['min_price'] = (string) (int) $voyage->price_from;
        }
        if (preg_match('/(\d+)/', (string) $voyage->duration_text, $m) && (int) $m[1] > 0) {
            $metas['duration_day'] = (string) (int) $m[1];
        }
        if (! empty($voyage->destination)) {
            $metas['address'] = (string) $voyage->destination;
        }

        foreach ($metas as $key => $value) {
            $this->writeMeta($postId, $key, (string) $value);
        }

        $this->writeLegacyPathPrefix($postId, $voyage);

        return $postId;
    }

    /**
     * Préfixe de chemin historique lu par le plugin WordPress (AJTB_Legacy_Permalinks) pour
     * servir la fiche sous son ancienne URL `/voyage-national/...` ou `/voyages-international/...`.
     */
    private function writeLegacyPathPrefix(int $postId, Voyage $voyage): void
    {
        $prefix = (string) data_get($voyage->logistics_meta, 'seo.legacy_path_prefix', '');

        if ($prefix !== '') {
            $this->writeMeta($postId, '_aj_legacy_path_prefix', $prefix);
        }
    }

    private function writeMeta(int $postId, string $key, string $value): void
    {
        $existing = WpPostMeta::query()->where('post_id', $postId)->where('meta_key', $key)->first();

        if ($existing) {
            $existing->meta_value = $value;
            $existing->save();

            return;
        }

        WpPostMeta::create(['post_id' => $postId, 'meta_key' => $key, 'meta_value' => $value]);
    }
}
