<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChargeType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChargeTypeController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('charge_types.manage'), 403);

        return view('admin.settings.charge-types.index', [
            'chargeTypes' => ChargeType::query()->orderBy('sort_order')->orderBy('name')->paginate(30),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('charge_types.manage'), 403);

        $data = $this->validatePayload($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        ChargeType::query()->create($data);

        return back()->with('success', 'Type de charge ajoute.');
    }

    public function update(Request $request, ChargeType $chargeType): RedirectResponse
    {
        abort_unless($request->user()->can('charge_types.manage'), 403);

        $data = $this->validatePayload($request);
        $data['slug'] = $this->uniqueSlug($data['name'], $chargeType->id);
        $data['is_active'] = $request->boolean('is_active');

        $chargeType->update($data);

        return back()->with('success', 'Type de charge mis a jour.');
    }

    public function destroy(Request $request, ChargeType $chargeType): RedirectResponse
    {
        abort_unless($request->user()->can('charge_types.manage'), 403);
        $chargeType->delete();

        return back()->with('success', 'Type de charge supprime.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'type-charge';
        $slug = $base;
        $i = 2;

        while (ChargeType::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
