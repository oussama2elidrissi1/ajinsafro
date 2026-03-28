<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\PartnerCommissionRule;
use App\Models\Voyage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerCommissionRuleController extends Controller
{
    public function index(Request $request): View
    {
        $query = PartnerCommissionRule::query()->with(['partner', 'voyage']);
        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        $rules = $query->orderByRaw('partner_id IS NULL')->orderBy('voyage_id')->paginate(20)->withQueryString();
        $partners = Partner::where('status', Partner::STATUS_VALIDATED)->orderBy('raison_sociale')->get(['id', 'raison_sociale', 'nom_commercial']);
        return view('admin.partner-commission-rules.index', compact('rules', 'partners'));
    }

    public function create(): View
    {
        $partners = Partner::where('status', Partner::STATUS_VALIDATED)->orderBy('raison_sociale')->get(['id', 'raison_sociale', 'nom_commercial']);
        $voyages = Voyage::orderBy('name')->get(['id', 'name']);
        return view('admin.partner-commission-rules.create', compact('partners', 'voyages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'partner_id' => ['nullable', 'exists:partners,id'],
            'voyage_id' => ['nullable', 'exists:voyages,id'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_volume' => ['nullable', 'integer', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        PartnerCommissionRule::create($data);
        return redirect()->route('admin.partner-commission-rules.index')->with('success', 'Règle de commission créée.');
    }

    public function edit(PartnerCommissionRule $partnerCommissionRule): View
    {
        $rule = $partnerCommissionRule;
        $partners = Partner::where('status', Partner::STATUS_VALIDATED)->orderBy('raison_sociale')->get(['id', 'raison_sociale', 'nom_commercial']);
        $voyages = Voyage::orderBy('name')->get(['id', 'name']);
        return view('admin.partner-commission-rules.edit', compact('rule', 'partners', 'voyages'));
    }

    public function update(Request $request, PartnerCommissionRule $partnerCommissionRule): RedirectResponse
    {
        $data = $request->validate([
            'partner_id' => ['nullable', 'exists:partners,id'],
            'voyage_id' => ['nullable', 'exists:voyages,id'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_volume' => ['nullable', 'integer', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $partnerCommissionRule->update($data);
        return redirect()->route('admin.partner-commission-rules.index')->with('success', 'Règle mise à jour.');
    }

    public function destroy(PartnerCommissionRule $partnerCommissionRule): RedirectResponse
    {
        $partnerCommissionRule->delete();
        return redirect()->route('admin.partner-commission-rules.index')->with('success', 'Règle supprimée.');
    }
}
