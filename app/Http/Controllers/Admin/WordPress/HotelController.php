<?php

namespace App\Http\Controllers\Admin\WordPress;

use App\Http\Controllers\Controller;
use App\Http\Requests\HotelStoreRequest;
use App\Http\Requests\HotelUpdateRequest;
use App\Models\StHotel;
use App\Models\WpPost;
use App\Models\WpPostmeta;
use App\Services\WordPressMediaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HotelController extends Controller
{
    public function __construct(
        protected WordPressMediaService $media
    ) {}

    public function index(): View
    {
        $hotels = WpPost::typeHotel()
            ->publishedOrDraft()
            ->with('stHotel')
            ->orderBy('post_modified', 'desc')
            ->paginate(15);

        return view('admin.wordpress.hotels.index', [
            'hotels' => $hotels,
            'media' => $this->media,
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
            $post->post_excerpt = '';
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

            if ($request->hasFile('featured_image')) {
                $attachmentId = $this->media->uploadAndCreateAttachment($request->file('featured_image'));
                $this->media->setHotelThumbnail($post->ID, $attachmentId);
            }

            $galleryIds = [];
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $file) {
                    if ($file->isValid()) {
                        $galleryIds[] = $this->media->uploadAndCreateAttachment($file);
                    }
                }
                if (! empty($galleryIds)) {
                    $this->media->setHotelGallery($post->ID, $galleryIds);
                    $this->media->setGalleryMeta($post->ID, $galleryIds);
                }
            }

            DB::commit();
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

            if ($request->hasFile('featured_image')) {
                $attachmentId = $this->media->uploadAndCreateAttachment($request->file('featured_image'));
                $this->media->setHotelThumbnail($hotel->ID, $attachmentId);
            }

            $galleryKeepIds = array_values(array_filter(array_map('intval', $validated['gallery_keep_ids'] ?? [])));
            $newGalleryIds = [];
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $file) {
                    if ($file->isValid()) {
                        $newGalleryIds[] = $this->media->uploadAndCreateAttachment($file);
                    }
                }
            }
            $finalGalleryIds = array_merge($galleryKeepIds, $newGalleryIds);
            $this->media->setHotelGallery($hotel->ID, $finalGalleryIds);
            $this->media->setGalleryMeta($hotel->ID, $finalGalleryIds);

            $this->saveHotelDetailMeta($hotel->ID, $request, $validated);

            DB::commit();
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
            $attachmentId = $this->media->uploadAndCreateAttachment($request->file('hotel_logo'));
            $logoUrl = $this->media->getAttachmentUrl($attachmentId);
            WpPostmeta::updateOrInsertMeta($postId, '_logo_id', (string) $attachmentId);
            WpPostmeta::updateOrInsertMeta($postId, '_logo', $logoUrl ?? '');
        }

        $layout = $validated['_single_layout'] ?? null;
        WpPostmeta::updateOrInsertMeta($postId, '_single_layout', $layout && in_array($layout, ['layout-1', 'layout-2'], true) ? $layout : null);
    }
}
