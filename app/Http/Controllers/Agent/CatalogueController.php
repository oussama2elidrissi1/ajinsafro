<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use App\Models\Wp\WpPost;
use App\Models\Wp\WpPostMeta;
use App\Services\AdminWpTourCatalogQuery;
use App\Services\Reservations\ReservationPricingService;
use App\Services\View\AgentPortalLayout;
use App\Services\Wp\WpHeroImageService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    public function __construct(
        protected ReservationPricingService $reservationPricing,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless(
            $user && AgentPortalLayout::shouldUse($user) && ($user->can('reservations.view') || $user->can('reservations.create')),
            403
        );

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'destination' => trim((string) $request->query('destination', '')),
            'date' => trim((string) $request->query('date', '')),
            'budget_max' => (int) $request->query('budget_max', 0),
        ];

        $today = Carbon::today();
        $publishedIds = AdminWpTourCatalogQuery::publishedWpTourIds();

        $baseQuery = Voyage::query()
            ->where('status', 'actif')
            ->whereNotNull('wp_post_id')
            ->where('wp_post_id', '>', 0)
            ->whereHas('departures', fn (Builder $query) => $query->whereDate('start_date', '>=', $today))
            ->when($publishedIds !== [], fn (Builder $query) => $query->whereIn('wp_post_id', $publishedIds))
            ->when($publishedIds === [], fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%test%']);

        $destinationOptions = (clone $baseQuery)
            ->whereNotNull('destination')
            ->pluck('destination')
            ->map(fn ($destination) => trim((string) $destination))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $voyages = $baseQuery
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = '%'.mb_strtolower($filters['search']).'%';
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->whereRaw('LOWER(name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(destination) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(accroche) LIKE ?', [$search]);
                });
            })
            ->when($filters['destination'] !== '', fn (Builder $query) => $query->where('destination', $filters['destination']))
            ->when($filters['date'] !== '', function (Builder $query) use ($filters): void {
                $query->whereHas('departures', fn (Builder $departureQuery) => $departureQuery->whereDate('start_date', '>=', $filters['date']));
            })
            ->when($filters['budget_max'] > 0, function (Builder $query) use ($filters): void {
                $query->where(function (Builder $budgetQuery) use ($filters): void {
                    $budgetQuery->whereNull('price_from')
                        ->orWhere('price_from', '<=', $filters['budget_max']);
                });
            })
            ->with(['departures' => fn ($query) => $query->whereDate('start_date', '>=', $today)->orderBy('start_date')->orderBy('id')])
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $voyages->setCollection($this->formatCatalogueRows($voyages->getCollection()));
        $detailMap = $this->buildModalDetailMap($voyages->getCollection());

        return view('agent.catalogue', [
            'voyages' => $voyages,
            'filters' => $filters,
            'destinationOptions' => $destinationOptions,
            'canCreateReservation' => $user->can('reservations.create'),
            'agentCatalogueDetailMap' => $detailMap,
        ]);
    }

    protected function formatCatalogueRows(Collection $voyages): Collection
    {
        $wpPosts = WpPost::query()
            ->whereIn('ID', $voyages->pluck('wp_post_id')->filter()->unique()->values()->all())
            ->get()
            ->keyBy('ID');

        return $voyages->map(function (Voyage $voyage) use ($wpPosts): Voyage {
            $wpPost = $wpPosts->get((int) ($voyage->wp_post_id ?? 0));
            $publicPrice = $this->resolvePublicPrice($voyage, $wpPost);
            $nextDeparture = $voyage->departures->first();

            if ($nextDeparture) {
                $resolved = $this->reservationPricing->resolveUnitPrice($voyage, $nextDeparture, null);
                $unitPrice = (float) ($resolved['unit_price'] ?? 0);
                if ($unitPrice > 0) {
                    $publicPrice = $unitPrice;
                }
            }

            $voyage->agent_catalogue_image_url = $this->resolveCatalogImageUrl($voyage);
            $voyage->agent_catalogue_price_value = $publicPrice;
            $voyage->agent_catalogue_price_label = $publicPrice > 0
                ? number_format($publicPrice, 0, ',', ' ').' '.$voyage->currency_symbol
                : 'Prix sur demande';
            $voyage->agent_catalogue_next_departure = $nextDeparture;
            $voyage->agent_catalogue_future_departures_count = $voyage->departures->count();

            return $voyage;
        });
    }

    protected function resolvePublicPrice(Voyage $voyage, ?WpPost $wpPost): float
    {
        if ($wpPost) {
            foreach (['sale_price', 'base_price', 'min_price'] as $key) {
                $value = (float) ($wpPost->getMeta($key) ?? 0);
                if ($value > 0) {
                    return $value;
                }
            }
        }

        return (float) ($voyage->price_from ?? 0);
    }

    protected function buildModalDetailMap(Collection $voyages): array
    {
        return $voyages->mapWithKeys(function (Voyage $voyage): array {
            $code = 'agent-voyage-'.$voyage->id;
            $departures = $voyage->departures->values()->map(function ($departure) use ($voyage): array {
                $capacity = (int) (($departure->capacity ?? null) ?: ($departure->total_capacity ?? 0));
                $remaining = (int) ($departure->available_capacity ?? 0);
                $fillPct = $capacity > 0 ? min(100, max(0, (int) round((($capacity - $remaining) / $capacity) * 100))) : 0;
                $dateLabel = trim(($departure->start_date ? $departure->start_date->format('d/m/Y') : '—')
                    .($departure->end_date ? ' - '.$departure->end_date->format('d/m/Y') : ''));

                return [
                    'travel_date_id' => (string) ($departure->wp_travel_date_id ?: $departure->id),
                    'date_label' => $dateLabel,
                    'capacity' => $capacity,
                    'remaining' => $remaining,
                    'fill_pct' => $fillPct,
                    'is_past' => $departure->start_date ? $departure->start_date->isPast() : false,
                    'status_key' => $remaining <= 0 ? 'full' : ($remaining <= 5 ? 'almost_full' : 'available'),
                    'status_label' => $remaining <= 0 ? 'Complet' : 'Disponible',
                    'pax' => [
                        'validee' => 0,
                        'en_cours' => 0,
                        'annulee' => 0,
                    ],
                    'reservations' => [
                        'total' => 0,
                        'validee' => 0,
                        'en_cours' => 0,
                        'annulee' => 0,
                    ],
                    'routes' => [],
                    'unit_price' => (float) ($voyage->agent_catalogue_price_value ?? 0),
                ];
            })->all();

            return [
                $code => [
                    'kind' => 'package',
                    'title' => (string) $voyage->name,
                    'destination' => (string) ($voyage->destination ?? ''),
                    'duration' => (string) ($voyage->duration_text ?? ''),
                    'wp_post_id' => (int) ($voyage->wp_post_id ?? 0),
                    'laravel_voyage_id' => (int) $voyage->id,
                    'post_status_label' => 'Actif',
                    'prices' => [
                        'adult_label' => (string) ($voyage->agent_catalogue_price_label ?? 'Prix sur demande'),
                        'currency' => (string) ($voyage->currency ?? 'MAD'),
                    ],
                    'travel_dates' => collect($departures)->map(fn (array $departure): array => [
                        'date_label' => $departure['date_label'],
                        'is_past' => $departure['is_past'],
                    ])->all(),
                    'departures' => $departures,
                    'routes' => [],
                    'stats' => [
                        'validee' => 0,
                        'en_cours' => 0,
                        'annulee' => 0,
                    ],
                ],
            ];
        })->all();
    }

    private function resolveCatalogImageUrl(Voyage $voyage): ?string
    {
        $wpTourId = (int) ($voyage->wp_post_id ?? 0);
        if ($wpTourId > 0) {
            $fromWp = $this->resolveWpTourFirstImageUrl($wpTourId);
            if ($fromWp) {
                return $fromWp;
            }
        }

        return $voyage->featured_image_url;
    }

    private function resolveWpTourFirstImageUrl(int $wpTourId): ?string
    {
        $metas = WpPostMeta::query()
            ->where('post_id', $wpTourId)
            ->whereIn('meta_key', ['_tour_hero_image_id', '_tour_hero_gallery_ids', '_thumbnail_id'])
            ->pluck('meta_value', 'meta_key');

        $attachmentIds = [];
        foreach (['_tour_hero_image_id', '_thumbnail_id'] as $key) {
            if (! empty($metas[$key])) {
                $attachmentIds[] = (int) $metas[$key];
            }
        }

        if (! empty($metas['_tour_hero_gallery_ids'])) {
            foreach (explode(',', (string) $metas['_tour_hero_gallery_ids']) as $id) {
                $attachmentIds[] = (int) trim($id);
            }
        }

        foreach (array_values(array_unique(array_filter($attachmentIds))) as $attachmentId) {
            $url = WpHeroImageService::publicUrlForAttachmentId((int) $attachmentId);
            if ($url) {
                return $url;
            }
        }

        return null;
    }
}
