<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Models\Wp\WpPost;
use App\Models\Wp\WpPostMeta;
use Illuminate\Console\Command;

/**
 * Aligne WordPress sur les chemins historiques stockés côté Laravel, pour que chaque ancienne
 * URL d'ajinsafro.ma réponde à l'identique sur le nouveau site.
 *
 * Le plugin tour-bridge sert /<préfixe>/<slug> quand le tour porte la meta
 * `_aj_legacy_path_prefix`. `legacy:push-wp` ne la pose qu'à la création du tour : les fiches
 * rattachées à un tour existant (`wp:backfill-links`) ne l'ont jamais reçue — 84 sur 143 à
 * l'audit du 24/09/2026, dont les anciennes URL répondaient 404.
 *
 * Le permalien historique est construit sur `post_name` : quand il diverge de l'ancien slug
 * (fiches natives créées avant l'import, sans le suffixe -<id>), l'URL n'est plus l'ancienne.
 * `--rename-slugs` réaligne `post_name` sur l'ancien slug, seulement s'il est libre.
 *
 * Simulation par défaut ; `--execute` écrit.
 */
class LegacySyncPermalinksCommand extends Command
{
    protected $signature = 'legacy:sync-permalinks
        {--execute : Écrit réellement (sinon simulation)}
        {--rename-slugs : Réaligne post_name sur l’ancien slug quand il est libre}
        {--id=* : Ne traiter que ces identifiants historiques}';

    protected $description = 'Pose le préfixe de chemin historique et, sur demande, l’ancien slug sur les tours WordPress des fiches importées.';

    /** Doit rester identique à AJTB_Legacy_Permalinks::PREFIXES côté WordPress. */
    private const PREFIXES = ['voyage-national', 'voyages-international', 'voyages-organisees'];

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $rename = (bool) $this->option('rename-slugs');
        $onlyIds = array_map('intval', (array) $this->option('id'));

        $voyages = Voyage::query()
            ->whereNotNull('wp_post_id')
            ->orderBy('id')
            ->get()
            ->filter(fn (Voyage $v) => $v->isLegacyImport() && $v->legacyPath() !== null)
            ->filter(fn (Voyage $v) => $onlyIds === [] || in_array($this->legacyId($v), $onlyIds, true));

        if ($voyages->isEmpty()) {
            $this->info('Aucune fiche historique avec chemin et tour WordPress.');

            return self::SUCCESS;
        }

        $posts = WpPost::query()
            ->where('post_type', 'st_tours')
            ->whereIn('ID', $voyages->pluck('wp_post_id')->map('intval')->all())
            ->get(['ID', 'post_name'])
            ->keyBy('ID');
        $takenNames = WpPost::query()->where('post_type', 'st_tours')->pluck('ID', 'post_name');

        $stats = ['prefixes' => 0, 'identifiants' => 0, 'slugs' => 0, 'slugs_pris' => 0, 'prefixe_inconnu' => 0, 'tour_absent' => 0, 'conformes' => 0];

        foreach ($voyages as $voyage) {
            $wp = (int) $voyage->wp_post_id;
            $legacyId = $this->legacyId($voyage);
            $post = $posts->get($wp);
            if ($post === null) {
                $stats['tour_absent']++;
                $this->warn(sprintf('  legacy %-4d tour WordPress %d introuvable', $legacyId, $wp));

                continue;
            }

            $segments = explode('/', trim((string) $voyage->legacyPath(), '/'));
            $prefix = $segments[0] ?? '';
            $oldSlug = $segments[1] ?? '';
            if (! in_array($prefix, self::PREFIXES, true) || $oldSlug === '') {
                $stats['prefixe_inconnu']++;
                $this->warn(sprintf('  legacy %-4d chemin historique hors préfixes connus : %s', $legacyId, $voyage->legacyPath()));

                continue;
            }

            $actions = [];

            if ((string) $this->meta($wp, '_aj_legacy_path_prefix') !== $prefix) {
                if ($execute) {
                    $this->writeMeta($wp, '_aj_legacy_path_prefix', $prefix);
                }
                $actions[] = 'préfixe ' . $prefix;
                $stats['prefixes']++;
            }

            if ((string) $this->meta($wp, Voyage::WP_LEGACY_ID_META) !== (string) $legacyId) {
                if ($execute) {
                    $this->writeMeta($wp, Voyage::WP_LEGACY_ID_META, (string) $legacyId);
                }
                $actions[] = 'identifiant';
                $stats['identifiants']++;
            }

            if ((string) $post->post_name !== $oldSlug) {
                $owner = $takenNames->get($oldSlug);
                if ($owner !== null && (int) $owner !== $wp) {
                    $stats['slugs_pris']++;
                    $this->warn(sprintf('  legacy %-4d ancien slug déjà pris par le tour %d : %s', $legacyId, (int) $owner, $oldSlug));
                } elseif ($rename) {
                    if ($execute) {
                        WpPost::query()->where('ID', $wp)->update(['post_name' => $oldSlug]);
                        $takenNames->put($oldSlug, $wp);
                    }
                    $actions[] = 'slug → ' . $oldSlug;
                    $stats['slugs']++;
                } else {
                    $this->line(sprintf('  legacy %-4d slug divergent (relancez avec --rename-slugs) : %s ≠ %s', $legacyId, (string) $post->post_name, $oldSlug));
                }
            }

            if ($actions === []) {
                $stats['conformes']++;

                continue;
            }
            $this->line(sprintf('  legacy %-4d wp %-6d %s', $legacyId, $wp, implode(', ', $actions)));
        }

        $this->newLine();
        foreach ($stats as $key => $n) {
            $this->line(sprintf('  %-18s %d', $key, $n));
        }
        $this->newLine();
        $this->info($execute
            ? 'Permaliens historiques synchronisés. Videz le cache de pages et le sitemap WordPress.'
            : 'Simulation. Relancez avec --execute' . ($rename ? ' --rename-slugs' : '') . ' pour appliquer.');

        return self::SUCCESS;
    }

    private function legacyId(Voyage $voyage): int
    {
        return (int) data_get($voyage->logistics_meta, 'legacy_import.legacy_id');
    }

    private function meta(int $postId, string $key): ?string
    {
        $value = WpPostMeta::query()->where('post_id', $postId)->where('meta_key', $key)->value('meta_value');

        return $value === null ? null : (string) $value;
    }

    private function writeMeta(int $postId, string $key, string $value): void
    {
        WpPostMeta::updateOrCreate(
            ['post_id' => $postId, 'meta_key' => $key],
            ['meta_value' => $value]
        );
    }
}
