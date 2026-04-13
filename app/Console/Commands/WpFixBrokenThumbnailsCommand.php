<?php

namespace App\Console\Commands;

use App\Models\Wp\WpPost;
use App\Services\WordPressMediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WpFixBrokenThumbnailsCommand extends Command
{
    protected $signature = 'wp:fix-broken-thumbnails
                            {--dry-run : Ne fait aucun update, rapport uniquement}
                            {--post-types=st_hotel,st_tours : Post types à scanner}
                            {--bad=14777,14778,14779,14780 : IDs d\'attachments suspects (CSV)}
                            {--map=14778:14762,14780:14762 : Remplacements bad->good (CSV "bad:good")}
                            {--limit=500 : Limite de posts scannés}
                            {--json : Sortie JSON uniquement}';

    protected $description = 'Corrige et audite les _thumbnail_id cassés (attachments manquants). Peut aussi nettoyer les galeries.';

    public function handle(WordPressMediaService $media): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $limit = $limit < 1 ? 500 : min(5000, $limit);

        $postTypes = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('post-types')))));
        if (empty($postTypes)) {
            $postTypes = ['st_hotel', 'st_tours'];
        }

        $badIds = array_values(array_filter(array_map('intval', explode(',', (string) $this->option('bad')))));
        $map = $this->parseMap((string) $this->option('map'));

        $report = [
            'dry_run' => $dryRun,
            'post_types' => $postTypes,
            'bad_ids' => $badIds,
            'map' => $map,
            'fixed' => [],
            'unchanged' => [],
            'invalid_thumbnail' => [],
            'gallery_cleaned' => [],
        ];

        $posts = WpPost::query()
            ->whereIn('post_type', $postTypes)
            ->whereIn('post_status', ['publish', 'draft'])
            ->orderBy('ID')
            ->limit($limit)
            ->get(['ID', 'post_type', 'post_title', 'post_status']);

        foreach ($posts as $post) {
            $postId = (int) $post->ID;
            $metaThumbRaw = (string) $post->getMeta('_thumbnail_id');
            $thumbId = is_numeric($metaThumbRaw) ? (int) $metaThumbRaw : 0;

            $didFix = false;
            $before = $thumbId;
            $after = $thumbId;

            if ($thumbId > 0 && in_array($thumbId, $badIds, true) && isset($map[$thumbId])) {
                $candidate = (int) $map[$thumbId];
                $valid = $media->validateAttachmentIdForDisplay($candidate);
                if ($valid) {
                    $after = $valid;
                    $didFix = true;
                    if (! $dryRun) {
                        $post->setMeta('_thumbnail_id', (string) $valid);
                    }
                } else {
                    // bad -> good mapping exists but good isn't valid on disk, so remove thumbnail.
                    $after = 0;
                    $didFix = true;
                    if (! $dryRun) {
                        $post->deleteMeta('_thumbnail_id');
                    }
                }
            } elseif ($thumbId > 0) {
                $valid = $media->validateAttachmentIdForDisplay($thumbId);
                if (! $valid) {
                    $report['invalid_thumbnail'][] = [
                        'post_id' => $postId,
                        'post_type' => $post->post_type,
                        'post_title' => $post->post_title,
                        'thumbnail_id' => $thumbId,
                    ];
                }
            }

            if ($didFix) {
                $report['fixed'][] = [
                    'post_id' => $postId,
                    'post_type' => $post->post_type,
                    'post_title' => $post->post_title,
                    'thumbnail_before' => $before,
                    'thumbnail_after' => $after,
                ];
            } else {
                $report['unchanged'][] = [
                    'post_id' => $postId,
                    'post_type' => $post->post_type,
                    'post_title' => $post->post_title,
                    'thumbnail_id' => $thumbId,
                ];
            }

            // Clean galleries: remove bad attachment ids, apply mapping if provided and valid.
            foreach (['_gallery', 'gallery', 'st_gallery'] as $gkey) {
                $raw = (string) $post->getMeta($gkey);
                if (trim($raw) === '') {
                    continue;
                }
                $ids = array_values(array_filter(array_map('intval', explode(',', $raw))));
                if (empty($ids)) {
                    continue;
                }
                $newIds = [];
                $changed = false;
                foreach ($ids as $id) {
                    $id = (int) $id;
                    if (in_array($id, $badIds, true) && isset($map[$id])) {
                        $replacement = (int) $map[$id];
                        $valid = $media->validateAttachmentIdForDisplay($replacement);
                        if ($valid) {
                            $newIds[] = $valid;
                        }
                        $changed = true;
                        continue;
                    }
                    if (in_array($id, $badIds, true)) {
                        $changed = true;
                        continue;
                    }
                    $valid = $media->validateAttachmentIdForDisplay($id);
                    if ($valid) {
                        $newIds[] = $valid;
                    } else {
                        $changed = true;
                    }
                }
                $newIds = array_values(array_unique($newIds));
                if ($changed) {
                    $report['gallery_cleaned'][] = [
                        'post_id' => $postId,
                        'meta_key' => $gkey,
                        'before' => $raw,
                        'after' => implode(',', $newIds),
                    ];
                    if (! $dryRun) {
                        if (empty($newIds)) {
                            $post->deleteMeta($gkey);
                        } else {
                            $post->setMeta($gkey, implode(',', $newIds));
                        }
                    }
                }
            }
        }

        // Also report other posts that explicitly use bad IDs as _thumbnail_id (fast query).
        // Important: DB::connection('wp')->table('postmeta') already applies the prefix.
        $hits = [];
        if (! empty($badIds)) {
            $hits = DB::connection('wp')->table('postmeta')
                ->select(['post_id', 'meta_value'])
                ->where('meta_key', '_thumbnail_id')
                ->whereIn('meta_value', array_map('strval', $badIds))
                ->limit(2000)
                ->get()
                ->map(fn ($r) => ['post_id' => (int) $r->post_id, 'thumbnail_id' => (int) $r->meta_value])
                ->all();
        }
        $report['thumbnail_bad_id_hits'] = $hits;

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return 0;
        }

        $this->info('Fix broken thumbnails report');
        $this->line('dry_run=' . ($dryRun ? '1' : '0'));
        $this->line('fixed=' . count($report['fixed']) . ' invalid_thumbnail=' . count($report['invalid_thumbnail']) . ' gallery_cleaned=' . count($report['gallery_cleaned']));
        if (! empty($report['fixed'])) {
            $this->newLine();
            $this->comment('Fixed:');
            foreach ($report['fixed'] as $row) {
                $this->line(sprintf(
                    'post_id=%d type=%s title="%s" thumbnail %d -> %d',
                    $row['post_id'],
                    $row['post_type'],
                    $row['post_title'],
                    $row['thumbnail_before'],
                    $row['thumbnail_after']
                ));
            }
        }

        return 0;
    }

    /**
     * @return array<int, int>
     */
    protected function parseMap(string $raw): array
    {
        $out = [];
        foreach (array_filter(array_map('trim', explode(',', $raw))) as $pair) {
            if (! str_contains($pair, ':')) {
                continue;
            }
            [$bad, $good] = array_map('trim', explode(':', $pair, 2));
            if (! is_numeric($bad) || ! is_numeric($good)) {
                continue;
            }
            $out[(int) $bad] = (int) $good;
        }

        return $out;
    }
}
