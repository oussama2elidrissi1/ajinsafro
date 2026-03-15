<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerCommissionRule;
use App\Models\Voyage;
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
        if (!empty($voyageIds)) {
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

        return view('partner.catalogue.index', [
            'voyages' => $voyages,
            'ruleByVoyage' => $ruleByVoyage,
            'globalRule' => $globalRule,
        ]);
    }
}
