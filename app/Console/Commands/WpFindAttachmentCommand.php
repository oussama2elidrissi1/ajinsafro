<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WpFindAttachmentCommand extends Command
{
    protected $signature = 'wp:find-attachment
                            {needle : Ex: 2026/04/3d67d024-z-cahpIdm7.webp}
                            {--limit=50 : Max résultats}
                            {--json : Sortie JSON uniquement}';

    protected $description = 'Trouve un attachment à partir de _wp_attached_file (ou metadata) et liste les posts qui le référencent (_thumbnail_id / galleries).';

    public function handle(): int
    {
        $needle = trim((string) $this->argument('needle'));
        $limit = (int) $this->option('limit');
        $limit = $limit < 1 ? 50 : min(500, $limit);

        $attachments = DB::connection('wp')->table('postmeta as pm')
            ->join('posts as p', 'p.ID', '=', 'pm.post_id')
            ->where('p.post_type', 'attachment')
            ->where(function ($q) use ($needle) {
                $q->where(function ($inner) use ($needle) {
                    $inner->where('pm.meta_key', '_wp_attached_file')
                        ->where('pm.meta_value', 'like', '%'.$needle.'%');
                })->orWhere(function ($inner) use ($needle) {
                    $inner->where('pm.meta_key', '_wp_attachment_metadata')
                        ->where('pm.meta_value', 'like', '%'.$needle.'%');
                });
            })
            ->select(['p.ID as attachment_id', 'p.guid', 'pm.meta_key', 'pm.meta_value'])
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'attachment_id' => (int) $r->attachment_id,
                'guid' => (string) ($r->guid ?? ''),
                'hit_key' => (string) $r->meta_key,
                'hit_value' => (string) $r->meta_value,
            ])
            ->all();

        $attachmentIds = array_values(array_unique(array_map(fn ($a) => (int) $a['attachment_id'], $attachments)));
        $refs = [];
        if (! empty($attachmentIds)) {
            // References via _thumbnail_id
            $refsThumb = DB::connection('wp')->table('postmeta')
                ->select(['post_id', 'meta_key', 'meta_value'])
                ->where('meta_key', '_thumbnail_id')
                ->whereIn('meta_value', array_map('strval', $attachmentIds))
                ->limit(2000)
                ->get()
                ->map(fn ($r) => ['post_id' => (int) $r->post_id, 'meta_key' => (string) $r->meta_key, 'meta_value' => (string) $r->meta_value])
                ->all();

            // References via galleries (CSV)
            $refsGallery = DB::connection('wp')->table('postmeta')
                ->select(['post_id', 'meta_key', 'meta_value'])
                ->whereIn('meta_key', ['st_gallery', 'gallery', '_gallery'])
                ->where(function ($q) use ($attachmentIds) {
                    foreach ($attachmentIds as $id) {
                        $q->orWhere('meta_value', 'like', '%'.(string) $id.'%');
                    }
                })
                ->limit(2000)
                ->get()
                ->map(fn ($r) => ['post_id' => (int) $r->post_id, 'meta_key' => (string) $r->meta_key, 'meta_value' => (string) $r->meta_value])
                ->all();

            $refs = array_values(array_merge($refsThumb, $refsGallery));
        }

        $out = [
            'needle' => $needle,
            'attachments' => $attachments,
            'referenced_by' => $refs,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            return 0;
        }

        $this->info('Attachments hits: '.count($attachments));
        foreach ($attachments as $a) {
            $this->line(sprintf('attachment_id=%d guid=%s hit=%s', $a['attachment_id'], $a['guid'], $a['hit_key']));
        }
        $this->newLine();
        $this->info('References: '.count($refs));
        foreach ($refs as $r) {
            $this->line(sprintf('post_id=%d meta_key=%s meta_value=%s', $r['post_id'], $r['meta_key'], $r['meta_value']));
        }

        return 0;
    }
}

