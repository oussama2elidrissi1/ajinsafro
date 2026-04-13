<?php

namespace App\Services;

use App\Models\CatalogActivity;
use App\Models\CatalogTransfer;
use App\Models\Wp\StActivity as WpStActivity;
use App\Models\Wp\StCar as WpStCar;
use App\Models\Wp\WpPost;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WordPressCatalogSyncService
{
    public function __construct(
        protected WordPressMediaService $media,
        protected WordPressTravelerMetaMirror $travelerMeta,
        protected WordPressPublicCacheInvalidator $wpCache,
    ) {}

    public function syncActivityRecordFromWpPostId(int $wpPostId): CatalogActivity
    {
        $post = $this->findWpPost($wpPostId, 'st_activity');
        $record = CatalogActivity::query()->firstOrNew(['wp_post_id' => $wpPostId]);
        $detail = WpStActivity::query()->find($wpPostId);
        $metas = $post->getAllMetas();

        $record->fill([
            'title' => (string) $post->post_title,
            'slug' => (string) $post->post_name,
            'excerpt' => (string) ($post->post_excerpt ?? ''),
            'content' => (string) ($post->post_content ?? ''),
            'status' => (string) ($post->post_status ?? 'draft'),
            'address' => $detail?->address,
            'type_activity' => $detail?->type_activity,
            'adult_price' => $this->nullableDecimal($detail?->adult_price),
            'child_price' => $this->nullableDecimal($detail?->child_price),
            'min_price' => $this->nullableDecimal($detail?->min_price),
            'duration' => $detail?->duration,
            'max_people' => $this->nullableInt($detail?->max_people),
            'rate_review' => $this->nullableDecimal($detail?->rate_review),
            'is_featured' => ($detail?->is_featured ?? 'off') === 'on',
            'category' => $this->nullableString($metas['aj_activity_category'] ?? null),
            'place_text' => $this->nullableString($metas['aj_activity_place_text'] ?? null),
            'min_age' => $this->nullableInt($metas['aj_activity_min_age'] ?? null),
            'max_age' => $this->nullableInt($metas['aj_activity_max_age'] ?? null),
            'featured_image_wp_id' => $this->nullableInt($metas['_thumbnail_id'] ?? null),
            'gallery_image_wp_ids' => $this->parseIdList($metas['_gallery'] ?? $metas['gallery'] ?? null),
            'wp_synced_at' => now(),
            'wp_sync_hash' => $this->computeHash([
                'post' => $this->postSnapshot($post),
                'detail' => $detail?->only(['address', 'adult_price', 'child_price', 'min_price', 'type_activity', 'duration', 'max_people', 'rate_review', 'is_featured']) ?? [],
                'metas' => [
                    'aj_activity_category' => $metas['aj_activity_category'] ?? null,
                    'aj_activity_place_text' => $metas['aj_activity_place_text'] ?? null,
                    'aj_activity_min_age' => $metas['aj_activity_min_age'] ?? null,
                    'aj_activity_max_age' => $metas['aj_activity_max_age'] ?? null,
                    '_thumbnail_id' => $metas['_thumbnail_id'] ?? null,
                    '_gallery' => $metas['_gallery'] ?? null,
                ],
            ]),
        ]);
        $record->save();

        $post->setMeta('_aj_laravel_activity_id', (string) $record->id);

        return $record->fresh();
    }

    public function syncTransferRecordFromWpPostId(int $wpPostId): CatalogTransfer
    {
        $post = $this->findWpPost($wpPostId, 'st_cars');
        $record = CatalogTransfer::query()->firstOrNew(['wp_post_id' => $wpPostId]);
        $detail = WpStCar::query()->find($wpPostId);
        $metas = $post->getAllMetas();

        $record->fill([
            'title' => (string) $post->post_title,
            'slug' => (string) $post->post_name,
            'excerpt' => (string) ($post->post_excerpt ?? ''),
            'content' => (string) ($post->post_content ?? ''),
            'status' => (string) ($post->post_status ?? 'draft'),
            'cars_address' => $detail?->cars_address,
            'cars_price' => $this->nullableDecimal($detail?->cars_price),
            'min_price' => $this->nullableDecimal($detail?->min_price),
            'max_price' => $this->nullableDecimal($detail?->max_price),
            'number_car' => $this->nullableInt($detail?->number_car),
            'is_featured' => ($detail?->is_featured ?? 'off') === 'on',
            'transfer_from' => $this->nullableString($metas['aj_transfer_from'] ?? null),
            'transfer_to' => $this->nullableString($metas['aj_transfer_to'] ?? null),
            'transfer_type' => $this->nullableString($metas['aj_transfer_type'] ?? null),
            'transfer_capacity' => $this->nullableInt($metas['aj_transfer_capacity'] ?? null),
            'transfer_vehicle_type' => $this->nullableString($metas['aj_transfer_vehicle_type'] ?? null),
            'featured_image_wp_id' => $this->nullableInt($metas['_thumbnail_id'] ?? null),
            'wp_synced_at' => now(),
            'wp_sync_hash' => $this->computeHash([
                'post' => $this->postSnapshot($post),
                'detail' => $detail?->only(['cars_address', 'cars_price', 'min_price', 'max_price', 'number_car', 'is_featured']) ?? [],
                'metas' => [
                    'aj_transfer_from' => $metas['aj_transfer_from'] ?? null,
                    'aj_transfer_to' => $metas['aj_transfer_to'] ?? null,
                    'aj_transfer_type' => $metas['aj_transfer_type'] ?? null,
                    'aj_transfer_capacity' => $metas['aj_transfer_capacity'] ?? null,
                    'aj_transfer_vehicle_type' => $metas['aj_transfer_vehicle_type'] ?? null,
                    '_thumbnail_id' => $metas['_thumbnail_id'] ?? null,
                ],
            ]),
        ]);
        $record->save();

        $post->setMeta('_aj_laravel_transfer_id', (string) $record->id);

        return $record->fresh();
    }

    public function saveActivityFromRequest(array $validated, Request $request, ?CatalogActivity $record = null): CatalogActivity
    {
        $record ??= new CatalogActivity();

        $record->fill([
            'title' => $validated['post_title'],
            'slug' => $validated['post_name'] ?? '',
            'excerpt' => $validated['post_excerpt'] ?? '',
            'content' => $validated['post_content'] ?? '',
            'status' => $validated['post_status'],
            'address' => $validated['address'] ?? null,
            'type_activity' => $validated['type_activity'] ?? null,
            'adult_price' => $validated['adult_price'] ?? null,
            'child_price' => $validated['child_price'] ?? null,
            'min_price' => $validated['min_price'] ?? ($validated['adult_price'] ?? null),
            'duration' => $validated['duration'] ?? null,
            'max_people' => $validated['max_people'] ?? null,
            'rate_review' => $validated['rate_review'] ?? null,
            'is_featured' => ($validated['is_featured'] ?? 'off') === 'on',
            'category' => $validated['aj_activity_category'] ?? null,
            'place_text' => $validated['aj_activity_place_text'] ?? null,
            'min_age' => $validated['aj_activity_min_age'] ?? null,
            'max_age' => $validated['aj_activity_max_age'] ?? null,
        ]);
        $record->save();

        $this->pushActivityToWordPress($record, $request);

        return $record->fresh();
    }

    public function saveTransferFromRequest(array $validated, Request $request, ?CatalogTransfer $record = null): CatalogTransfer
    {
        $record ??= new CatalogTransfer();

        $record->fill([
            'title' => $validated['post_title'],
            'slug' => $validated['post_name'] ?? '',
            'excerpt' => $validated['post_excerpt'] ?? '',
            'content' => $validated['post_content'] ?? '',
            'status' => $validated['post_status'],
            'cars_address' => $validated['cars_address'] ?? null,
            'cars_price' => $validated['cars_price'] ?? null,
            'min_price' => $validated['min_price'] ?? ($validated['cars_price'] ?? null),
            'max_price' => $validated['max_price'] ?? ($validated['cars_price'] ?? null),
            'number_car' => $validated['number_car'] ?? null,
            'is_featured' => ($validated['is_featured'] ?? 'off') === 'on',
            'transfer_from' => $validated['aj_transfer_from'] ?? null,
            'transfer_to' => $validated['aj_transfer_to'] ?? null,
            'transfer_type' => $validated['aj_transfer_type'] ?? null,
            'transfer_capacity' => $validated['aj_transfer_capacity'] ?? null,
            'transfer_vehicle_type' => $validated['aj_transfer_vehicle_type'] ?? null,
        ]);
        $record->save();

        $this->pushTransferToWordPress($record, $request);

        return $record->fresh();
    }

    public function trashActivityByWpPostId(int $wpPostId): void
    {
        $record = CatalogActivity::query()->where('wp_post_id', $wpPostId)->first()
            ?? $this->syncActivityRecordFromWpPostId($wpPostId);
        $record->status = 'trash';
        $record->save();
        $this->pushActivityToWordPress($record, request());
    }

    public function trashTransferByWpPostId(int $wpPostId): void
    {
        $record = CatalogTransfer::query()->where('wp_post_id', $wpPostId)->first()
            ?? $this->syncTransferRecordFromWpPostId($wpPostId);
        $record->status = 'trash';
        $record->save();
        $this->pushTransferToWordPress($record, request());
    }

    public function getWpPost(int $wpPostId, string $postType): WpPost
    {
        return $this->findWpPost($wpPostId, $postType);
    }

    protected function pushActivityToWordPress(CatalogActivity $record, Request $request): void
    {
        /** @var WpPost|null $post */
        $post = null;
        $galleryIds = [];
        $slug = '';

        // WpPost uses connection 'wp' → table {WP_DB_PREFIX}posts (see config/database.php wp).
        // DB::beginTransaction() without connection targets mysql default, not WordPress — wp_posts updates were never in a DB transaction on 'wp'.
        DB::connection('wp')->transaction(function () use ($record, $request, &$post, &$galleryIds, &$slug) {
            $post = $record->wp_post_id
                ? $this->findWpPost((int) $record->wp_post_id, 'st_activity')
                : new WpPost();

            $isNew = ! $post->exists;
            $now = Carbon::now();
            $nowGmt = Carbon::now('UTC');
            $slug = $this->ensureUniqueSlug($record->slug ?: $record->title, 'st_activity', $record->wp_post_id);

            if ($isNew) {
                $post->fill([
                    'post_author' => 1,
                    'post_date' => $now->format('Y-m-d H:i:s'),
                    'post_date_gmt' => $nowGmt->format('Y-m-d H:i:s'),
                    'comment_status' => 'open',
                    'ping_status' => 'open',
                    'post_password' => '',
                    'to_ping' => '',
                    'pinged' => '',
                    'post_content_filtered' => '',
                    'post_parent' => 0,
                    'menu_order' => 0,
                    'post_type' => 'st_activity',
                    'post_mime_type' => '',
                    'comment_count' => 0,
                    'guid' => '',
                ]);
            }

            // Explicit wp_posts columns (CatalogActivity → wp_posts) — ensures post_title and siblings persist on UPDATE.
            $post->post_title = (string) $record->title;
            $post->post_content = (string) ($record->content ?? '');
            $post->post_excerpt = (string) ($record->excerpt ?? '');
            $post->post_status = $record->status ? (string) $record->status : 'draft';
            $post->post_name = $slug;
            $post->post_modified = $now->format('Y-m-d H:i:s');
            $post->post_modified_gmt = $nowGmt->format('Y-m-d H:i:s');
            $post->save();

            if ($isNew) {
                $post->guid = $this->buildGuid((int) $post->ID, 'st_activity');
                $post->save();
            }

            $addressForTable = $this->nullableString($record->address);
            if ($addressForTable === null) {
                $addressForTable = $this->nullableString($record->place_text);
            }

            WpStActivity::query()->updateOrCreate(
                ['post_id' => $post->ID],
                [
                    'address' => $addressForTable,
                    'type_activity' => $record->type_activity,
                    'adult_price' => $record->adult_price,
                    'child_price' => $record->child_price,
                    'price' => $record->adult_price ?? $record->min_price,
                    'min_price' => $record->min_price,
                    'duration' => $record->duration,
                    'max_people' => $record->max_people,
                    'rate_review' => $record->rate_review,
                    'is_featured' => $record->is_featured ? 'on' : 'off',
                ]
            );

            $post->setMeta('aj_activity_category', $record->category ?? '');
            $post->setMeta('aj_activity_place_text', $record->place_text ?? '');
            $post->setMeta('aj_activity_min_age', $record->min_age === null ? '' : (string) $record->min_age);
            $post->setMeta('aj_activity_max_age', $record->max_age === null ? '' : (string) $record->max_age);
            $post->setMeta('_aj_laravel_activity_id', (string) $record->id);

            $galleryIds = $this->syncImagesForPost($post, $request, $record->gallery_image_wp_ids ?? [], true);

            $this->travelerMeta->mirrorActivityMetas($post, $record);
        });

        if ($post === null) {
            return;
        }

        $metas = $post->getAllMetas();
        $detail = WpStActivity::query()->find($post->ID);

        $record->forceFill([
            'wp_post_id' => (int) $post->ID,
            'slug' => $slug,
            'featured_image_wp_id' => $this->nullableInt($metas['_thumbnail_id'] ?? null),
            'gallery_image_wp_ids' => $galleryIds,
            'wp_synced_at' => now(),
            'wp_sync_hash' => $this->computeHash([
                'post' => $this->postSnapshot($post),
                'detail' => $detail?->only(['address', 'adult_price', 'child_price', 'min_price', 'type_activity', 'duration', 'max_people', 'rate_review', 'is_featured']) ?? [],
                'metas' => [
                    'aj_activity_category' => $metas['aj_activity_category'] ?? null,
                    'aj_activity_place_text' => $metas['aj_activity_place_text'] ?? null,
                    'aj_activity_min_age' => $metas['aj_activity_min_age'] ?? null,
                    'aj_activity_max_age' => $metas['aj_activity_max_age'] ?? null,
                    '_thumbnail_id' => $metas['_thumbnail_id'] ?? null,
                    '_gallery' => $metas['_gallery'] ?? null,
                ],
            ]),
        ])->save();

        $this->wpCache->invalidatePostIds([(int) $post->ID]);
    }

    protected function pushTransferToWordPress(CatalogTransfer $record, Request $request): void
    {
        /** @var WpPost|null $post */
        $post = null;
        $slug = '';

        DB::connection('wp')->transaction(function () use ($record, $request, &$post, &$slug) {
            $post = $record->wp_post_id
                ? $this->findWpPost((int) $record->wp_post_id, 'st_cars')
                : new WpPost();

            $isNew = ! $post->exists;
            $now = Carbon::now();
            $nowGmt = Carbon::now('UTC');
            $slug = $this->ensureUniqueSlug($record->slug ?: $record->title, 'st_cars', $record->wp_post_id);

            if ($isNew) {
                $post->fill([
                    'post_author' => 1,
                    'post_date' => $now->format('Y-m-d H:i:s'),
                    'post_date_gmt' => $nowGmt->format('Y-m-d H:i:s'),
                    'comment_status' => 'open',
                    'ping_status' => 'open',
                    'post_password' => '',
                    'to_ping' => '',
                    'pinged' => '',
                    'post_content_filtered' => '',
                    'post_parent' => 0,
                    'menu_order' => 0,
                    'post_type' => 'st_cars',
                    'post_mime_type' => '',
                    'comment_count' => 0,
                    'guid' => '',
                ]);
            }

            $post->post_title = (string) $record->title;
            $post->post_content = (string) ($record->content ?? '');
            $post->post_excerpt = (string) ($record->excerpt ?? '');
            $post->post_status = $record->status ? (string) $record->status : 'draft';
            $post->post_name = $slug;
            $post->post_modified = $now->format('Y-m-d H:i:s');
            $post->post_modified_gmt = $nowGmt->format('Y-m-d H:i:s');
            $post->save();

            if ($isNew) {
                $post->guid = $this->buildGuid((int) $post->ID, 'st_cars');
                $post->save();
            }

            WpStCar::query()->updateOrCreate(
                ['post_id' => $post->ID],
                [
                    'cars_address' => $record->cars_address,
                    'cars_price' => $record->cars_price,
                    'min_price' => $record->min_price,
                    'max_price' => $record->max_price,
                    'number_car' => $record->number_car,
                    'is_featured' => $record->is_featured ? 'on' : 'off',
                ]
            );

            $post->setMeta('aj_transfer_from', $record->transfer_from ?? '');
            $post->setMeta('aj_transfer_to', $record->transfer_to ?? '');
            $post->setMeta('aj_transfer_type', $record->transfer_type ?? '');
            $post->setMeta('aj_transfer_capacity', $record->transfer_capacity === null ? '' : (string) $record->transfer_capacity);
            $post->setMeta('aj_transfer_vehicle_type', $record->transfer_vehicle_type ?? '');
            $post->setMeta('_aj_laravel_transfer_id', (string) $record->id);

            $this->syncImagesForPost($post, $request, [], false);

            $this->travelerMeta->mirrorTransferMetas($post, $record);
        });

        if ($post === null) {
            return;
        }

        $metas = $post->getAllMetas();
        $detail = WpStCar::query()->find($post->ID);

        $record->forceFill([
            'wp_post_id' => (int) $post->ID,
            'slug' => $slug,
            'featured_image_wp_id' => $this->nullableInt($metas['_thumbnail_id'] ?? null),
            'wp_synced_at' => now(),
            'wp_sync_hash' => $this->computeHash([
                'post' => $this->postSnapshot($post),
                'detail' => $detail?->only(['cars_address', 'cars_price', 'min_price', 'max_price', 'number_car', 'is_featured']) ?? [],
                'metas' => [
                    'aj_transfer_from' => $metas['aj_transfer_from'] ?? null,
                    'aj_transfer_to' => $metas['aj_transfer_to'] ?? null,
                    'aj_transfer_type' => $metas['aj_transfer_type'] ?? null,
                    'aj_transfer_capacity' => $metas['aj_transfer_capacity'] ?? null,
                    'aj_transfer_vehicle_type' => $metas['aj_transfer_vehicle_type'] ?? null,
                    '_thumbnail_id' => $metas['_thumbnail_id'] ?? null,
                ],
            ]),
        ])->save();

        $this->wpCache->invalidatePostIds([(int) $post->ID]);
    }

    protected function syncImagesForPost(WpPost $post, Request $request, array $keepGalleryIds, bool $withGallery): array
    {
        $parentId = (int) $post->ID;
        if ($request->hasFile('featured_image')) {
            $attachmentId = $this->media->tryUploadAndCreateAttachment($request->file('featured_image'), $parentId);
            if ($attachmentId) {
                $this->media->setPostThumbnailIfValidWithPolicy($post, $attachmentId, [
                    'source' => 'WordPressCatalogSyncService::syncImagesForPost',
                ], false);
            }
        } elseif ($request->boolean('remove_featured_image')) {
            $post->deleteMeta('_thumbnail_id');
        }

        if (! $withGallery) {
            return [];
        }

        $hasGalleryInput = $request->has('gallery_keep_ids') || $request->hasFile('gallery_images');
        $galleryIds = array_values(array_filter(array_map('intval', $request->input('gallery_keep_ids', $keepGalleryIds))));
        if ($request->hasFile('gallery_images')) {
            foreach ((array) $request->file('gallery_images') as $file) {
                if ($file && $file->isValid()) {
                    $newId = $this->media->tryUploadAndCreateAttachment($file, $parentId);
                    if ($newId) {
                        $galleryIds[] = $newId;
                    }
                }
            }
        }

        // Protection corrections manuelles: ne réécrit la galerie que si une action explicite la modifie.
        if ($hasGalleryInput) {
            $this->media->setPostGalleryMetasFiltered($post, $galleryIds, ['_gallery', 'gallery', 'st_gallery'], [
                'source' => 'WordPressCatalogSyncService::syncImagesForPost',
            ]);
        }

        return $galleryIds;
    }

    protected function findWpPost(int $wpPostId, string $postType): WpPost
    {
        return WpPost::query()
            ->where('post_type', $postType)
            ->findOrFail($wpPostId);
    }

    protected function ensureUniqueSlug(string $source, string $postType, ?int $excludeId = null): string
    {
        $base = Str::slug($source);
        if ($base === '') {
            $base = $postType === 'st_activity' ? 'activite' : 'transfert';
        }

        $slug = $base;
        $i = 2;
        while (WpPost::query()
            ->where('post_type', $postType)
            ->where('post_name', $slug)
            ->when($excludeId, fn ($query) => $query->where('ID', '!=', $excludeId))
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    protected function buildGuid(int $postId, string $postType): string
    {
        $siteUrl = (string) DB::connection('wp')->table('options')->where('option_name', 'siteurl')->value('option_value');
        if ($siteUrl === '') {
            $siteUrl = (string) config('app.public_url', config('app.url'));
        }

        return rtrim($siteUrl, '/').'/?post_type='.$postType.'&p='.$postId;
    }

    protected function parseIdList(mixed $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $value))));
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function nullableDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    protected function postSnapshot(WpPost $post): array
    {
        return [
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'excerpt' => $post->post_excerpt,
            'content' => $post->post_content,
            'status' => $post->post_status,
        ];
    }

    protected function computeHash(array $snapshot): string
    {
        return md5(json_encode($snapshot));
    }
}
