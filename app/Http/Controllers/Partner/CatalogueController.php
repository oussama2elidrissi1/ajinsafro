<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use App\Models\Wp\WpPost;
use App\Services\AdminWpTourCatalogQuery;
use App\Services\Reservations\ReservationPricingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    public function __construct(
        protected ReservationPricingService $reservationPricing,
    ) {}

    /**
     * Voyages que le partenaire a le droit de vendre.
     * Si aucun accès restreint (partner_voyage_access vide) = tous les voyages actifs.
     */
    public function index(Request $request): View
    {
        $partner = $request->user()->partner;
        $voyageIds = $partner->voyageAccess()->pluck('voyages.id')->toArray();

        // Must match what the back-office "Circuits / voyages" makes available for booking:
        // active Laravel voyage linked to a published WP tour, with departures.
        $publishedIds = AdminWpTourCatalogQuery::publishedWpTourIds();
        $query = Voyage::query()
            ->where('status', 'actif')
            ->whereNotNull('wp_post_id')
            ->where('wp_post_id', '>', 0)
            ->whereHas('departures')
            ->when($publishedIds !== [], fn ($q) => $q->whereIn('wp_post_id', $publishedIds))
            ->when($publishedIds === [], fn ($q) => $q->whereRaw('1 = 0'))
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%test%']);
        if (! empty($voyageIds)) {
            $query->whereIn('id', $voyageIds);
        }
        $query->with(['departures' => function ($q) {
            $q->orderBy('start_date')->orderBy('id');
        }]);
        $query->orderBy('name');
        $voyages = $query->paginate(20)->withQueryString();

        // Préparer les valeurs formatées pour l'affichage catalogue (prix & commission)
        /** @var \Illuminate\Support\Collection<int,\App\Models\Voyage> $voyageCollection */
        $voyageCollection = $voyages->getCollection();
        $formatted = $this->formatCataloguePricing($voyageCollection);
        $voyages->setCollection($formatted);

        $voyagesCollection = $voyages->getCollection();

        // Préparer une structure "workspace-like" : voyages + 3 prochains départs (stats + prix unitaire).
        $today = Carbon::today();
        $workspaceRows = $voyagesCollection->map(function (Voyage $voyage) use ($today) {
            $departures = $voyage->departures
                ->filter(fn ($d) => $d && $d->start_date)
                ->sortBy(fn ($d) => $d->start_date)
                ->values();

            $futureDepartures = $departures
                ->filter(fn ($d) => $d->start_date && $d->start_date->gte($today))
                ->values();

            $visible = $futureDepartures->take(3)->map(function ($d) use ($voyage) {
                $resolved = $this->reservationPricing->resolveUnitPrice($voyage, $d, null);
                return [
                    'id' => (int) $d->id,
                    'label' => ($d->start_date ? $d->start_date->format('d/m/Y') : '-')
                        .($d->end_date ? ' -> '.$d->end_date->format('d/m/Y') : ''),
                    'date_iso' => $d->start_date ? $d->start_date->format('Y-m-d') : null,
                    'available_capacity' => (int) ($d->available_capacity ?? 0),
                    'capacity' => (int) ($d->capacity ?? 0),
                    'status' => (string) ($d->status ?? 'active'),
                    'unit_price' => (float) ($resolved['unit_price'] ?? 0),
                    'travel_date_id' => $d->wp_travel_date_id,
                    'routes' => [
                        'reserve' => route('partner.reservations.create', array_filter([
                            'voyage_id' => (int) $voyage->id,
                            'departure_id' => (int) $d->id,
                            'travel_date_id' => $d->wp_travel_date_id ?: null,
                        ], fn ($v) => $v !== null && $v !== '')),
                    ],
                ];
            })->values()->all();

            return [
                'type' => 'package',
                'voyage_id' => (int) $voyage->id,
                'wp_post_id' => (int) ($voyage->wp_post_id ?? 0),
                'name' => (string) $voyage->name,
                'voyage_destination' => (string) ($voyage->destination ?? ''),
                'image_url' => $voyage->featured_image_url,
                'price_label' => $voyage->catalog_public_price_display ?? '—',
                'commission_label' => $voyage->catalog_commission_display ?? '—',
                'price_value' => (float) preg_replace('/[^\d.]/', '', (string) ($voyage->catalog_public_price_display ?? '0')),
                'modal_detail' => [
                    'departures' => $visible,
                ],
                'ws_has_future' => $futureDepartures->isNotEmpty(),
                'ws_future_count' => $futureDepartures->count(),
            ];
        })->values();

        $destinationOptions = $workspaceRows
            ->map(fn (array $row) => trim((string) ($row['voyage_destination'] ?? '')))
            ->filter(fn (string $d) => $d !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        return view('partner.v2.catalogue.workspace', [
            'voyages' => $voyages,
            'workspaceRows' => $workspaceRows,
            'destinationOptions' => $destinationOptions,
        ]);
    }

    /**
     * Prépare les champs d'affichage pour le catalogue partenaire :
     * - catalog_public_price_display
     * - catalog_commission_display
     */
    protected function formatCataloguePricing(Collection $voyages): Collection
    {
        // Charger les WpPost associés (prix & commissions dans les métas WP)
        $wpIds = $voyages->pluck('wp_post_id')->filter()->unique()->values()->all();
        $wpPosts = [];

        if (! empty($wpIds)) {
            /** @var \Illuminate\Support\Collection<int,\App\Models\Wp\WpPost> $posts */
            $posts = WpPost::query()->whereIn('ID', $wpIds)->get();
            $wpPosts = $posts->keyBy('ID')->all();
        }

        return $voyages->map(function (Voyage $voyage) use ($wpPosts) {
            $wpPostId = (int) ($voyage->wp_post_id ?? 0);
            $wpPost = $wpPostId && isset($wpPosts[$wpPostId]) ? $wpPosts[$wpPostId] : null;

            $minPrice = null;
            $basePrice = null;
            $salePrice = null;
            $adultCommission = null;

            if ($wpPost) {
                // Les champs proviennent de l'onglet \"Prix & Paiement\" du CRUD admin (stockés en metas WP)
                $minPrice = (float) ($wpPost->getMeta('min_price') ?? 0);
                $basePrice = (float) ($wpPost->getMeta('base_price') ?? 0);
                $salePrice = (float) ($wpPost->getMeta('sale_price') ?? 0);
                $adultCommission = (float) ($wpPost->getMeta('commission_adulte') ?? 0);
            }

            // Prix public : sale_price > base_price > min_price
            $public = null;
            if ($salePrice > 0) {
                $public = $salePrice;
            } elseif ($basePrice > 0) {
                $public = $basePrice;
            } elseif ($minPrice > 0) {
                $public = $minPrice;
            }

            if ($public !== null) {
                $voyage->catalog_public_price_display = number_format($public, 0, ',', ' ') . ' ' . $voyage->currency_symbol;
            } else {
                $voyage->catalog_public_price_display = '—';
            }

            // Commission : commission_adulte (MAD)
            if ($adultCommission !== null && $adultCommission > 0) {
                $voyage->catalog_commission_display = number_format($adultCommission, 0, ',', ' ') . ' DH';
            } else {
                $voyage->catalog_commission_display = '—';
            }

            return $voyage;
        });
    }
}
