<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TourHotel;
use App\Models\TourHotelRoom;
use App\Models\Wp\WpPost;
use App\Services\Wp\WpHeroImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TourHotelController extends Controller
{
    /**
     * Liste des hôtels (circuit) : table avec nom, rating, voyage lié, infos, actions.
     */
    public function index(Request $request): View
    {
        $wpConnectionFailed = false;
        try {
            $query = TourHotel::query()->withCount('rooms')->orderBy('hotel_name');

            if ($request->filled('search')) {
                $term = $request->input('search');
                $query->where('hotel_name', 'like', '%' . $term . '%');
            }

            $hotels = $query->paginate(20)->withQueryString();
            $tourIds = $hotels->pluck('tour_id')->unique()->filter()->values()->toArray();
            $tourTitles = [];
            if ($tourIds !== []) {
                $tourTitles = WpPost::on('wp')->whereIn('ID', $tourIds)->pluck('post_title', 'ID')->toArray();
            }
        } catch (\Throwable $e) {
            \Log::warning('TourHotelController@index: connection failed', ['error' => $e->getMessage()]);
            $wpConnectionFailed = true;
            $hotels = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1, ['path' => $request->url()]);
            $tourTitles = [];
        }

        return view('admin.circuits.tour-hotels.index', compact('hotels', 'tourTitles', 'wpConnectionFailed'));
    }

    /**
     * Données JSON d'un hôtel (tour_id) + chambres pour "copier depuis un hôtel existant" (voyage edit).
     */
    public function data(int $tourId): JsonResponse
    {
        $hotel = TourHotel::getForTour($tourId);
        if (!$hotel) {
            return response()->json(['hotel' => null, 'rooms' => []]);
        }
        $hotel->load('rooms');
        $rooms = $hotel->rooms->map(fn ($r) => [
            'id' => null,
            'room_type' => $r->room_type,
            'room_label' => $r->room_label,
            'room_code' => $r->room_code,
            'room_count' => $r->room_count,
            'capacity_adults' => $r->capacity_adults,
            'capacity_children' => $r->capacity_children,
            'capacity_total' => $r->capacity_total,
            'supplement' => $r->supplement,
            'description' => $r->description,
            'is_active' => $r->is_active,
            'is_default' => $r->is_default,
            'notes' => $r->notes,
        ])->values()->toArray();

        return response()->json([
            'hotel' => [
                'id' => $hotel->id,
                'tour_id' => $hotel->tour_id,
                'hotel_name' => $hotel->hotel_name,
                'stars' => $hotel->stars,
                'address' => $hotel->address,
                'room_type' => $hotel->room_type,
                'meal_plan' => $hotel->meal_plan,
                'notes' => $hotel->notes,
                'image_id' => $hotel->image_id,
                'image_url' => ! empty($hotel->image_id)
                    ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int) $hotel->image_id)
                    : '',
                'check_in_day' => $hotel->check_in_day ?? 1,
                'check_out_day' => $hotel->check_out_day ?? 1,
                'is_optional' => (bool) $hotel->is_optional,
            ],
            'rooms' => $rooms,
        ]);
    }

    /**
     * DonnÃ©es JSON d'un hÃ´tel WordPress (post_type=st_hotel) pour prÃ©remplir un nouveau sÃ©jour.
     */
    public function wpHotelData(int $hotelId): JsonResponse
    {
        $post = WpPost::query()
            ->where('post_type', 'st_hotel')
            ->find($hotelId);

        if (! $post) {
            return response()->json(['hotel' => null, 'rooms' => []]);
        }

        $metas = $post->getAllMetas();
        $detail = DB::connection('wp')->table('st_hotel')->where('post_id', $hotelId)->first();

        $thumbnailId = $this->asInt($metas['_thumbnail_id'] ?? null);
        $thumbnailUrl = $thumbnailId > 0 ? WpHeroImageService::getAttachmentUrl($thumbnailId) : null;

        $address = $this->firstNonEmpty([
            $detail?->address ?? null,
            $metas['address'] ?? null,
            $metas['location'] ?? null,
        ]);
        $city = $this->resolveHotelCity($detail, $metas);

        $mealPlan = $this->firstNonEmpty([
            $metas['meal_plan'] ?? null,
            $metas['hotel_meal_plan'] ?? null,
            $metas['pension'] ?? null,
            $metas['hotel_pension'] ?? null,
        ]);

        $notes = $this->firstNonEmpty([
            $metas['internal_notes'] ?? null,
            $metas['hotel_notes'] ?? null,
            $metas['notes'] ?? null,
            $post->post_excerpt ?? null,
        ]);

        $stars = $this->normalizeStars(
            $detail?->hotel_star ?? ($metas['hotel_star'] ?? null)
        );

        $galleryIds = $this->parseCsvIds(
            $metas['_gallery'] ?? ($metas['st_gallery'] ?? ($metas['gallery'] ?? null))
        );
        $gallery = collect($galleryIds)
            ->map(fn (int $id) => ['id' => $id, 'url' => WpHeroImageService::getAttachmentUrl($id)])
            ->filter(fn (array $row) => ! empty($row['url']))
            ->values()
            ->all();
        $wpSiteUrl = rtrim((string) config('wordpress.site_url', ''), '/');
        $wpUrl = $wpSiteUrl !== '' ? ($wpSiteUrl . '/?post_type=st_hotel&p=' . (int) $post->ID) : null;

        return response()->json([
            'hotel' => [
                'id' => (int) $post->ID,
                'wp_post_id' => (int) $post->ID,
                'hotel_id' => (int) $post->ID,
                'source_hotel_id' => (int) $post->ID,
                'hotel_name' => trim((string) ($post->post_title ?? '')),
                'stars' => $stars,
                'address' => $address,
                'city' => $city,
                'location' => $city ?: $address,
                'meal_plan' => $mealPlan,
                'notes' => $notes,
                'description' => trim(strip_tags((string) ($post->post_content ?? ''))),
                'post_excerpt' => trim((string) ($post->post_excerpt ?? '')),
                'image_id' => $thumbnailId > 0 ? $thumbnailId : null,
                'image_url' => $thumbnailUrl,
                'gallery' => $gallery,
                'wp_url' => $wpUrl,
            ],
            'rooms' => [],
        ]);
    }

    /**
     * Détail de l'hôtel du circuit (lecture seule + tableau des chambres).
     */
    public function show(int $tourId): View
    {
        $tour = WpPost::tours()->where('ID', $tourId)->firstOrFail();
        $hotel = TourHotel::getForTour($tourId);
        if ($hotel) {
            $hotel->load('rooms');
        }

        return view('admin.circuits.tour-hotels.show', compact('tour', 'hotel'));
    }

    /**
     * Formulaire d'édition de l'hôtel du tour (tour_id = wp post ID).
     */
    public function edit(int $tourId): View
    {
        $tour = WpPost::tours()->where('ID', $tourId)->firstOrFail();
        $hotel = TourHotel::getForTour($tourId);
        if ($hotel) {
            $hotel->load('rooms');
        }

        return view('admin.circuits.tour-hotels.edit', compact('tour', 'hotel'));
    }

    /**
     * Enregistrer ou créer l'hôtel du tour (un seul par tour) + sync des types de chambres.
     */
    public function update(Request $request, int $tourId): RedirectResponse
    {
        $tour = WpPost::tours()->where('ID', $tourId)->firstOrFail();

        $validated = $request->validate([
            'hotel_name' => 'nullable|string|max:255',
            'stars' => 'nullable|integer|min:0|max:5',
            'address' => 'nullable|string|max:500',
            'room_type' => 'nullable|string|max:255',
            'meal_plan' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'image_id' => 'nullable|integer|min:0',
        ]);

        $hotel = TourHotel::getForTour($tourId);
        if ($hotel) {
            $hotel->update($validated);
        } else {
            $hotel = TourHotel::create(array_merge($validated, ['tour_id' => $tourId]));
        }

        $this->syncRooms($hotel->id, $request);

        return redirect()
            ->route('admin.circuits.tour-hotels.show', $tourId)
            ->with('success', 'Hôtel et types de chambres enregistrés.');
    }

    /**
     * Sync des types de chambres pour un tour_hotel (fiche hôtel dédiée).
     */
    private function syncRooms(int $tourHotelId, Request $request): void
    {
        $roomsInput = $request->input('rooms', []);
        if (!is_array($roomsInput)) {
            return;
        }

        $keptRoomIds = [];
        $sortOrder = 0;
        foreach ($roomsInput as $r) {
            if (!is_array($r)) {
                continue;
            }
            $roomType = $r['room_type'] ?? null;
            if ($roomType === null || $roomType === '') {
                continue;
            }
            $roomId = isset($r['id']) && $r['id'] !== '' ? (int) $r['id'] : null;
            $payload = [
                'tour_hotel_id' => $tourHotelId,
                'room_type' => $roomType,
                'room_label' => $r['room_label'] ?? null,
                'room_code' => $r['room_code'] ?? null,
                'room_count' => max(1, (int) ($r['room_count'] ?? 1)),
                'capacity_adults' => max(0, (int) ($r['capacity_adults'] ?? 0)),
                'capacity_children' => max(0, (int) ($r['capacity_children'] ?? 0)),
                'capacity_total' => max(1, (int) ($r['capacity_total'] ?? 1)),
                'supplement' => max(0, (float) ($r['supplement'] ?? 0)),
                'description' => $r['description'] ?? null,
                'is_active' => !empty($r['is_active']),
                'sort_order' => $sortOrder++,
                'is_default' => !empty($r['is_default']),
                'notes' => $r['notes'] ?? null,
            ];
            if ($roomId && TourHotelRoom::where('tour_hotel_id', $tourHotelId)->where('id', $roomId)->exists()) {
                TourHotelRoom::where('id', $roomId)->update($payload);
                $keptRoomIds[] = $roomId;
            } else {
                $maxId = (int) TourHotelRoom::query()->max('id');
                $payload['id'] = $maxId > 0 ? ($maxId + 1) : 1;
                $room = TourHotelRoom::create($payload);
                $keptRoomIds[] = $room->id;
            }
        }
        TourHotelRoom::where('tour_hotel_id', $tourHotelId)->whereNotIn('id', $keptRoomIds)->delete();
    }

    private function firstNonEmpty(array $values): ?string
    {
        foreach ($values as $value) {
            $text = trim((string) ($value ?? ''));
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    private function asInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function normalizeStars(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $stars = (int) round((float) str_replace(',', '.', (string) $value));

        return max(0, min(5, $stars));
    }

    private function parseCsvIds(mixed $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', explode(',', $value)))));
    }

    private function resolveHotelCity(object|null $detail, array $metas): ?string
    {
        $locationId = $this->asInt($detail?->id_location ?? null);
        if ($locationId <= 0) {
            $locationId = $this->extractFirstMultiLocationId(
                $detail?->multi_location ?? ($metas['multi_location'] ?? null)
            );
        }

        if ($locationId > 0) {
            $locationTitle = DB::connection('wp')
                ->table('posts')
                ->where('ID', $locationId)
                ->where('post_type', 'location')
                ->value('post_title');
            $locationTitle = trim((string) ($locationTitle ?? ''));
            if ($locationTitle !== '') {
                return $locationTitle;
            }
        }

        return $this->firstNonEmpty([
            $metas['city'] ?? null,
            $metas['location'] ?? null,
        ]);
    }

    private function extractFirstMultiLocationId(mixed $value): int
    {
        if (! is_string($value) || trim($value) === '') {
            return 0;
        }

        if (preg_match('/_(\d+)_/', $value, $matches) === 1) {
            return (int) ($matches[1] ?? 0);
        }

        return 0;
    }
}
