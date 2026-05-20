<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TailorMadeRequest;
use App\Models\Voyage;
use App\Services\Wp\WpHeroImageService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TailorMadeRequestController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('reservations.view'), 403);

        $quickStatus = trim((string) $request->query('status', 'all')) ?: 'all';
        $statusFilter = trim((string) $request->query('request_status', ''));
        $search = trim((string) $request->query('search', ''));
        $period = trim((string) $request->query('period', '30d')) ?: '30d';
        $voyageId = (int) $request->query('voyage_id', 0);
        $requestedDate = trim((string) $request->query('requested_date', ''));
        $departurePlace = trim((string) $request->query('departure_place', ''));

        $query = TailorMadeRequest::query();

        if ($period !== 'all') {
            $now = Carbon::now();
            $from = match ($period) {
                '7d' => $now->copy()->subDays(7),
                '90d' => $now->copy()->subDays(90),
                default => $now->copy()->subDays(30),
            };
            $query->where('created_at', '>=', $from);
        }

        if ($quickStatus !== 'all') {
            // quick tabs map to status groups
            if ($quickStatus === 'active') {
                $query->whereIn('status', [
                    TailorMadeRequest::STATUS_NEW,
                    TailorMadeRequest::STATUS_PENDING,
                    TailorMadeRequest::STATUS_PROCESSING,
                ]);
            } else {
                $query->where('status', $quickStatus);
            }
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($voyageId > 0) {
            $query->where('voyage_id', $voyageId);
        }

        if ($requestedDate !== '') {
            $query->whereDate('custom_departure_date', $requestedDate);
        }

        if ($departurePlace !== '') {
            $like = '%' . $departurePlace . '%';
            $query->where('custom_departure_place', 'like', $like);
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('tour_title', 'like', $like)
                    ->orWhere('custom_departure_place', 'like', $like)
                    ->orWhere('client_first_name', 'like', $like)
                    ->orWhere('client_last_name', 'like', $like)
                    ->orWhere('client_phone', 'like', $like)
                    ->orWhere('client_email', 'like', $like)
                    ->orWhere('message', 'like', $like)
                    ->orWhere('id', 'like', $like);
            });
        }

        // Guardrail: this is used to build grouped cards in memory.
        $requests = $query
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get();

        $voyageIds = $requests
            ->pluck('voyage_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->unique()
            ->values()
            ->all();

        $voyagesById = Voyage::query()
            ->whereIn('id', $voyageIds)
            ->get(['id', 'name', 'destination', 'slug', 'featured_image', 'wp_post_id'])
            ->keyBy('id');

        $cards = collect();
        $grouped = $requests->groupBy(function (TailorMadeRequest $r) {
            if ((int) $r->voyage_id > 0) {
                return 'voyage:' . (int) $r->voyage_id;
            }
            if ((int) $r->wp_post_id > 0) {
                return 'wp:' . (int) $r->wp_post_id;
            }
            return 'title:' . md5((string) ($r->tour_title ?: ''));
        });

        foreach ($grouped as $key => $items) {
            $items = $items->sortByDesc(fn (TailorMadeRequest $r) => optional($r->created_at)->timestamp ?? 0)->values();
            if ($items->isEmpty()) {
                continue;
            }

            $first = $items->first();
            $voyage = ((int) ($first->voyage_id ?? 0) > 0) ? $voyagesById->get((int) $first->voyage_id) : null;

            $title = $voyage?->name ?: ($first->tour_title ?: ('Voyage ' . ($first->voyage_id ?: $first->wp_post_id ?: $first->id)));
            $title = html_entity_decode((string) $title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $destination = $voyage?->destination ?: null;

            $imageUrl = $this->resolveOfferImageUrl($voyage);

            $statusCounts = [
                TailorMadeRequest::STATUS_NEW => 0,
                TailorMadeRequest::STATUS_PENDING => 0,
                TailorMadeRequest::STATUS_PROCESSING => 0,
                TailorMadeRequest::STATUS_DONE => 0,
                TailorMadeRequest::STATUS_CANCELLED => 0,
            ];
            foreach ($items as $r) {
                $s = (string) ($r->status ?: TailorMadeRequest::STATUS_NEW);
                if (array_key_exists($s, $statusCounts)) {
                    $statusCounts[$s] += 1;
                }
            }

            $primaryDate = $items
                ->pluck('custom_departure_date')
                ->filter()
                ->map(fn ($d) => (string) $d)
                ->countBy()
                ->sortDesc()
                ->keys()
                ->first();

            $dateLabel = $primaryDate
                ? Carbon::parse($primaryDate)->locale('fr')->translatedFormat('d F Y')
                : 'Dates personnalisées';

            $cards->push((object) [
                'key' => $key,
                'voyage_id' => (int) ($voyage?->id ?? $first->voyage_id ?? 0) ?: null,
                'wp_post_id' => (int) ($voyage?->wp_post_id ?? $first->wp_post_id ?? 0) ?: null,
                'image_url' => $imageUrl,
                'title' => $title,
                'destination' => $destination,
                'requested_date_label' => $dateLabel,
                'requests' => $items,
                'requests_count' => $items->count(),
                'new_count' => $statusCounts[TailorMadeRequest::STATUS_NEW],
                'pending_count' => $statusCounts[TailorMadeRequest::STATUS_PENDING],
                'processing_count' => $statusCounts[TailorMadeRequest::STATUS_PROCESSING],
                'done_count' => $statusCounts[TailorMadeRequest::STATUS_DONE],
                'cancelled_count' => $statusCounts[TailorMadeRequest::STATUS_CANCELLED],
            ]);
        }

        $cards = $cards->sortByDesc(fn ($c) => optional($c->requests->first()?->created_at)->timestamp ?? 0)->values();

        $statsQuery = TailorMadeRequest::query();
        if ($period !== 'all') {
            $now = Carbon::now();
            $from = match ($period) {
                '7d' => $now->copy()->subDays(7),
                '90d' => $now->copy()->subDays(90),
                default => $now->copy()->subDays(30),
            };
            $statsQuery->where('created_at', '>=', $from);
        }

        $stats = [
            'active' => (clone $statsQuery)->whereIn('status', [
                TailorMadeRequest::STATUS_NEW,
                TailorMadeRequest::STATUS_PENDING,
                TailorMadeRequest::STATUS_PROCESSING,
            ])->count(),
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', TailorMadeRequest::STATUS_PENDING)->count(),
            'to_process' => (clone $statsQuery)->whereIn('status', [TailorMadeRequest::STATUS_NEW, TailorMadeRequest::STATUS_PENDING])->count(),
            'done' => (clone $statsQuery)->where('status', TailorMadeRequest::STATUS_DONE)->count(),
            'cancelled' => (clone $statsQuery)->where('status', TailorMadeRequest::STATUS_CANCELLED)->count(),
        ];

        $perPage = max(1, min(24, (int) $request->query('per_page', 9)));
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $cards->forPage($currentPage, $perPage)->values(),
            $cards->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.tailor-made-requests.index', [
            'voyages' => $paginator,
            'stats' => $stats,
            'currentStatus' => $quickStatus,
            'filters' => [
                'search' => $search,
                'request_status' => $statusFilter,
                'period' => $period,
                'voyage_id' => $voyageId > 0 ? $voyageId : null,
                'requested_date' => $requestedDate,
                'departure_place' => $departurePlace,
                'per_page' => $perPage,
            ],
            'voyageOptions' => Voyage::query()->orderBy('name')->limit(300)->get(['id', 'name']),
            'statusOptions' => TailorMadeRequest::statusOptions(),
        ]);
    }

    public function show(Request $request, TailorMadeRequest $tailorMadeRequest): View
    {
        abort_unless($request->user()->can('reservations.view'), 403);

        $tailorMadeRequest->tour_title = html_entity_decode((string) ($tailorMadeRequest->tour_title ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $voyage = null;
        if ((int) $tailorMadeRequest->voyage_id > 0) {
            $voyage = Voyage::query()->find((int) $tailorMadeRequest->voyage_id);
        }

        $imageUrl = $this->resolveOfferImageUrl($voyage);

        $backUrl = url()->previous();
        if (! $backUrl || str_contains($backUrl, '/admin/demande-a-la-carte/'.$tailorMadeRequest->id)) {
            $backUrl = route('admin.tailor-made-requests.index');
        }

        return view('admin.tailor-made-requests.show', [
            'req' => $tailorMadeRequest,
            'voyage' => $voyage,
            'imageUrl' => $imageUrl,
            'backUrl' => $backUrl,
            'statusOptions' => TailorMadeRequest::statusOptions(),
        ]);
    }

    public function updateStatus(Request $request, TailorMadeRequest $tailorMadeRequest): RedirectResponse
    {
        abort_unless($request->user()->can('reservations.update'), 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_keys(TailorMadeRequest::statusOptions()))],
        ]);

        $tailorMadeRequest->status = $validated['status'];
        $tailorMadeRequest->save();

        return redirect()
            ->back()
            ->with('success', 'Statut mis a jour.');
    }

    public function destroy(Request $request, TailorMadeRequest $tailorMadeRequest): RedirectResponse
    {
        abort_unless($request->user()->can('reservations.delete'), 403);

        $tailorMadeRequest->delete();

        return redirect()
            ->route('admin.tailor-made-requests.index')
            ->with('success', 'Demande supprimee.');
    }

    private function resolveOfferImageUrl(?Voyage $offer): ?string
    {
        if (! $offer) {
            return null;
        }

        if (! empty($offer->image_url)) {
            return $offer->image_url;
        }

        if (! empty($offer->featured_image_url)) {
            return $offer->featured_image_url;
        }

        if (! empty($offer->cover_image)) {
            return asset('storage/' . ltrim((string) $offer->cover_image, '/'));
        }

        if (! empty($offer->thumbnail)) {
            return asset('storage/' . ltrim((string) $offer->thumbnail, '/'));
        }

        if (! empty($offer->featured_image)) {
            $featuredImage = (string) $offer->featured_image;

            return str_starts_with($featuredImage, 'http://') || str_starts_with($featuredImage, 'https://')
                ? $featuredImage
                : asset('storage/' . ltrim($featuredImage, '/'));
        }

        if (! empty($offer->wp_image_url)) {
            return $offer->wp_image_url;
        }

        if ((int) ($offer->wp_post_id ?? 0) > 0) {
            $heroImageId = \App\Models\Wp\WpPostMeta::query()
                ->where('post_id', (int) $offer->wp_post_id)
                ->whereIn('meta_key', ['_tour_hero_image_id', '_thumbnail_id'])
                ->orderByRaw("FIELD(meta_key, '_tour_hero_image_id', '_thumbnail_id')")
                ->value('meta_value');

            if ((int) $heroImageId > 0) {
                return WpHeroImageService::publicUrlForAttachmentId((int) $heroImageId);
            }
        }

        return null;
    }
}

