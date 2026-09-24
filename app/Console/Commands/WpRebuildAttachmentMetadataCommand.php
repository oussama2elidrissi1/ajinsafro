<?php

namespace App\Console\Commands;

use App\Models\Wp\WpPost;
use App\Models\Wp\WpPostMeta;
use App\Services\Wp\WpHeroImageService;
use Illuminate\Console\Command;

/**
 * Reconstruit `_wp_attachment_metadata` des images WordPress sans largeur ni hauteur.
 *
 * Sans ces deux valeurs, wp_calculate_image_srcset() renonce et WordPress sert le fichier
 * pleine taille : une carte de 300 px reçoit un visuel de 1024 px. Les métadonnées sont
 * recalculées depuis le fichier réel et les déclinaisons intermédiaires régénérées ; le
 * chemin, le guid et le type MIME ne sont jamais modifiés.
 *
 * Un attachment dont le fichier est absent du disque est signalé et ignoré : on n'écrit
 * jamais des métadonnées vides, ce serait aggraver le cas.
 *
 * Convention de la famille `wp:*` : écrit par défaut, `--dry-run` pour un simple rapport.
 */
class WpRebuildAttachmentMetadataCommand extends Command
{
    protected $signature = 'wp:rebuild-attachment-metadata
        {--dry-run : Liste les attachments concernés sans rien écrire}
        {--id=* : Ne traiter que ces attachments, même si leurs dimensions sont présentes}
        {--limit=50 : Nombre maximal d’attachments traités}';

    protected $description = 'Recalcule les métadonnées des images WordPress sans dimensions, pour que leur srcset fonctionne.';

    public function handle(WpHeroImageService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(1, (int) $this->option('limit'));
        $onlyIds = array_values(array_filter(array_map('intval', (array) $this->option('id'))));

        $targets = $onlyIds !== [] ? $onlyIds : $this->attachmentsWithoutDimensions($limit);

        if ($targets === []) {
            $this->info('Aucun attachment image sans dimensions.');

            return self::SUCCESS;
        }

        $this->line(sprintf('%d attachment(s) à examiner.', count($targets)));
        $this->newLine();

        $stats = ['reconstruits' => 0, 'absents' => 0, 'illisibles' => 0, 'non_images' => 0];

        foreach ($targets as $attachmentId) {
            $mime = (string) WpPost::query()->where('ID', $attachmentId)->value('post_mime_type');
            if (! str_starts_with($mime, 'image/')) {
                $stats['non_images']++;
                $this->warn(sprintf('  #%-6d pas une image (%s), ignoré', $attachmentId, $mime ?: 'type inconnu'));

                continue;
            }

            $relative = (string) WpHeroImageService::getAttachedFile($attachmentId);
            $fullPath = WpHeroImageService::attachmentAbsolutePath($attachmentId);

            if ($fullPath === null || ! is_file($fullPath)) {
                $stats['absents']++;
                $this->warn(sprintf('  #%-6d %-44s fichier absent, ignoré', $attachmentId, $this->short($relative)));

                continue;
            }

            if ($dryRun) {
                $this->line(sprintf('  #%-6d %-44s à reconstruire', $attachmentId, $this->short($relative)));
                $stats['reconstruits']++;

                continue;
            }

            $metadata = $service->rebuildAttachmentMetadata($attachmentId);

            if ($metadata === null) {
                $stats['illisibles']++;
                $this->warn(sprintf('  #%-6d %-44s illisible, ignoré', $attachmentId, $this->short($relative)));

                continue;
            }

            $stats['reconstruits']++;
            $this->line(sprintf(
                '  #%-6d %-44s %dx%d, %d déclinaison(s)',
                $attachmentId,
                $this->short($relative),
                (int) ($metadata['width'] ?? 0),
                (int) ($metadata['height'] ?? 0),
                count($metadata['sizes'] ?? [])
            ));
        }

        $this->newLine();
        $this->info(sprintf(
            '%s : %d reconstruit(s), %d absent(s), %d illisible(s), %d hors image.',
            $dryRun ? 'Simulation (relancez sans --dry-run)' : 'Reconstruction',
            $stats['reconstruits'],
            $stats['absents'],
            $stats['illisibles'],
            $stats['non_images']
        ));

        return self::SUCCESS;
    }

    /**
     * Attachments dont `_wp_attachment_metadata` décrit un fichier sans largeur ni hauteur.
     *
     * @return list<int>
     */
    private function attachmentsWithoutDimensions(int $limit): array
    {
        $ids = [];

        $rows = WpPostMeta::query()
            ->where('meta_key', '_wp_attachment_metadata')
            ->orderBy('post_id')
            ->cursor();

        foreach ($rows as $row) {
            $meta = @unserialize((string) $row->meta_value);
            if (! is_array($meta) || empty($meta['file'])) {
                continue;
            }
            if (! empty($meta['width']) && ! empty($meta['height'])) {
                continue;
            }

            $ids[] = (int) $row->post_id;
            if (count($ids) >= $limit) {
                break;
            }
        }

        return $ids;
    }

    private function short(string $relative): string
    {
        $name = basename($relative);

        return strlen($name) > 42 ? substr($name, 0, 39).'...' : ($name !== '' ? $name : '(chemin vide)');
    }
}
