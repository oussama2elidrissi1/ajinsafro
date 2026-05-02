<?php

namespace App\Http\Controllers\Admin\WordPress;

use App\Http\Controllers\Controller;
use App\Http\Requests\HotelStoreRequest;
use App\Http\Requests\HotelUpdateRequest;
use App\Models\StHotel;
use App\Models\WpPost;
use App\Models\WpPostmeta;
use App\Services\WordPressMediaService;
use App\Services\WordPressPublicCacheInvalidator;
use App\Services\WordPressTravelerMetaMirror;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HotelController extends Controller
{
    public function __construct(
        protected WordPressMediaService $media,
        protected WordPressTravelerMetaMirror $travelerMeta,
        protected WordPressPublicCacheInvalidator $wpCache,
    ) {}

    public function index(Request $request): View
    {
        $postsTable = (new WpPost())->getTable();
        $hotelsTable = (new StHotel())->getTable();

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $featured = trim((string) $request->query('featured', ''));
        $star = trim((string) $request->query('hotel_star', ''));
        $destination = trim((string) $request->query('destination', ''));

        $hotels = WpPost::query()
            ->leftJoin($hotelsTable, $postsTable.'.ID', '=', $hotelsTable.'.post_id')
            ->select($postsTable.'.*')
            ->typeHotel()
            ->publishedOrDraft()
            ->with('stHotel')
            ->when($search !== '', function ($query) use ($search, $postsTable, $hotelsTable) {
                $query->where(function ($inner) use ($search, $postsTable, $hotelsTable) {
                    $inner->where($postsTable.'.post_title', 'like', '%'.$search.'%')
                        ->orWhere($postsTable.'.post_name', 'like', '%'.$search.'%')
                        ->orWhere($postsTable.'.post_excerpt', 'like', '%'.$search.'%')
                        ->orWhere($hotelsTable.'.address', 'like', '%'.$search.'%');
                });
            })
            ->when(in_array($status, ['publish', 'draft'], true), fn ($query) => $query->where($postsTable.'.post_status', $status))
            ->when($featured === '1', fn ($query) => $query->where($hotelsTable.'.is_featured', 'on'))
            ->when($star !== '' && ctype_digit($star), fn ($query) => $query->where($hotelsTable.'.hotel_star', $star))
            ->when($destination !== '', fn ($query) use ($destination, $hotelsTable) => $query->where($hotelsTable.'.address', 'like', '%'.$destination.'%'))
            ->orderByDesc($postsTable.'.post_modified')
            ->paginate(15);

        $hotels->appends($request->query());

        $wpSiteUrl = rtrim((string) config('wordpress.site_url', config('wordpress.public_site_url', '')), '/');

        return view('admin.wordpress.hotels.index', [
            'hotels' => $hotels,
            'media' => $this->media,
            'filters' => compact('search', 'status', 'featured', 'star', 'destination'),
            'wpSiteUrl' => $wpSiteUrl,
        ]);
    }

    public function create(): View
    {
        return view('admin.wordpress.hotels.create');
    }

    public function store(HotelStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $postName = ! empty($validated['post_name'])
            ? WpPost::uniqueSlug($validated['post_name'], null)
            : WpPost::uniqueSlug($validated['post_title'], null);

        $now = Carbon::now();
        $nowGmt = $now->utc();

        DB::beginTransaction();
        try {
            $post = new WpPost();
            $post->post_author = 1;
            $post->post_date = $now->format('Y-m-d H:i:s');
            $post->post_date_gmt = $nowGmt->format('Y-m-d H:i:s');
            $post->post_content = $validated['post_content'] ?? '';
            $post->post_title = $validated['post_title'];
            $post->post_excerpt = $validated['post_excerpt'] ?? '';
            $post->post_status = $validated['post_status'];
            $post->comment_status = 'open';
            $post->ping_status = 'open';
            $post->post_password = '';
            $post->post_name = $postName;
            $post->to_ping = '';
            $post->pinged = '';
            $post->post_modified = $now->format('Y-m-d H:i:s');
            $post->post_modified_gmt = $nowGmt->format('Y-m-d H:i:s');
            $post->post_content_filtered = '';
            $post->post_parent = 0;
            $post->guid = '';
            $post->menu_order = 0;
            $post->post_type = 'st_hotel';
            $post->post_mime_type = '';
            $post->comment_count = 0;
            $post->save();

            $stHotel = new StHotel();
            $stHotel->post_id = $post->ID;
            $stHotel->address = $validated['address'] ?? null;
            $stHotel->hotel_star = isset($validated['hotel_star']) ? (string) $validated['hotel_star'] : null;
            $stHotel->min_price = isset($validated['min_price']) ? (string) $validated['min_price'] : null;
            $stHotel->map_lat = $validated['map_lat'] ?? null;
            $stHotel->map_lng = $validated['map_lng'] ?? null;
            $stHotel->is_featured = ($validated['is_featured'] ?? 'off') === 'on' ? 'on' : 'off';
            $stHotel->save();

            $this->saveHotelMeta($post->ID, $validated);
            WpPostmeta::updateOrInsertMeta($post->ID, '_ajinsafro_catalog_source', 'laravel-hotel-crud');

            if ($request->hasFile('featured_image')) {
                $attachmentId = $this->media->tryUploadAndCreateAttachment($request->file('featured_image'), (int) $post->ID);
                if ($attachmentId) {
                    $this->media->setPostThumbnailIfValidWithPolicy($post, $attachmentId, [
                        'source' => 'HotelController::store featured_image',
                    ], true);
                }
            } elseif ($request->boolean('remove_featured_image')) {
                $post->deleteMeta('_thumbnail_id');
            }

            $galleryIds = [];
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $file) {
                    if ($file->isValid()) {
                        $newId = $this->media->tryUploadAndCreateAttachment($file, (int) $post->ID);
                        if ($newId) {
                            $galleryIds[] = $newId;
                        }
                    }
                }
                if (! empty($galleryIds)) {
                    $this->media->setPostGalleryMetasFiltered($post, $galleryIds, ['st_gallery', 'gallery', '_gallery'], [
                        'source' => 'HotelController::store gallery',
                    ]);
                }
            }

            $this->travelerMeta->mirrorHotelMetas((int) $post->ID, $stHotel);

            DB::commit();
            $this->wpCache->invalidatePostIds([(int) $post->ID]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()
            ->route('admin.wordpress.hotels.index')
            ->with('success', 'Hôtel créé avec succès.');
    }

    public function edit(WpPost $hotel): View
    {
        $hotel->load('stHotel');
        if ($hotel->post_type !== 'st_hotel') {
            abort(404);
        }
        if (! in_array($hotel->post_status, ['publish', 'draft'], true)) {
            abort(404);
        }
        $stHotel = $hotel->stHotel ?? new StHotel(['post_id' => $hotel->ID]);
        // URLs construites via _wp_attached_file uniquement (jamais guid), vérifiées si fichier existe
        $featuredUrl = $this->media->getFeaturedImageUrlVerified($hotel->ID);
        $galleryUrls = $this->media->getGalleryUrlsVerified($hotel->ID);

        $meta = [
            'hotel_amenities' => WpPostmeta::getMeta($hotel->ID, 'hotel_amenities'),
            'hotel_policies' => WpPostmeta::getMeta($hotel->ID, 'hotel_policies'),
            'hotel_phone' => WpPostmeta::getMeta($hotel->ID, 'hotel_phone'),
            'hotel_email' => WpPostmeta::getMeta($hotel->ID, 'hotel_email'),
        ];

        $hotelDetailMeta = [
            '_is_featured' => WpPostmeta::getMeta($hotel->ID, '_is_featured'),
            '_external_booking' => WpPostmeta::getMeta($hotel->ID, '_external_booking'),
            '_external_booking_link' => WpPostmeta::getMeta($hotel->ID, '_external_booking_link'),
            '_logo' => WpPostmeta::getMeta($hotel->ID, '_logo'),
            '_logo_id' => WpPostmeta::getMeta($hotel->ID, '_logo_id'),
            '_single_layout' => WpPostmeta::getMeta($hotel->ID, '_single_layout'),
        ];
        // Logo: URL via _logo_id → _wp_attached_file, uniquement si fichier existe
        $logoUrl = null;
        if (! empty($hotelDetailMeta['_logo_id']) && is_numeric($hotelDetailMeta['_logo_id'])) {
            $logoUrl = $this->media->getAttachmentUrlVerified((int) $hotelDetailMeta['_logo_id']);
        }

        return view('admin.wordpress.hotels.edit', [
            'hotel' => $hotel,
            'stHotel' => $stHotel,
            'featuredUrl' => $featuredUrl,
            'galleryUrls' => $galleryUrls,
            'meta' => $meta,
            'hotelDetailMeta' => $hotelDetailMeta,
            'logoUrl' => $logoUrl,
            'media' => $this->media,
        ]);
    }

    public function update(HotelUpdateRequest $request, WpPost $hotel): RedirectResponse
    {
        if ($hotel->post_type !== 'st_hotel') {
            abort(404);
        }
        if (! in_array($hotel->post_status, ['publish', 'draft'], true)) {
            abort(404);
        }

        $validated = $request->validated();
        $postName = ! empty($validated['post_name'])
            ? WpPost::uniqueSlug($validated['post_name'], $hotel->ID)
            : WpPost::uniqueSlug($validated['post_title'], $hotel->ID);

        $now = Carbon::now();
        $nowGmt = $now->utc();

        DB::beginTransaction();
        try {
            $hotel->post_content = $validated['post_content'] ?? '';
            $hotel->post_title = $validated['post_title'];
            $hotel->post_excerpt = $validated['post_excerpt'] ?? '';
            $hotel->post_status = $validated['post_status'];
            $hotel->post_name = $postName;
            $hotel->post_modified = $now->format('Y-m-d H:i:s');
            $hotel->post_modified_gmt = $nowGmt->format('Y-m-d H:i:s');
            $hotel->save();

            $stHotel = StHotel::where('post_id', $hotel->ID)->first();
            if (! $stHotel) {
                $stHotel = new StHotel();
                $stHotel->post_id = $hotel->ID;
            }
            $stHotel->address = $validated['address'] ?? null;
            $stHotel->hotel_star = isset($validated['hotel_star']) ? (string) $validated['hotel_star'] : null;
            $stHotel->min_price = isset($validated['min_price']) ? (string) $validated['min_price'] : null;
            $stHotel->map_lat = $validated['map_lat'] ?? null;
            $stHotel->map_lng = $validated['map_lng'] ?? null;
            $stHotel->is_featured = ($validated['is_featured'] ?? 'off') === 'on' ? 'on' : 'off';
            $stHotel->save();

            $this->saveHotelMeta($hotel->ID, $validated);
            WpPostmeta::updateOrInsertMeta($hotel->ID, '_ajinsafro_catalog_source', 'laravel-hotel-crud');

            if ($request->hasFile('featured_image')) {
                $attachmentId = $this->media->tryUploadAndCreateAttachment($request->file('featured_image'), (int) $hotel->ID);
                if ($attachmentId) {
                    $this->media->setPostThumbnailIfValidWithPolicy($hotel, $attachmentId, [
                        'source' => 'HotelController::update featured_image',
                    ], true);
                }
            } elseif ($request->boolean('remove_featured_image')) {
                $hotel->deleteMeta('_thumbnail_id');
            }

            $galleryKeepIds = array_values(array_filter(array_map('intval', $validated['gallery_keep_ids'] ?? [])));
            $newGalleryIds = [];
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $file) {
                    if ($file->isValid()) {
                        $newId = $this->media->tryUploadAndCreateAttachment($file, (int) $hotel->ID);
                        if ($newId) {
                            $newGalleryIds[] = $newId;
                        }
                    }
                }
            }
            $finalGalleryIds = array_merge($galleryKeepIds, $newGalleryIds);
            $this->media->setPostGalleryMetasFiltered($hotel, $finalGalleryIds, ['st_gallery', 'gallery', '_gallery'], [
                'source' => 'HotelController::update gallery',
            ]);

            $this->saveHotelDetailMeta($hotel->ID, $request, $validated);

            $this->travelerMeta->mirrorHotelMetas((int) $hotel->ID, $stHotel);

            DB::commit();
            $this->wpCache->invalidatePostIds([(int) $hotel->ID]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()
            ->route('admin.wordpress.hotels.index')
            ->with('success', 'Hôtel mis à jour avec succès.');
    }

    public function destroy(WpPost $hotel): RedirectResponse
    {
        if ($hotel->post_type !== 'st_hotel') {
            abort(404);
        }

        $hotel->post_status = 'trash';
        $hotel->save();

        return redirect()
            ->route('admin.wordpress.hotels.index')
            ->with('success', 'Hôtel déplacé dans la corbeille.');
    }

    protected function saveHotelMeta(int $postId, array $validated): void
    {
        $metaKeys = [
            'hotel_amenities' => 'hotel_amenities',
            'hotel_policies' => 'hotel_policies',
            'hotel_phone' => 'hotel_phone',
            'hotel_email' => 'hotel_email',
        ];
        foreach ($metaKeys as $key => $metaKey) {
            $value = $validated[$key] ?? null;
            if (is_string($value)) {
                $value = trim($value) === '' ? null : $value;
            }
            WpPostmeta::updateOrInsertMeta($postId, $metaKey, $value);
        }
    }

    protected function saveHotelDetailMeta(int $postId, \Illuminate\Http\Request $request, array $validated): void
    {
        $isFeatured = ($validated['_is_featured'] ?? '') === '1' ? '1' : '0';
        WpPostmeta::updateOrInsertMeta($postId, '_is_featured', $isFeatured);

        $externalBooking = ($validated['_external_booking'] ?? '') === '1' ? '1' : '0';
        WpPostmeta::updateOrInsertMeta($postId, '_external_booking', $externalBooking);

        if ($externalBooking === '1') {
            $link = trim($validated['external_booking_link'] ?? '');
            WpPostmeta::updateOrInsertMeta($postId, '_external_booking_link', $link === '' ? null : $link);
        }

        if ($request->boolean('hotel_logo_remove')) {
            WpPostmeta::deleteMeta($postId, '_logo_id');
            WpPostmeta::deleteMeta($postId, '_logo');
        } elseif ($request->hasFile('hotel_logo') && $request->file('hotel_logo')->isValid()) {
            $attachmentId = $this->media->tryUploadAndCreateAttachment($request->file('hotel_logo'), (int) $postId);
            if (! $attachmentId) {
                return;
            }
            if (! $this->media->isAttachmentStrictlyValidForWrite((int) $attachmentId)) {
                // Ne pas écraser le logo existant si l'upload/attachment n'est pas strictement valide.
                \Log::warning('HotelController::saveHotelDetailMeta rejected logo attachment', [
                    'post_id' => $postId,
                    'attachment_id' => $attachmentId,
                    'status' => $this->media->getAttachmentDisplayStatus((int) $attachmentId),
                ]);

                return;
            }
            $logoUrl = $this->media->getAttachmentUrl($attachmentId);
            WpPostmeta::updateOrInsertMeta($postId, '_logo_id', (string) $attachmentId);
            WpPostmeta::updateOrInsertMeta($postId, '_logo', $logoUrl ?? '');
        }

        $layout = $validated['_single_layout'] ?? null;
        WpPostmeta::updateOrInsertMeta($postId, '_single_layout', $layout && in_array($layout, ['layout-1', 'layout-2'], true) ? $layout : null);
    }
}
