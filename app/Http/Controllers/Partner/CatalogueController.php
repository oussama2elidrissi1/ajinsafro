<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use App\Models\Wp\WpPost;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    /**
     * Voyages que le partenaire a le droit de vendre.
     * Si aucun accès restreint (partner_voyage_access vide) = tous les voyages actifs.
     */
    public function index(Request $request): View
    {
        $partner = $request->user()->partner;
        $voyageIds = $partner->voyageAccess()->pluck('voyages.id')->toArray();

        $query = Voyage::query()->where(function ($q) {
            $q->where('status', 'actif')->orWhere('status', 'publish');
        });
        if (! empty($voyageIds)) {
            $query->whereIn('id', $voyageIds);
        }
        $query->orderBy('name');
        $voyages = $query->paginate(20)->withQueryString();

        // Préparer les valeurs formatées pour l'affichage catalogue (prix & commission)
        /** @var \Illuminate\Support\Collection<int,\App\Models\Voyage> $voyageCollection */
        $voyageCollection = $voyages->getCollection();
        $formatted = $this->formatCataloguePricing($voyageCollection);
        $voyages->setCollection($formatted);

        return view('partner.catalogue.index', [
            'voyages' => $voyages,
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
