<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityOffer;
use App\Services\WpCatalogCacheInvalidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityOfferController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('accommodations.view');
        $offers = ActivityOffer::query()
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('admin.activity-offers.index', compact('offers'));
    }

    public function create(): View
    {
        $this->authorize('accommodations.view');
        $offer = new ActivityOffer();

        return view('admin.activity-offers.create', compact('offer'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('accommodations.view');
        $data = $request->validate($this->rules());
        $data['is_featured'] = (bool) ($request->input('is_featured') ?? false);
        $data['is_active'] = (bool) ($request->input('is_active') ?? true);
        $data['includes'] = $this->normalizeIncludes($request->input('includes'));

        ActivityOffer::create($data);
        WpCatalogCacheInvalidator::invalidate(['ajth_activity_offers_v1', 'ajth_activity_filters_v1']);

        return redirect()->route('admin.activity-offers.index')->with('success', 'Offre activité créée avec succès.');
    }

    public function edit(ActivityOffer $activityOffer): View
    {
        $this->authorize('accommodations.view');

        return view('admin.activity-offers.edit', ['offer' => $activityOffer]);
    }

    public function update(Request $request, ActivityOffer $activityOffer): RedirectResponse
    {
        $this->authorize('accommodations.view');
        $data = $request->validate($this->rules($activityOffer->id));
        $data['is_featured'] = (bool) ($request->input('is_featured') ?? false);
        $data['is_active'] = (bool) ($request->input('is_active') ?? true);
        $data['includes'] = $this->normalizeIncludes($request->input('includes'));

        $activityOffer->update($data);
        WpCatalogCacheInvalidator::invalidate(['ajth_activity_offers_v1', 'ajth_activity_filters_v1']);

        return redirect()->route('admin.activity-offers.index')->with('success', 'Offre activité mise à jour.');
    }

    public function destroy(ActivityOffer $activityOffer): RedirectResponse
    {
        $this->authorize('accommodations.view');
        $activityOffer->delete();
        WpCatalogCacheInvalidator::invalidate(['ajth_activity_offers_v1', 'ajth_activity_filters_v1']);

        return redirect()->route('admin.activity-offers.index')->with('success', 'Offre activité supprimée.');
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:activity_offers,slug' . ($ignoreId ? ',' . $ignoreId : '')],
            'country' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:100'],
            'duration_label' => ['nullable', 'string', 'max:100'],
            'badge' => ['nullable', 'string', 'max:100'],
            'short_description' => ['nullable', 'string'],
            'includes' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'price_from' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'availability_label' => ['nullable', 'string', 'max:100'],
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
