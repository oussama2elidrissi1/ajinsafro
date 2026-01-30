<?php

namespace App\Http\Controllers\Admin\WordPress;

use App\Http\Controllers\Controller;
use App\Http\Requests\HotelStoreRequest;
use App\Http\Requests\HotelUpdateRequest;
use App\Models\StHotel;
use App\Models\WpPost;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HotelController extends Controller
{
    public function index(): View
    {
        $hotels = WpPost::typeHotel()
            ->publishedOrDraft()
            ->with('stHotel')
            ->orderBy('post_modified', 'desc')
            ->paginate(15);

        return view('admin.wordpress.hotels.index', compact('hotels'));
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

        return view('admin.wordpress.hotels.edit', [
            'hotel' => $hotel,
            'stHotel' => $stHotel,
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
}
