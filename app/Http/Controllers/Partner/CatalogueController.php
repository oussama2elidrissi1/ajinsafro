<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerCommissionRule;
use App\Models\Voyage;
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

        $ruleByVoyage = [];
        foreach ($partner->commissionRules()->where('is_active', true)->get() as $rule) {
            $key = $rule->voyage_id ?? 'global';
            if (!isset($ruleByVoyage[$key])) {
                $ruleByVoyage[$key] = $rule;
            }
        }
        $globalRule = $ruleByVoyage['global'] ?? null;

        // Préparer les valeurs formatées pour l'affichage catalogue
        /** @var \Illuminate\Support\Collection<int,\App\Models\Voyage> $voyageCollection */
        $voyageCollection = $voyages->getCollection();
        $formatted = $this->formatCataloguePricing($voyageCollection, $ruleByVoyage, $globalRule);
        $voyages->setCollection($formatted);

        return view('partner.catalogue.index', [
            'voyages' => $voyages,
            'ruleByVoyage' => $ruleByVoyage,
            'globalRule' => $globalRule,
        ]);
    }

    /**
     * Prépare les champs d'affichage pour le catalogue partenaire :
     * - catalog_public_price_display
     * - catalog_commission_display
     */
    protected function formatCataloguePricing(Collection $voyages, array $ruleByVoyage, ?PartnerCommissionRule $globalRule): Collection
    {
        return $voyages->map(function (Voyage $voyage) use ($ruleByVoyage, $globalRule) {
            $priceFrom = $voyage->price_from;

            // Prix public à partir de price_from (stocké en cents)
            if ($priceFrom && $priceFrom > 0) {
                $publicAmount = $priceFrom / 100;
                $voyage->catalog_public_price_display = number_format($publicAmount, 0, ',', ' ') . ' ' . $voyage->currency_symbol;
            } else {
                $voyage->catalog_public_price_display = '—';
            }

            // Règle de commission applicable : spécifique au voyage, sinon globale
            /** @var PartnerCommissionRule|null $rule */
            $rule = $ruleByVoyage[$voyage->id] ?? $globalRule;

            if (! $rule) {
                $voyage->catalog_commission_display = '—';
                return $voyage;
            }

            // Si aucun prix public, on ne peut pas calculer de montant fixe lié au prix
            if (! $priceFrom || $priceFrom <= 0) {
                if ($rule->type === PartnerCommissionRule::TYPE_PERCENT) {
                    $voyage->catalog_commission_display = rtrim(rtrim(number_format((float) $rule->value, 2, ',', ' '), '0'), ',') . ' %';
                } else {
                    $voyage->catalog_commission_display = number_format((float) $rule->value, 0, ',', ' ') . ' DH';
                }
                return $voyage;
            }

            if ($rule->type === PartnerCommissionRule::TYPE_FIXED) {
                // Commission fixe en montant
                $voyage->catalog_commission_display = number_format((float) $rule->value, 0, ',', ' ') . ' DH';
            } else {
                // Commission en pourcentage : affichage en pourcentage (source de vérité)
                $voyage->catalog_commission_display = rtrim(rtrim(number_format((float) $rule->value, 2, ',', ' '), '0'), ',') . ' %';
            }

            return $voyage;
        });
    }
}
