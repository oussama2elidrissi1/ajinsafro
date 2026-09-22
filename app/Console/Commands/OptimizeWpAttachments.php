<?php

namespace App\Console\Commands;

use App\Services\Wp\WpHeroImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rattrapage des visuels deja televerses dans wp-content/uploads avant que
 * l'upload ne les redimensionne (voir WpHeroImageService) : re-encode en WebP
 * plafonne, regenere les declinaisons medium/medium_large/large et fait pointer
 * l'attachment dessus. Les fichiers d'origine restent en place.
 */
class OptimizeWpAttachments extends Command
{
    protected $signature = 'wp:optimize-attachments
        {--dry-run : Liste les fichiers concernes sans rien ecrire}
        {--max-width=1600 : Largeur maximale conservee}
        {--min-size=250 : Ne traiter que les fichiers au-dela de cette taille, en Ko}
        {--pattern=hero- : Filtre sur _wp_attached_file (chaine vide = toutes les images)}
        {--limit=200 : Nombre maximal d’attachments traites}';

    protected $description = 'Redimensionne et convertit en WebP les images WordPress déjà en ligne, et régénère leurs déclinaisons srcset.';

    public function handle(WpHeroImageService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $maxWidth = max(320, (int) $this->option('max-width'));
        $minBytes = max(0, (int) $this->option('min-size')) * 1024;
        $pattern = trim((string) $this->option('pattern'));
        $limit = max(1, (int) $this->option('limit'));

        $query = DB::connection('wp')->table('posts')
            ->join('postmeta', 'postmeta.post_id', '=', 'posts.ID')
            ->where('posts.post_type', 'attachment')
            ->where('posts.post_mime_type', 'like', 'image/%')
            ->where('postmeta.meta_key', '_wp_attached_file')
            ->orderByDesc('posts.ID')
            ->limit($limit)
            ->select('posts.ID', 'postmeta.meta_value as attached_file');

        if ($pattern !== '') {
            $query->where('postmeta.meta_value', 'like', '%'.$pattern.'%');
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            $this->info('Aucun attachment ne correspond au filtre.');

            return self::SUCCESS;
        }

        $converted = 0;
        $savedBytes = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $attachmentId = (int) $row->ID;
            $fullPath = WpHeroImageService::attachmentAbsolutePath($attachmentId);
            if ($fullPath === null) {
                $skipped++;
                continue;
            }

            $size = (int) filesize($fullPath);
            if ($size < $minBytes) {
                continue;
            }

            if ($dryRun) {
                $this->line(sprintf('  #%d %s (%s Ko)', $attachmentId, $row->attached_file, number_format($size / 1024, 0, ',', ' ')));
                $converted++;
                continue;
            }

            try {
                $result = $service->optimizeExistingAttachment($attachmentId, $maxWidth);
            } catch (\Throwable $e) {
                // Un fichier illisible ne doit jamais arreter le lot en cours.
                $skipped++;
                $this->warn(sprintf('  #%d %s : ignoré (%s)', $attachmentId, $row->attached_file, $e->getMessage()));
                continue;
            }

            if ($result === null) {
                $skipped++;
                $this->line(sprintf('  #%d %s : inchangé (gain insuffisant ou GD absent)', $attachmentId, $row->attached_file));
                continue;
            }

            $converted++;
            $savedBytes += max(0, $result['before'] - $result['after']);
            $this->line(sprintf(
                '  #%d %s → %s (%s Ko → %s Ko)',
                $attachmentId,
                $result['from'],
                $result['to'],
                number_format($result['before'] / 1024, 0, ',', ' '),
                number_format($result['after'] / 1024, 0, ',', ' ')
            ));
        }

        if ($dryRun) {
            $this->info(sprintf('Simulation : %d image(s) seraient traitées, rien n’a été écrit.', $converted));

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%d image(s) optimisée(s), %d ignorée(s), %s Mo économisés. Les fichiers d’origine sont conservés.',
            $converted,
            $skipped,
            number_format($savedBytes / 1048576, 1, ',', ' ')
        ));

        return self::SUCCESS;
    }
}
