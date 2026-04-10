<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessReferenceValue;
use App\Services\BusinessReferentialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessReferenceController extends Controller
{
    public function index(): View
    {
        $groups = [];
        foreach (BusinessReferentialService::GROUP_LABELS as $key => $label) {
            $count = BusinessReferenceValue::query()->forGroup($key)->count();
            $groups[] = ['key' => $key, 'label' => $label, 'count' => $count];
        }

        return view('admin.settings.business_references.index', compact('groups'));
    }

    public function showGroup(string $groupKey): View|RedirectResponse
    {
        if (! isset(BusinessReferentialService::GROUP_LABELS[$groupKey])) {
            abort(404);
        }

        $label = BusinessReferentialService::GROUP_LABELS[$groupKey];
        $items = BusinessReferenceValue::query()
            ->forGroup($groupKey)
            ->ordered()
            ->get();

        return view('admin.settings.business_references.group', compact('groupKey', 'label', 'items'));
    }

    public function store(Request $request, string $groupKey): RedirectResponse
    {
        if (! isset(BusinessReferentialService::GROUP_LABELS[$groupKey])) {
            abort(404);
        }

        $rules = [
            'label' => 'required|string|max:500',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'meta_json' => 'nullable|string',
        ];

        if ($groupKey === 'payment_methods') {
            $rules['meta_json'] = 'required|string';
        } else {
            $rules['value'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('business_reference_values', 'value')->where(fn ($q) => $q->where('group_key', $groupKey)),
            ];
        }

        $validated = $request->validate($rules);

        $meta = null;
        if (! empty($validated['meta_json'])) {
            try {
                $meta = json_decode($validated['meta_json'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                return back()->withErrors(['meta_json' => 'JSON meta invalide.'])->withInput();
            }
        }

        if ($groupKey === 'payment_methods') {
            if (! is_array($meta) || empty($meta['meta_key'])) {
                return back()->withErrors(['meta_json' => 'Le champ meta_key est obligatoire pour les moyens de paiement.'])->withInput();
            }
            $value = trim((string) $meta['meta_key']);
        } else {
            $value = trim((string) $validated['value']);
        }

        BusinessReferenceValue::query()->create([
            'group_key' => $groupKey,
            'value' => $value,
            'label' => $validated['label'],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'meta' => $meta,
        ]);

        return redirect()
            ->route('admin.settings.referentiels-metier.group', $groupKey)
            ->with('success', 'Valeur ajoutée.');
    }

    public function update(Request $request, string $groupKey, BusinessReferenceValue $item): RedirectResponse
    {
        $this->assertGroupItem($groupKey, $item);

        $validated = $request->validate([
            'label' => 'required|string|max:500',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'meta_json' => 'nullable|string',
        ]);

        $meta = $item->meta;
        if (array_key_exists('meta_json', $validated)) {
            if ($validated['meta_json'] === null || $validated['meta_json'] === '') {
                $meta = null;
            } else {
                try {
                    $meta = json_decode($validated['meta_json'], true, 512, JSON_THROW_ON_ERROR);
                } catch (\Throwable) {
                    return back()->withErrors(['meta_json' => 'JSON meta invalide.'])->withInput();
                }
            }
        }

        $item->fill([
            'label' => $validated['label'],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? $item->sort_order),
            'meta' => $meta,
        ]);
        $item->save();

        return redirect()
            ->route('admin.settings.referentiels-metier.group', $groupKey)
            ->with('success', 'Valeur mise à jour.');
    }

    public function destroy(string $groupKey, BusinessReferenceValue $item): RedirectResponse
    {
        $this->assertGroupItem($groupKey, $item);
        $item->delete();

        return redirect()
            ->route('admin.settings.referentiels-metier.group', $groupKey)
            ->with('success', 'Valeur supprimée.');
    }

    public function importLegacy(): RedirectResponse
    {
        $n = BusinessReferentialService::importFromLegacySettingJson();

        return redirect()
            ->route('admin.settings.referentiels-metier')
            ->with('success', 'Import depuis l’ancien JSON : '.$n.' entrée(s) fusionnée(s).');
    }

    private function assertGroupItem(string $groupKey, BusinessReferenceValue $item): void
    {
        if ($item->group_key !== $groupKey) {
            abort(404);
        }
    }
}
