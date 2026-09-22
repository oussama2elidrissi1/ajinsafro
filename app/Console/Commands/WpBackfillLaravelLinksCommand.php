<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Models\Wp\WpPost;
use App\Models\Wp\WpPostMeta;
use Illuminate\Console\Command;

/**
 * Pose la meta `_aj_laravel_voyage_id` sur chaque tour WordPress rattaché à un voyage Laravel.
 *
 * Pourquoi c'est nécessaire : le catalogue public (`ajinsafro-traveler-home/templates/voyages.php`)
 * bascule en mode « uniquement les tours pilotés par Laravel » dès qu'au moins un tour publié porte
 * cette meta. Les tours qui ne l'ont pas disparaissent alors de la page. Or la meta n'était jamais
 * écrite, `WpTourSyncService` passant par `WpRepository` qui est inopérant (double préfixe de table).
 *
 * Dry-run par défaut ; `--execute` écrit.
 */
class WpBackfillLaravelLinksCommand extends Command
{
    protected $signature = 'wp:backfill-links {--execute : Écrit réellement les metas}';

    protected $description = 'Pose _aj_laravel_voyage_id sur les tours WordPress liés à un voyage Laravel.';

    public const META_KEY = '_aj_laravel_voyage_id';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');

        $voyages = Voyage::query()
            ->whereNotNull('wp_post_id')
            ->where('wp_post_id', '>', 0)
            ->orderBy('id')
            ->get(['id', 'wp_post_id', 'name']);

        if ($voyages->isEmpty()) {
            $this->warn('Aucun voyage Laravel rattaché à un tour WordPress.');

            return self::SUCCESS;
        }

        // Plusieurs voyages peuvent pointer le même tour : on retient l'id canonique (le plus petit),
        // même règle que le catalogue workspace.
        $canonical = $voyages
            ->groupBy(fn (Voyage $v) => (int) $v->wp_post_id)
            ->map(fn ($group) => (int) $group->min('id'));

        try {
            $existing = WpPostMeta::query()
                ->where('meta_key', self::META_KEY)
                ->whereIn('post_id', $canonical->keys()->all())
                ->get(['post_id', 'meta_value'])
                ->keyBy(fn ($m) => (int) $m->post_id);
        } catch (\Throwable $e) {
            $this->error('Accès WordPress impossible : '.$e->getMessage());

            return self::FAILURE;
        }

        $stats = ['ok' => 0, 'added' => 0, 'fixed' => 0];

        foreach ($canonical as $postId => $voyageId) {
            $current = $existing->get($postId);

            if ($current && (int) $current->meta_value === $voyageId) {
                $stats['ok']++;

                continue;
            }

            $action = $current ? 'fixed' : 'added';
            $stats[$action]++;
            $this->line(sprintf(
                '  %s tour WP %d -> voyage #%d%s',
                $current ? 'corrigé ' : 'ajouté  ',
                $postId,
                $voyageId,
                $current ? ' (valait '.$current->meta_value.')' : ''
            ));

            if (! $execute) {
                continue;
            }

            if ($current) {
                $current->meta_value = (string) $voyageId;
                $current->save();

                continue;
            }

            WpPostMeta::create([
                'post_id' => $postId,
                'meta_key' => self::META_KEY,
                'meta_value' => (string) $voyageId,
            ]);
        }

        $this->newLine();
        $this->info(sprintf(
            '%s : %d metas ajoutées, %d corrigées, %d déjà correctes.',
            $execute ? 'Backfill' : 'Simulation (relancez avec --execute)',
            $stats['added'],
            $stats['fixed'],
            $stats['ok']
        ));

        $this->reportOrphans($canonical->keys()->all());

        return self::SUCCESS;
    }

    /**
     * Tours publiés sans voyage Laravel : ils sont exclus du catalogue public par le filtre
     * « uniquement les tours pilotés par Laravel ».
     *
     * @param  list<int>  $linkedPostIds
     */
    private function reportOrphans(array $linkedPostIds): void
    {
        try {
            $orphans = WpPost::query()
                ->tours()
                ->where('post_status', 'publish')
                ->whereNotIn('ID', $linkedPostIds)
                ->get(['ID', 'post_title']);
        } catch (\Throwable $e) {
            return;
        }

        if ($orphans->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->warn(sprintf('%d tour(s) publié(s) sans voyage Laravel — masqués du catalogue public :', $orphans->count()));
        foreach ($orphans as $orphan) {
            $this->line(sprintf('  WP %d — %s', $orphan->ID, $orphan->post_title));
        }
    }
}
