<?php

namespace App\Services;

use App\Models\CatalogActivity;
use App\Models\CatalogTransfer;
use App\Models\Voyage;
use App\Models\Wp\StActivity as WpStActivity;
use App\Models\Wp\StCar as WpStCar;
use App\Models\Wp\WpPost;
use Illuminate\Support\Facades\DB;

/**
 * Lecture directe WordPress (connexion wp) pour diagnostic interne — sans API REST publique.
 */
class WordPressCatalogInspectService
{
    public function __construct(
        protected WordPressMediaService $media,
    ) {}

    public function wpConnectionLabel(): array
    {
        $c = config('database.connections.wp');

        return [
            'connection' => 'wp',
            'database' => $c['database'] ?? null,
            'prefix' => $c['prefix'] ?? '',
            'host' => $c['host'] ?? null,
        ];
    }

    public function inspectActivity(int $wpPostId): array
    {
        $post = WpPost::query()->where('post_type', 'st_activity')->findOrFail($wpPostId);
        $detail = WpStActivity::query()->find($wpPostId);
        $catalog = CatalogActivity::query()->where('wp_post_id', $wpPostId)->first();

        return [
            'module' => 'activity',
            'wp' => $this->wpConnectionLabel(),
            'laravel_catalog' => $catalog?->only(['id', 'title', 'slug', 'status', 'wp_post_id', 'updated_at']),
            'wp_posts' => $this->postCoreSnapshot($post),
            'st_activity' => $detail?->toArray(),
            'postmeta_subset' => $this->metaSubset($post, [
                '_thumbnail_id', '_gallery', 'aj_activity_category', 'aj_activity_place_text',
                'price', 'min_price', 'address', 'duration',
            ]),
        ];
    }

    public function inspectTransfer(int $wpPostId): array
    {
        $post = WpPost::query()->where('post_type', 'st_cars')->findOrFail($wpPostId);
        $detail = WpStCar::query()->find($wpPostId);
        $catalog = CatalogTransfer::query()->where('wp_post_id', $wpPostId)->first();

        return [
            'module' => 'transfer',
            'wp' => $this->wpConnectionLabel(),
            'laravel_catalog' => $catalog?->only(['id', 'title', 'slug', 'status', 'wp_post_id', 'updated_at']),
            'wp_posts' => $this->postCoreSnapshot($post),
            'st_cars' => $detail?->toArray(),
            'postmeta_subset' => $this->metaSubset($post, [
                '_thumbnail_id', 'aj_transfer_from', 'aj_transfer_to', 'aj_transfer_type',
                'aj_transfer_vehicle_type', 'aj_transfer_capacity', 'price', 'min_price', 'address',
            ]),
        ];
    }

    public function inspectHotel(int $wpPostId): array
    {
        $post = WpPost::query()->where('post_type', 'st_hotel')->findOrFail($wpPostId);
        $detail = DB::connection('wp')->table('st_hotel')->where('post_id', $wpPostId)->first();
        $metas = $post->getAllMetas();
        $thumbId = isset($metas['_thumbnail_id']) && is_numeric($metas['_thumbnail_id']) ? (int) $metas['_thumbnail_id'] : 0;

        return [
            'module' => 'hotel',
            'wp' => $this->wpConnectionLabel(),
            'wp_posts' => $this->postCoreSnapshot($post),
            'st_hotel' => $detail ? (array) $detail : null,
            'postmeta_subset' => $this->metaSubset($post, [
                '_thumbnail_id', '_gallery', 'price', 'min_price', 'address', 'hotel_star',
            ]),
            'media' => [
                'thumbnail' => $thumbId > 0 ? $this->attachmentSnapshot($thumbId) : null,
                'meta_hits_2026_04' => DB::connection('wp')->table('postmeta')
                    ->select(['meta_key', 'meta_value'])
                    ->where('post_id', $wpPostId)
                    ->where('meta_value', 'like', '%2026/04%')
                    ->limit(30)
                    ->get()
                    ->map(fn ($r) => ['meta_key' => $r->meta_key, 'meta_value' => $r->meta_value])
                    ->all(),
            ],
        ];
    }

    public function inspectVoyage(int $wpPostId): array
    {
        $post = WpPost::query()->where('post_type', 'st_tours')->findOrFail($wpPostId);
        $detail = DB::connection('wp')->table('st_tours')->where('post_id', $wpPostId)->first();
        $voyage = Voyage::query()->where('wp_post_id', $wpPostId)->first();

        return [
            'module' => 'voyage',
            'wp' => $this->wpConnectionLabel(),
            'laravel_voyage' => $voyage?->only(['id', 'name', 'slug', 'status', 'wp_post_id', 'updated_at']),
            'wp_posts' => $this->postCoreSnapshot($post),
            'st_tours' => $detail ? (array) $detail : null,
            'postmeta_subset' => $this->metaSubset($post, [
                '_thumbnail_id', '_gallery', '_aj_laravel_voyage_id', 'adult_price', 'min_price', 'address',
            ]),
        ];
    }

    protected function postCoreSnapshot(WpPost $post): array
    {
        return [
            'ID' => (int) $post->ID,
            'post_type' => $post->post_type,
            'post_title' => $post->post_title,
            'post_name' => $post->post_name,
            'post_status' => $post->post_status,
            'post_excerpt' => $post->post_excerpt,
            'post_content_length' => strlen((string) $post->post_content),
            'post_modified' => $this->formatWpDate($post->post_modified),
            'post_modified_gmt' => $this->formatWpDate($post->post_modified_gmt),
        ];
    }

    protected function metaSubset(WpPost $post, array $keys): array
    {
        $all = $post->getAllMetas();
        $out = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $all)) {
                $out[$k] = strlen((string) $all[$k]) > 200
                    ? substr((string) $all[$k], 0, 200).'…'
                    : $all[$k];
            }
        }

        return $out;
    }

    protected function formatWpDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }

    protected function attachmentSnapshot(int $attachmentId): array
    {
        $att = WpPost::query()
            ->where('post_type', 'attachment')
            ->find($attachmentId);

        $attached = $att?->getMeta('_wp_attached_file');
        $metaRaw = $att?->getMeta('_wp_attachment_metadata');

        $relative = is_string($attached) ? trim($attached) : null;
        $absPath = $relative ? $this->media->path($relative) : null;

        return [
            'ID' => (int) $attachmentId,
            'exists' => $att !== null,
            'guid' => $att?->guid,
            '_wp_attached_file' => $relative,
            '_wp_attachment_metadata_raw' => is_string($metaRaw) ? (strlen($metaRaw) > 500 ? substr($metaRaw, 0, 500).'…' : $metaRaw) : null,
            'public_url' => $relative ? $this->media->url($relative) : null,
            'abs_path' => $absPath,
            'file_exists' => $absPath ? is_file($absPath) : false,
        ];
    }
}
