<?php

namespace App\Console\Commands;

use App\Models\Wp\WpPost;
use App\Services\WordPressMediaService;
use Illuminate\Console\Command;

class WpAuditMediaRefsCommand extends Command
{
    protected $signature = 'wp:audit-media-refs
                            {--post-types=st_hotel,st_tours : Types à scanner}
                            {--limit=5000 : Limite de posts}
                            {--json : Sortie JSON uniquement}';

    protected $description = 'Audit complet: détecte tous les _thumbnail_id et galeries pointant vers des attachments invalides (ou non vérifiables).';

    public function handle(WordPressMediaService $media): int
    {
        $postTypes = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('post-types')))));
        if (empty($postTypes)) {
            $postTypes = ['st_hotel', 'st_tours'];
        }
        $limit = (int) $this->option('limit');
        $limit = $limit < 1 ? 5000 : min(50000, $limit);

        $rows = WpPost::query()
            ->whereIn('post_type', $postTypes)
            ->whereIn('post_status', ['publish', 'draft'])
            ->orderBy('ID')
            ->limit($limit)
            ->get(['ID', 'post_type', 'post_title']);

        $report = [
            'post_types' => $postTypes,
            'scanned' => count($rows),
            'invalid_thumbnails' => [],
            'invalid_gallery_ids' => [],
            'unknown_thumbnails' => [],
        ];

        foreach ($rows as $post) {
            $postId = (int) $post->ID;
            $thumbRaw = (string) $post->getMeta('_thumbnail_id', '');
            $thumbId = is_numeric($thumbRaw) ? (int) $thumbRaw : 0;
            if ($thumbId > 0) {
                $st = $media->getAttachmentDisplayStatus($thumbId);
                if ($st['status'] === 'invalid') {
                    $report['invalid_thumbnails'][] = [
                        'post_id' => $postId,
                        'post_type' => $post->post_type,
                        'post_title' => $post->post_title,
                        'thumbnail_id' => $thumbId,
                        'reason' => $st['reason'],
                        'attached_file' => $st['attached_file'] ?? null,
                    ];
                } elseif ($st['status'] === 'unknown') {
                    $report['unknown_thumbnails'][] = [
                        'post_id' => $postId,
                        'post_type' => $post->post_type,
                        'post_title' => $post->post_title,
                        'thumbnail_id' => $thumbId,
                        'reason' => $st['reason'],
                        'attached_file' => $st['attached_file'] ?? null,
                    ];
                }
            }

            foreach (['_gallery', 'gallery', 'st_gallery'] as $gkey) {
                $raw = (string) $post->getMeta($gkey, '');
                if (trim($raw) === '') {
                    continue;
                }
                $ids = array_values(array_filter(array_map('intval', explode(',', $raw))));
                foreach ($ids as $gid) {
                    $st = $media->getAttachmentDisplayStatus((int) $gid);
                    if ($st['status'] === 'invalid') {
                        $report['invalid_gallery_ids'][] = [
                            'post_id' => $postId,
                            'post_type' => $post->post_type,
                            'post_title' => $post->post_title,
                            'meta_key' => $gkey,
                            'attachment_id' => (int) $gid,
                            'reason' => $st['reason'],
                            'attached_file' => $st['attached_file'] ?? null,
                        ];
                    }
                }
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            return 0;
        }

        $this->info('Scanned: '.$report['scanned']);
        $this->line('invalid_thumbnails='.count($report['invalid_thumbnails']).' invalid_gallery_ids='.count($report['invalid_gallery_ids']).' unknown_thumbnails='.count($report['unknown_thumbnails']));

        return 0;
    }
}

