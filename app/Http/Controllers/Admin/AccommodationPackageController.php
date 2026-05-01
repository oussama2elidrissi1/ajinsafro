<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccommodationPackage;
use App\Services\WpCatalogCacheInvalidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccommodationPackageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('accommodations.view');
        $packages = AccommodationPackage::query()
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('admin.accommodation-packages.index', compact('packages'));
    }

    public function create(): View
    {
        $this->authorize('accommodations.view');
        $package = new AccommodationPackage();

        return view('admin.accommodation-packages.create', compact('package'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('accommodations.view');
        $data = $request->validate($this->rules());
        $data['is_featured'] = (bool) ($request->input('is_featured') ?? false);
        $data['is_active'] = (bool) ($request->input('is_active') ?? true);
        $data['includes'] = $this->normalizeIncludes($request->input('includes'));

        AccommodationPackage::create($data);
        WpCatalogCacheInvalidator::invalidate(['ajth_accommodation_packages_v1']);

        return redirect()->route('admin.accommodation-packages.index')->with('success', 'Pack hébergement créé avec succès.');
    }

    public function edit(AccommodationPackage $accommodationPackage): View
    {
        $this->authorize('accommodations.view');

        return view('admin.accommodation-packages.edit', ['package' => $accommodationPackage]);
    }

    public function update(Request $request, AccommodationPackage $accommodationPackage): RedirectResponse
    {
        $this->authorize('accommodations.view');
        $data = $request->validate($this->rules($accommodationPackage->id));
        $data['is_featured'] = (bool) ($request->input('is_featured') ?? false);
        $data['is_active'] = (bool) ($request->input('is_active') ?? true);
        $data['includes'] = $this->normalizeIncludes($request->input('includes'));

        $accommodationPackage->update($data);
        WpCatalogCacheInvalidator::invalidate(['ajth_accommodation_packages_v1']);

        return redirect()->route('admin.accommodation-packages.index')->with('success', 'Pack hébergement mis à jour.');
    }

    public function destroy(AccommodationPackage $accommodationPackage): RedirectResponse
    {
        $this->authorize('accommodations.view');
        $accommodationPackage->delete();
        WpCatalogCacheInvalidator::invalidate(['ajth_accommodation_packages_v1']);

        return redirect()->route('admin.accommodation-packages.index')->with('success', 'Pack hébergement supprimé.');
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:accommodation_packages,slug' . ($ignoreId ? ',' . $ignoreId : '')],
            'country' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'nights' => ['required', 'integer', 'min:0'],
            'pension_type' => ['nullable', 'string', 'max:100'],
            'accommodation_type' => ['nullable', 'string', 'max:100'],
            'badge' => ['nullable', 'string', 'max:100'],
            'short_description' => ['nullable', 'string'],
            'includes' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'price_from' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }

    private function normalizeIncludes(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];

        return array_values(array_filter(array_map('trim', $lines)));
    }
}
