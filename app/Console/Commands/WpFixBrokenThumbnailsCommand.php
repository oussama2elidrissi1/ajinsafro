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
                            {--post-ids= : Restreindre à une liste de posts.ID (CSV)}
                            {--bad=14777,14778,14779,14780 : IDs d\'attachments suspects (CSV)}
                            {--map=14778:14762,14780:14762 : Remplacements bad->good (CSV "bad:good")}
                            {--fix-invalid-only=1 : Ne corrige que si le thumbnail est réellement invalide}
                            {--remove-if-unfixable=0 : Si invalide et aucune alternative, supprimer _thumbnail_id (désactivé par défaut)}
                            {--limit=500 : Limite de posts scannés}
                            {--json : Sortie JSON uniquement}';

    protected $description = 'Corrige et audite les _thumbnail_id cassés (attachments manquants). Peut aussi nettoyer les galeries.';

    public function handle(WordPressMediaService $media): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fixInvalidOnly = (string) $this->option('fix-invalid-only') !== '0';
        $removeIfUnfixable = (string) $this->option('remove-if-unfixable') !== '0';
        $limit = (int) $this->option('limit');
        $limit = $limit < 1 ? 500 : min(5000, $limit);

        $postTypes = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('post-types')))));
        if (empty($postTypes)) {
            $postTypes = ['st_hotel', 'st_tours'];
        }
        $onlyPostIds = array_values(array_filter(array_map('intval', array_map('trim', explode(',', (string) $this->option('post-ids'))))));

        $badIds = array_values(array_filter(array_map('intval', explode(',', (string) $this->option('bad')))));
        $map = $this->parseMap((string) $this->option('map'));

        $report = [
            'dry_run' => $dryRun,
            'fix_invalid_only' => $fixInvalidOnly,
            'remove_if_unfixable' => $removeIfUnfixable,
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
            ->when(! empty($onlyPostIds), fn ($q) => $q->whereIn('ID', $onlyPostIds))
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
            $reason = '';
            $action = 'ignored';

            $thumbStatus = $thumbId > 0 ? $media->getAttachmentDisplayStatus($thumbId) : ['status' => 'valid', 'reason' => 'no_thumbnail'];
            $isInvalid = $thumbId > 0 && $thumbStatus['status'] === 'invalid';

            if ($thumbId > 0 && $thumbStatus['status'] !== 'valid') {
                $report['invalid_thumbnail'][] = [
                    'post_id' => $postId,
                    'post_type' => $post->post_type,
                    'post_title' => $post->post_title,
                    'thumbnail_id' => $thumbId,
                    'status' => $thumbStatus['status'],
                    'reason' => $thumbStatus['reason'],
                ];
            }

            if ($thumbId > 0) {
                // Règle: ne pas toucher si déjà valide (ou non vérifiable).
                if ($thumbStatus['status'] === 'valid') {
                    $action = 'kept_valid';
                } elseif ($thumbStatus['status'] === 'unknown') {
                    $action = 'kept_unknown';
                } elseif (! $fixInvalidOnly || $isInvalid) {
                    // Tentative 1: mapping explicite (bad -> good), mais uniquement si le thumb actuel est invalide.
                    if ($isInvalid && isset($map[$thumbId])) {
                        $candidate = (int) $map[$thumbId];
                        $candidateStatus = $media->getAttachmentDisplayStatus($candidate);
                        if ($candidateStatus['status'] === 'valid') {
                            $after = $candidate;
                            $didFix = true;
                            $reason = 'invalid_thumbnail_mapped_to_valid';
                            $action = 'replaced_thumbnail';
                            if (! $dryRun) {
                                $post->setMeta('_thumbnail_id', (string) $candidate);
                            }
                        } else {
                            $reason = 'invalid_thumbnail_mapping_target_not_valid';
                        }
                    }

                    // Tentative 2: fallback vers une image de galerie valide si thumbnail invalide et pas déjà corrigé.
                    if ($isInvalid && ! $didFix) {
                        $galleryCandidate = 0;
                        foreach (['_gallery', 'gallery', 'st_gallery'] as $gkey) {
                            $raw = (string) $post->getMeta($gkey);
                            if (trim($raw) === '') {
                                continue;
                            }
                            foreach (array_values(array_filter(array_map('intval', explode(',', $raw)))) as $gid) {
                                if ($media->getAttachmentDisplayStatus((int) $gid)['status'] === 'valid') {
                                    $galleryCandidate = (int) $gid;
                                    break 2;
                                }
                            }
                        }
                        if ($galleryCandidate > 0) {
                            $after = $galleryCandidate;
                            $didFix = true;
                            $reason = 'invalid_thumbnail_fallback_to_gallery';
                            $action = 'replaced_thumbnail';
                            if (! $dryRun) {
                                $post->setMeta('_thumbnail_id', (string) $galleryCandidate);
                            }
                        }
                    }

                    // Tentative 3: supprimer si invalide et non corrigeable.
                    if ($isInvalid && ! $didFix && $removeIfUnfixable) {
                        $after = 0;
                        $didFix = true;
                        $reason = $reason !== '' ? $reason.';removed_unfixable' : 'removed_unfixable_invalid_thumbnail';
                        $action = 'removed_thumbnail';
                        if (! $dryRun) {
                            $post->deleteMeta('_thumbnail_id');
                        }
                    }
                }
            }

            if ($didFix) {
                $report['fixed'][] = [
                    'post_id' => $postId,
                    'post_type' => $post->post_type,
                    'post_title' => $post->post_title,
                    'thumbnail_before' => $before,
                    'thumbnail_after' => $after,
                    'reason' => $reason,
                    'action' => $action,
                ];
            } else {
                $report['unchanged'][] = [
                    'post_id' => $postId,
                    'post_type' => $post->post_type,
                    'post_title' => $post->post_title,
                    'thumbnail_id' => $thumbId,
                    'status' => $thumbStatus['status'] ?? 'n/a',
                    'action' => $action,
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
