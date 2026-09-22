<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use App\Models\WpPostmeta;
use App\Services\WpTourSyncService;
use Illuminate\Console\Command;

/**
 * Publie les programmes historiques importés par {@see \Database\Seeders\LegacyProgramsSeeder}
 * vers WordPress, afin qu'ils apparaissent dans le catalogue admin (piloté par WP) et que
 * l'URL publique existe.
 *
 * Le post WordPress est créé en `draft` avec `post_name` = slug Laravel, c'est-à-dire le slug
 * historique : l'URL publique reste identique à celle de l'ancien site, seul le domaine change.
 *
 * Dry-run par défaut ; `--execute` écrit réellement.
 */
class LegacyPushToWpCommand extends Command
{
    protected $signature = 'legacy:push-wp
        {--execute : Écrit réellement dans WordPress (sinon simulation)}
        {--limit=0 : Nombre maximum de programmes traités}
        {--id=* : Ne traiter que ces identifiants historiques}';

    protected $description = 'Crée les tours WordPress manquants pour les programmes du catalogue historique.';

    public function __construct(private readonly WpTourSyncService $sync)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
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

        $stats = ['created' => 0, 'relinked' => 0, 'already' => 0, 'failed' => 0];
        $processed = 0;

        foreach ($voyages as $voyage) {
            if ($limit > 0 && $processed >= $limit) {
                break;
            }

            $legacyId = (int) data_get($voyage->logistics_meta, 'legacy_import.legacy_id');

            if ($voyage->wp_post_id) {
                $stats['already']++;

                continue;
            }

            // Le plugin WordPress a peut-être déjà importé ce programme : on se rattache au post
            // existant plutôt que d'en créer un second.
            $existingWpId = $this->findWpPostId($legacyId);
            if ($existingWpId) {
                $stats['relinked']++;
                $processed++;
                $this->line(sprintf('  #%d %s -> rattaché au post WP %d', $legacyId, $voyage->slug, $existingWpId));
                if ($execute) {
                    $voyage->update(['wp_post_id' => $existingWpId]);
                    $this->sync->updateWpTourFromLaravel($voyage->id, true);
                }

                continue;
            }

            $processed++;

            if (! $execute) {
                $stats['created']++;
                $this->line(sprintf('  #%d %s -> création WP (draft)', $legacyId, $voyage->slug));

                continue;
            }

            try {
                $result = $this->sync->createWpTourFromLaravel($voyage->id);
                WpPostmeta::setMeta((int) $result['wp_post_id'], Voyage::WP_LEGACY_ID_META, (string) $legacyId);
                $stats['created']++;
                $this->line(sprintf('  #%d %s -> post WP %d', $legacyId, $voyage->slug, $result['wp_post_id']));
            } catch (\Throwable $e) {
                $stats['failed']++;
                $this->error(sprintf('  #%d %s -> échec : %s', $legacyId, $voyage->slug, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s : %d créés, %d rattachés à un post existant, %d déjà liés, %d en échec.',
            $execute ? 'Publication WordPress' : 'Simulation (relancez avec --execute)',
            $stats['created'],
            $stats['relinked'],
            $stats['already'],
            $stats['failed']
        ));

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function findWpPostId(int $legacyId): ?int
    {
        try {
            $postId = (int) WpPostmeta::query()
                ->where('meta_key', Voyage::WP_LEGACY_ID_META)
                ->where('meta_value', (string) $legacyId)
                ->value('post_id');
        } catch (\Throwable $e) {
            return null;
        }

        if ($postId <= 0) {
            return null;
        }

        $taken = Voyage::query()->where('wp_post_id', $postId)->exists();

        return $taken ? null : $postId;
    }
}
