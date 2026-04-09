<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\DepartureHotelRoom;
use App\Models\Hotel;
use App\Models\TourHotel;
use App\Models\TourTransfer;
use App\Models\Voyage;
use App\Models\VoyageDeparturePlace;
use App\Models\VoyageTheme;
use App\Models\Wp\WpPost;
use App\Models\Wp\WpPostMeta;
use App\Services\Wp\WpHeroImageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class VoyageController extends Controller
{
    private const VISIBLE_STATUSES = ['actif', 'published', 'active'];

    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', $request->input('s', '')));
        $themeSlug = trim((string) $request->input('theme', $request->input('cat', '')));
        $destination = trim((string) $request->input('destination', ''));
        $departDate = trim((string) $request->input('depart_date', ''));
        $sort = (string) $request->input('catalog_orderby', 'date');
        if (! in_array($sort, ['date', 'title', 'title_desc'], true)) {
            $sort = 'date';
        }

        $voyagesQuery = Voyage::query()
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->with(['themes' => fn ($t) => $t->orderBy('sort_order')->orderBy('name')]);

        if ($q !== '') {
            $like = '%'.$q.'%';
            $voyagesQuery->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                    ->orWhere('destination', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        if ($themeSlug !== '') {
            $voyagesQuery->whereHas('themes', function ($w) use ($themeSlug) {
                if (ctype_digit($themeSlug)) {
                    $w->where('voyage_themes.id', (int) $themeSlug);
                } else {
                    $w->where('voyage_themes.slug', $themeSlug);
                }
            });
        }

        if ($destination !== '') {
            $voyagesQuery->where('destination', $destination);
        }

        if ($departDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $departDate)) {
            $voyagesQuery->whereHas('departures', function ($w) use ($departDate) {
                $w->whereDate('start_date', $departDate);
            });
        }

        if ($sort === 'title') {
            $voyagesQuery->orderBy('name');
        } elseif ($sort === 'title_desc') {
            $voyagesQuery->orderByDesc('name');
        } else {
            $voyagesQuery->orderByDesc('updated_at');
        }

        $voyages = $voyagesQuery
            ->select([
                'id', 'name', 'slug', 'destination', 'duration_text',
                'price_from', 'old_price', 'currency', 'featured_image', 'updated_at',
            ])
            ->paginate(12)
            ->withQueryString();

        $themeOptions = $this->themeOptionsForCatalog();

        $destinationOptions = Voyage::query()
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->whereNotNull('destination')
            ->where('destination', '!=', '')
            ->distinct()
            ->orderBy('destination')
            ->pluck('destination')
            ->values();

        $hasFilters = $q !== '' || $themeSlug !== '' || $destination !== '' || ($departDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $departDate));

        return view('front.voyages.index', [
            'voyages' => $voyages,
            'themeOptions' => $themeOptions,
            'destinationOptions' => $destinationOptions,
            'filters' => [
                'q' => $q,
                'theme' => $themeSlug,
                'destination' => $destination,
                'depart_date' => $departDate,
                'catalog_orderby' => $sort,
            ],
            'hasFilters' => $hasFilters,
            'pageTitle' => $hasFilters ? 'Offres correspondantes' : 'Tous les voyages',
            'pageSubtitle' => 'Parcourez nos circuits et séjours. Affinez par thème, destination ou date de départ.',
        ]);
    }

    /**
     * Thèmes pour le select : union des thèmes actifs et des thèmes réellement attachés
     * à au moins un voyage visible, puis repli sur toute la table si besoin.
     *
     * @return Collection<int, VoyageTheme>
     */
    private function themeOptionsForCatalog(): Collection
    {
        if (! Schema::hasTable('voyage_themes')) {
            return collect();
        }

        $usedIds = collect();
        if (Schema::hasTable('voyage_voyage_theme')) {
            $usedIds = DB::table('voyage_voyage_theme')
                ->join('voyages', 'voyages.id', '=', 'voyage_voyage_theme.voyage_id')
                ->whereIn('voyages.status', self::VISIBLE_STATUSES)
                ->distinct()
                ->pluck('voyage_theme_id');
        }

        $merged = VoyageTheme::query()
            ->where(function ($q) use ($usedIds) {
                $q->where('is_active', true);
                if ($usedIds->isNotEmpty()) {
                    $q->orWhereIn('id', $usedIds);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->unique('id')
            ->values();

        if ($merged->isEmpty()) {
            $merged = VoyageTheme::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        return $merged->values();
    }

    public function show(string $slug): View
    {
        $with = [
            'images',
            'programDays' => fn ($q) => $q->orderBy('day_number'),
            'programDays.hotel',
            'flights.airline',
            'flightOptions' => fn ($q) => $q->orderBy('type')->orderBy('sort_order'),
            'flightOptions.airline',
            'extras' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
            'departures' => fn ($q) => $q->orderBy('start_date'),
            'departures.departureHotels' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
            'departures.departureHotels.rooms' => fn ($q) => $q->whereNotIn('status', [
                DepartureHotelRoom::STATUS_CLOSED,
                DepartureHotelRoom::STATUS_INACTIVE,
            ])->orderBy('room_type'),
            'departures.roomAllocations' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
        ];

        $voyage = $this->resolveVoyage($slug, $with);
        if (! $voyage) {
            abort(404);
        }

        $today = Carbon::today();

        $departures = $voyage->departures
            ->filter(fn (Departure $d) => $d->start_date && ! $d->start_date->lt($today))
            ->values();

        $nextDeparture = $departures->first(fn (Departure $d) => in_array($d->status, ['open', 'limited'], true));

        $wpTourId = $voyage->wp_post_id ? (int) $voyage->wp_post_id : null;
        $tourHotels = $wpTourId ? TourHotel::getAllForTour($wpTourId) : collect();
        $transfers = $wpTourId ? TourTransfer::getForTour($wpTourId) : ['arrival' => collect(), 'departure' => collect()];

        $priceFrom = $voyage->price_from;
        if (! $priceFrom && $wpTourId) {
            $wpAdult = WpPostMeta::where('post_id', $wpTourId)->where('meta_key', 'adult_price')->value('meta_value');
            $priceFrom = $wpAdult ? (int) $wpAdult : 0;
        }

        $heroImages = $this->resolveHeroImages($voyage, $wpTourId);
        $heroImageUrls = array_values(array_filter(array_map(
            fn ($i) => is_array($i) ? ($i['url'] ?? null) : null,
            $heroImages
        )));

        $highlights = $this->resolveHighlights($voyage);
        $includes = $this->resolveListField($voyage->tours_include);
        $excludes = $this->resolveListField($voyage->tours_exclude);

        $departurePlaces = VoyageDeparturePlace::where('voyage_id', $voyage->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $departuresJson = $departures->map(function (Departure $d) {
            $roomsFromDepartureHotels = $d->departureHotels
                ->flatMap(fn ($dh) => $dh->rooms->map(fn ($r) => [
                    'id' => (int) $r->id,
                    'departure_hotel_id' => (int) ($dh->id ?? 0),
                    'hotel_name' => $dh->hotel_name ?: null,
                    'room_type' => $r->room_type,
                    'status' => $r->status,
                    'supplement' => (float) ($r->supplement ?? 0),
                    'capacity_per_room' => (int) ($r->capacity_total ?? 0),
                    'total_rooms' => (int) ($r->total_rooms ?? 0),
                    'reserved_rooms' => (int) ($r->reserved_rooms ?? 0),
                    'available_rooms' => (int) ($r->available_rooms ?? 0),
                    'total_places' => (int) ($r->total_places ?? 0),
                    'reserved_places' => (int) ($r->reserved_places ?? 0),
                    'available_places' => (int) ($r->available_places ?? 0),
                    'effective_capacity' => max(0, (int) ($r->available_rooms ?? 0)) * max(1, (int) ($r->capacity_total ?? 1)),
                ]))
                ->filter(fn ($row) => is_string($row['room_type'] ?? null) && trim((string) $row['room_type']) !== '')
                ->values();

            $rooms = $roomsFromDepartureHotels;

            // Fallback to allocations when #hotels uses "Répartition des chambres par depart"
            // (and no per-departure hotel/room stock rows exist).
            if ($rooms->isEmpty() && $d->roomAllocations->isNotEmpty()) {
                $hotelNames = Hotel::query()
                    ->whereIn('id', $d->roomAllocations->pluck('hotel_id')->filter(fn ($id) => (int) $id > 0)->unique()->values())
                    ->get(['id', 'name'])
                    ->keyBy('id');

                $rooms = $d->roomAllocations
                    ->map(function ($allocation) use ($hotelNames) {
                        $qty = max(0, (int) ($allocation->quantity ?? 0));
                        $cap = max(1, (int) ($allocation->capacity_per_room ?? 1));
                        $hotelId = (int) ($allocation->hotel_id ?? 0);

                        return [
                            'id' => null,
                            'departure_hotel_id' => null,
                            'hotel_name' => $hotelId > 0
                                ? (optional($hotelNames->get($hotelId))->name ?: null)
                                : null,
                            'room_type' => (string) ($allocation->room_type ?? ''),
                            'status' => DepartureHotelRoom::STATUS_AVAILABLE,
                            'supplement' => 0.0,
                            'capacity_per_room' => $cap,
                            'total_rooms' => $qty,
                            'reserved_rooms' => 0,
                            'available_rooms' => $qty,
                            'total_places' => $qty * $cap,
                            'reserved_places' => 0,
                            'available_places' => $qty * $cap,
                            'effective_capacity' => $qty * $cap,
                            'source' => 'allocations',
                            'allocation_id' => (int) ($allocation->id ?? 0),
                        ];
                    })
                    ->filter(fn ($row) => is_string($row['room_type'] ?? null) && trim((string) $row['room_type']) !== '')
                    ->values();
            }

            return [
                'id' => (int) $d->id,
                'wp_travel_date_id' => (int) ($d->wp_travel_date_id ?? 0),
                'start_date' => $d->start_date->format('Y-m-d'),
                'end_date' => $d->end_date?->format('Y-m-d'),
                'start_label' => $d->start_date->locale('fr')->translatedFormat('d M Y'),
                'end_label' => $d->end_date?->locale('fr')->translatedFormat('d M Y'),
                'status' => $d->status ?? 'open',
                'available_capacity' => (int) ($d->available_capacity ?? 0),
                'base_price' => (float) ($d->base_price ?? 0),
                'sale_price' => (float) ($d->sale_price ?? 0),
                'rooms' => $rooms->all(),
            ];
        })->values()->all();

        $placesJson = $departurePlaces->map(fn ($p) => [
            'id' => (int) $p->id,
            'name' => (string) $p->name,
            'code' => (string) ($p->code ?? ''),
            'price' => (float) ($p->price ?? 0),
        ])->values()->all();

        $extrasJson = $voyage->extras->map(fn ($e) => [
            'id' => (int) $e->id,
            'name' => (string) $e->name,
            'description' => (string) ($e->description ?? ''),
            'price_adult' => (float) $e->price_adult,
            'price_child' => (float) $e->price_child,
            'icon' => (string) ($e->icon ?? 'fa-plus-circle'),
            'extra_type' => (string) ($e->extra_type ?? ''),
        ])->values()->all();

        $flightsByPlace = $voyage->flightOptions
            ->groupBy('departure_place_id')
            ->map(fn ($opts) => $opts->map(fn ($o) => [
                'type' => $o->type,
                'from_city' => $o->from_city,
                'to_city' => $o->to_city,
                'airline' => $o->airline->name ?? null,
                'flight_number' => $o->flight_number,
                'depart_at' => $o->depart_at?->format('H:i'),
                'arrive_at' => $o->arrive_at?->format('H:i'),
                'duration_minutes' => $o->duration_minutes,
                'baggage_checkin_kg' => $o->baggage_checkin_kg,
                'is_tentative' => (bool) $o->is_tentative,
            ])->values()->all())
            ->all();

        $similarVoyages = Voyage::query()
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->where('id', '!=', $voyage->id)
            ->when($voyage->destination, fn ($q) => $q->where('destination', $voyage->destination))
            ->select(['id', 'name', 'slug', 'destination', 'duration_text', 'price_from', 'currency', 'featured_image'])
            ->limit(3)
            ->get();

        return view('front.voyages.show', [
            'voyage' => $voyage,
            'priceFrom' => $priceFrom,
            'heroImages' => $heroImages,
            'heroImageUrls' => $heroImageUrls,
            'nextDeparture' => $nextDeparture,
            'departures' => $departures,
            'tourHotels' => $tourHotels,
            'transfers' => $transfers,
            'highlights' => $highlights,
            'includes' => $includes,
            'excludes' => $excludes,
            'departurePlaces' => $departurePlaces,
            'similarVoyages' => $similarVoyages,
            'departuresJson' => $departuresJson,
            'placesJson' => $placesJson,
            'extrasJson' => $extrasJson,
            'flightsByPlace' => $flightsByPlace,
        ]);
    }

    private function resolveVoyage(string $slug, array $with): ?Voyage
    {
        $voyage = Voyage::query()
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->where('slug', $slug)
            ->with($with)
            ->first();

        if (! $voyage) {
            $voyage = Voyage::query()->where('slug', $slug)->with($with)->first();
        }

        if (! $voyage && preg_match('/^tour-(\\d+)$/', $slug, $m)) {
            $wpId = (int) $m[1];
            $voyage = Voyage::query()->where('wp_post_id', $wpId)->with($with)->first();

            if (! $voyage) {
                $wp = WpPost::tours()->where('ID', $wpId)->first();
                if ($wp) {
                    $voyage = Voyage::firstOrCreate(
                        ['wp_post_id' => $wpId],
                        [
                            'name' => (string) ($wp->post_title ?? ('Tour #'.$wpId)),
                            'slug' => 'tour-'.$wpId,
                            'status' => ($wp->post_status === 'publish') ? 'published' : 'active',
                        ]
                    );
                    $voyage->load($with);
                }
            }
        }

        return $voyage;
    }

    /**
     * Collect hero + gallery images from multiple sources:
     * 1. WP hero meta (_tour_hero_image_id, _tour_hero_gallery_ids, _thumbnail_id)
     * 2. Laravel VoyageImage rows
     * 3. Voyage->featured_image field
     *
     * @return list<array{url:string, srcset?:string, sizes?:string, thumb_url?:string, width?:int, height?:int}> Ordered images (hero first)
     */
    private function resolveHeroImages(Voyage $voyage, ?int $wpTourId): array
    {
        $images = [];
        $wpUploadBase = WpHeroImageService::getUploadsBaseUrl();
        if ($wpUploadBase === '') {
            $wpUploadBase = rtrim((string) config('app.wp_upload_url'), '/');
        }

        if ($wpTourId) {
            $metas = WpPostMeta::where('post_id', $wpTourId)
                ->whereIn('meta_key', ['_tour_hero_image_id', '_tour_hero_gallery_ids', '_thumbnail_id'])
                ->pluck('meta_value', 'meta_key');

            $wpIds = [];
            if (! empty($metas['_tour_hero_image_id'])) {
                $wpIds[] = (int) $metas['_tour_hero_image_id'];
            }
            if (! empty($metas['_tour_hero_gallery_ids'])) {
                foreach (explode(',', $metas['_tour_hero_gallery_ids']) as $id) {
                    $id = (int) trim($id);
                    if ($id > 0) {
                        $wpIds[] = $id;
                    }
                }
            }
            if (! empty($metas['_thumbnail_id'])) {
                $wpIds[] = (int) $metas['_thumbnail_id'];
            }

            $wpIds = array_values(array_unique(array_filter($wpIds)));

            if ($wpIds !== []) {
                $rows = WpPostMeta::whereIn('post_id', $wpIds)
                    ->whereIn('meta_key', ['_wp_attached_file', '_wp_attachment_metadata'])
                    ->get(['post_id', 'meta_key', 'meta_value'])
                    ->groupBy('post_id')
                    ->map(fn ($g) => $g->pluck('meta_value', 'meta_key'));

                foreach ($wpIds as $mid) {
                    $attached = $rows[$mid]['_wp_attached_file'] ?? null;
                    if (! $attached) {
                        continue;
                    }

                    $fullUrl = WpHeroImageService::publicUrlForAttachmentId((int) $mid)
                        ?? ($wpUploadBase.'/'.ltrim((string) $attached, '/'));
                    $metaRaw = $rows[$mid]['_wp_attachment_metadata'] ?? null;
                    $images[] = $this->buildWpResponsiveImageSet(
                        fullUrl: $fullUrl,
                        attachedFile: (string) $attached,
                        metaSerialized: is_string($metaRaw) ? $metaRaw : null,
                        wpUploadBase: $wpUploadBase
                    );
                }
            }
        }

        if ($images === []) {
            $featuredUrl = $voyage->featured_image_url;
            if ($featuredUrl) {
                $images[] = ['url' => $featuredUrl];
            }
        }

        if ($images === [] && $voyage->images->isNotEmpty()) {
            foreach ($voyage->images as $img) {
                $u = $img->url ?? null;
                if ($u) {
                    $images[] = ['url' => $u];
                }
            }
        }

        $seen = [];
        $out = [];
        foreach ($images as $it) {
            $url = $it['url'] ?? null;
            if (! $url || isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;
            $out[] = $it;
        }

        return $out;
    }

    /**
     * @return array{url:string, srcset?:string, sizes?:string, thumb_url?:string, width?:int, height?:int}
     */
    private function buildWpResponsiveImageSet(string $fullUrl, string $attachedFile, ?string $metaSerialized, string $wpUploadBase): array
    {
        $result = ['url' => $fullUrl];

        if (! $metaSerialized) {
            return $result;
        }

        $meta = @unserialize($metaSerialized);
        if (! is_array($meta)) {
            return $result;
        }

        $baseDir = str_replace('\\', '/', dirname($attachedFile));
        $baseDir = $baseDir === '.' ? '' : trim($baseDir, '/');

        $candidates = [];
        $fullW = isset($meta['width']) ? (int) $meta['width'] : null;
        $fullH = isset($meta['height']) ? (int) $meta['height'] : null;
        if ($fullW) {
            $result['width'] = $fullW;
            $candidates[$fullW] = $fullUrl;
        }
        if ($fullH) {
            $result['height'] = $fullH;
        }

        $sizes = $meta['sizes'] ?? null;
        if (is_array($sizes)) {
            foreach ($sizes as $info) {
                if (! is_array($info)) {
                    continue;
                }
                $w = isset($info['width']) ? (int) $info['width'] : null;
                $file = $info['file'] ?? null;
                if (! $w || ! $file) {
                    continue;
                }
                $u = rtrim($wpUploadBase, '/').'/'.ltrim(($baseDir ? $baseDir.'/' : '').$file, '/');
                $candidates[$w] = $u;
            }
        }

        if ($candidates !== []) {
            ksort($candidates);
            $srcset = [];
            foreach ($candidates as $w => $u) {
                $srcset[] = $u.' '.$w.'w';
            }
            $result['srcset'] = implode(', ', $srcset);
            $result['sizes'] = '100vw';
        }

        $thumbUrl = null;
        if (is_array($sizes) && isset($sizes['thumbnail']['file'])) {
            $thumbUrl = rtrim($wpUploadBase, '/').'/'.ltrim(($baseDir ? $baseDir.'/' : '').$sizes['thumbnail']['file'], '/');
        } elseif ($candidates !== []) {
            foreach ($candidates as $w => $u) {
                if ($w >= 300 && $w <= 600) {
                    $thumbUrl = $u;
                    break;
                }
            }
        }

        if ($thumbUrl) {
            $result['thumb_url'] = $thumbUrl;
        }

        return $result;
    }

    private function resolveHighlights(Voyage $voyage): array
    {
        $raw = $voyage->tours_highlight;
        if (is_array($raw) && $raw !== []) {
            return array_filter(array_map(fn ($v) => is_string($v) ? trim($v) : (is_array($v) ? trim($v['text'] ?? $v['title'] ?? '') : ''), $raw));
        }

        return [];
    }

    private function resolveListField(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            } elseif (is_array($item)) {
                $text = trim($item['text'] ?? $item['title'] ?? $item['name'] ?? '');
                if ($text !== '') {
                    $out[] = $text;
                }
            }
        }

        return $out;
    }
}
