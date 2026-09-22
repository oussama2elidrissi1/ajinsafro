<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Models\Wp\WpPost as WpConnPost;
use App\Models\Wp\WpPostMeta as WpConnPostMeta;
use App\Models\WpPost as WpDefaultPost;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Diagnostic en lecture seule avant de publier le catalogue historique vers WordPress.
 *
 * Répond à trois questions :
 *  1. quel accès WordPress fonctionne réellement sur cet environnement ;
 *  2. quels programmes historiques existent déjà côté WordPress sous un autre slug
 *     (ex. `...-8900-dhs-2` au lieu de `...-8900-dhs-466`) — pousser créerait un doublon ;
 *  3. quels voyages Laravel font doublon entre eux.
 *
 * N'écrit rien.
 */
class LegacyCheckCommand extends Command
{
    protected $signature = 'legacy:check {--full : Liste aussi les programmes sans correspondance}';

    protected $description = 'Vérifie l\'accès WordPress et détecte les doublons avant publication du catalogue historique.';

    public function handle(): int
    {
        $this->checkWordPressAccess();
        $this->newLine();

        $voyages = Voyage::query()->get(['id', 'wp_post_id', 'name', 'slug', 'logistics_meta']);
        $legacy = $voyages->filter(fn (Voyage $v) => $v->isLegacyImport())->values();

        $this->line('<info>Programmes historiques en base :</info> '.$legacy->count().' / '.$voyages->count().' voyages Laravel.');

        if ($legacy->isEmpty()) {
            $this->warn('Rien à vérifier : lancez db:seed --class=LegacyProgramsSeeder.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->checkLaravelDuplicates($voyages, $legacy);
        $this->newLine();
        $this->checkWordPressOverlap($legacy);

        return self::SUCCESS;
    }

    /**
     * Base de comparaison d'un slug : on retire le suffixe numérique final, qui est soit
     * l'identifiant historique (`-466`), soit le compteur de dédoublonnage WordPress (`-2`).
     */
    public static function slugBase(?string $slug): string
    {
        $slug = trim((string) $slug);

        return (string) preg_replace('/-\d+$/', '', $slug);
    }

    private function checkWordPressAccess(): void
    {
        $this->line('<info>== Accès WordPress ==</info>');
        $this->line(sprintf(
            "  connexion 'wp' : base=%s prefix=%s",
            (string) config('database.connections.wp.database'),
            (string) config('database.connections.wp.prefix')
        ));
        $this->line(sprintf(
            "  connexion par défaut : %s / base=%s",
            (string) config('database.default'),
            (string) config('database.connections.'.config('database.default').'.database')
        ));

        $this->probe("App\\Models\\Wp\\WpPost (connexion 'wp' + prefix)", fn () => WpConnPost::query()->tours()->count().' tours');
        $this->probe("App\\Models\\WpPost (connexion par défaut, table cFdgeZ_posts)", fn () => WpDefaultPost::query()->where('post_type', 'st_tours')->count().' tours');
        $this->probe("App\\Models\\Wp\\WpPostMeta (connexion 'wp')", fn () => WpConnPostMeta::query()->where('meta_key', Voyage::WP_LEGACY_ID_META)->count().' metas '.Voyage::WP_LEGACY_ID_META);
        $this->probe('App\\Repositories\\WpRepository::getPost()', function () {
            $any = WpConnPost::query()->tours()->orderBy('ID')->first();
            if (! $any) {
                return 'aucun tour pour tester';
            }
            $post = app(\App\Repositories\WpRepository::class)->getPost((int) $any->ID);

            return $post ? 'lit le post #'.$any->ID : 'renvoie null pour le post #'.$any->ID;
        });
    }

    private function probe(string $label, callable $probe): void
    {
        try {
            $this->line(sprintf('  <fg=green>OK</>   %s : %s', $label, $probe()));
        } catch (\Throwable $e) {
            $this->line(sprintf('  <fg=red>KO</>   %s : %s', $label, Str::limit($e->getMessage(), 160)));
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Voyage>  $voyages
     * @param  \Illuminate\Support\Collection<int, Voyage>  $legacy
     */
    private function checkLaravelDuplicates($voyages, $legacy): void
    {
        $this->line('<info>== Doublons côté Laravel ==</info>');

        $byBase = $voyages->groupBy(fn (Voyage $v) => self::slugBase($v->slug));
        $dupes = $byBase->filter(fn ($group) => $group->count() > 1);

        if ($dupes->isEmpty()) {
            $this->line('  Aucun doublon de slug détecté.');

            return;
        }

        foreach ($dupes as $base => $group) {
            $this->line('  <comment>'.$base.'</comment>');
            foreach ($group as $v) {
                $this->line(sprintf(
                    '     voyage #%d | slug=%s | wp=%s | %s',
                    $v->id,
                    $v->slug,
                    $v->wp_post_id ?: '-',
                    $v->isLegacyImport() ? 'importé (legacy '.data_get($v->logistics_meta, 'legacy_import.legacy_id').')' : 'natif'
                ));
            }
        }

        $this->warn(sprintf('  %d groupes de doublons Laravel.', $dupes->count()));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Voyage>  $legacy
     */
    private function checkWordPressOverlap($legacy): void
    {
        $this->line('<info>== Correspondance avec les tours WordPress ==</info>');

        try {
            $tours = WpConnPost::query()->tours()->get(['ID', 'post_name', 'post_title', 'post_status']);
        } catch (\Throwable $e) {
            $this->error('  Lecture des tours WordPress impossible : '.$e->getMessage());

            return;
        }

        $this->line('  '.$tours->count().' tours WordPress lus.');

        $byExactName = $tours->keyBy(fn ($t) => (string) $t->post_name);
        $byBaseName = $tours->groupBy(fn ($t) => self::slugBase((string) $t->post_name));

        $legacyIdByPost = collect();
        try {
            $legacyIdByPost = WpConnPostMeta::query()
                ->where('meta_key', Voyage::WP_LEGACY_ID_META)
                ->get(['post_id', 'meta_value'])
                ->mapWithKeys(fn ($m) => [(int) $m->meta_value => (int) $m->post_id]);
        } catch (\Throwable $e) {
            // Meta absente : le plugin WordPress n'a pas (encore) tourné.
        }

        $stats = ['deja_lie' => 0, 'meta' => 0, 'slug_exact' => 0, 'slug_base' => 0, 'aucun' => 0];

        foreach ($legacy as $voyage) {
            $legacyId = (int) data_get($voyage->logistics_meta, 'legacy_import.legacy_id');
            $base = self::slugBase($voyage->slug);

            if ($voyage->wp_post_id) {
                $stats['deja_lie']++;

                continue;
            }

            if ($legacyIdByPost->has($legacyId)) {
                $stats['meta']++;
                $this->line(sprintf('  <fg=green>meta</>        legacy %-4d -> post WP %d', $legacyId, $legacyIdByPost->get($legacyId)));

                continue;
            }

            if ($byExactName->has($voyage->slug)) {
                $stats['slug_exact']++;
                $this->line(sprintf('  <fg=green>slug exact</>  legacy %-4d -> post WP %d', $legacyId, $byExactName->get($voyage->slug)->ID));

                continue;
            }

            $candidates = $byBaseName->get($base);
            if ($candidates && $candidates->isNotEmpty()) {
                $stats['slug_base']++;
                foreach ($candidates as $c) {
                    $this->line(sprintf(
                        '  <fg=yellow>slug proche</> legacy %-4d -> post WP %d (%s) « %s »',
                        $legacyId,
                        $c->ID,
                        $c->post_name,
                        Str::limit((string) $c->post_title, 60)
                    ));
                }

                continue;
            }

            $stats['aucun']++;
            if ($this->option('full')) {
                $this->line(sprintf('  <fg=gray>aucun</>       legacy %-4d (%s)', $legacyId, $voyage->slug));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Bilan : %d déjà liés, %d retrouvés par meta, %d par slug exact, %d par slug proche (doublon probable), %d sans équivalent WordPress.',
            $stats['deja_lie'],
            $stats['meta'],
            $stats['slug_exact'],
            $stats['slug_base'],
            $stats['aucun']
        ));

        if ($stats['slug_base'] > 0) {
            $this->warn('Ne lancez pas legacy:push-wp --execute tant que les « slug proche » ne sont pas arbitrés : ils créeraient des tours en double.');
        }
    }
}
