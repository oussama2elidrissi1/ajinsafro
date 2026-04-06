<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\TourHotel;
use App\Models\TourTransfer;
use App\Models\Voyage;
use App\Models\VoyageFlightOption;
use App\Models\Wp\WpPost;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoyageController extends Controller
{
    private const VISIBLE_STATUSES = ['actif', 'published', 'active'];

    public function index(Request $request): View
    {
        $voyages = Voyage::query()
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->select([
                'id', 'name', 'slug', 'destination', 'duration_text',
                'price_from', 'old_price', 'currency', 'featured_image',
            ])
            ->orderBy('name')
            ->paginate(12);

        return view('front.voyages.index', [
            'voyages' => $voyages,
        ]);
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
        ];

        // PROD SAFE: certains environnements n’ont pas le `slug/status` Laravel synchronisé.
        // - 1) tente la route standard par slug (si status OK)
        // - 2) tente par slug sans contrainte status (si status NULL / différent)
        // - 3) supporte le format `/voyages/tour-{wpId}` via `wp_post_id`
        $voyage = Voyage::query()
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->where('slug', $slug)
            ->with($with)
            ->first();

        if (! $voyage) {
            $voyage = Voyage::query()
                ->where('slug', $slug)
                ->with($with)
                ->first();
        }

        if (! $voyage && preg_match('/^tour-(\\d+)$/', $slug, $m)) {
            $wpId = (int) $m[1];
            $voyage = Voyage::query()
                ->where('wp_post_id', $wpId)
                ->with($with)
                ->first();

            // Fallback ultime: si la ligne Laravel n’existe pas, on la crée (slug = tour-{wpId}).
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

        $highlights = $this->resolveHighlights($voyage);
        $includes = $this->resolveListField($voyage->tours_include);
        $excludes = $this->resolveListField($voyage->tours_exclude);

        $similarVoyages = Voyage::query()
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->where('id', '!=', $voyage->id)
            ->when($voyage->destination, fn ($q) => $q->where('destination', $voyage->destination))
            ->select(['id', 'name', 'slug', 'destination', 'duration_text', 'price_from', 'currency', 'featured_image'])
            ->limit(3)
            ->get();

        return view('front.voyages.show', [
            'voyage' => $voyage,
            'nextDeparture' => $nextDeparture,
            'departures' => $departures,
            'tourHotels' => $tourHotels,
            'transfers' => $transfers,
            'highlights' => $highlights,
            'includes' => $includes,
            'excludes' => $excludes,
            'similarVoyages' => $similarVoyages,
        ]);
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
