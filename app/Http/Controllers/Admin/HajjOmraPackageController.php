<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HajjOmraPackageRequest;
use App\Models\HajjOmraBookingRequest;
use App\Models\HajjOmraDeparture;
use App\Models\HajjOmraPackage;
use App\Models\HajjOmraPackageHotel;
use App\Models\HajjOmraRoomPrice;
use App\Services\HajjOmra\HajjOmraPackageService;
use App\Services\WpCatalogCacheInvalidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HajjOmraPackageController extends Controller
{
    /** Relations chargees par l'editeur et l'apercu. */
    private const EDITOR_RELATIONS = [
        'images', 'departures', 'roomPrices', 'programDays', 'hotels', 'serviceItems',
    ];

    public function __construct(private readonly HajjOmraPackageService $packages)
    {
    }

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'destination' => trim((string) $request->input('destination', '')),
            'type' => trim((string) $request->input('type', '')),
            'status' => trim((string) $request->input('status', '')),
        ];

        $query = HajjOmraPackage::query()
            ->with(['departures', 'roomPrices'])
            ->withCount('bookingRequests');

        if ($filters['q'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder
                    ->where('title', 'like', '%'.$filters['q'].'%')
                    ->orWhere('title_ar', 'like', '%'.$filters['q'].'%')
                    ->orWhere('destination', 'like', '%'.$filters['q'].'%')
                    ->orWhere('departure_city', 'like', '%'.$filters['q'].'%')
                    ->orWhere('short_description', 'like', '%'.$filters['q'].'%');
            });
        }

        if ($filters['destination'] !== '') {
            $query->where('destination', 'like', '%'.$filters['destination'].'%');
        }

        if ($filters['type'] !== '' && in_array($filters['type'], HajjOmraPackage::TYPES, true)) {
            $query->where('type', $filters['type']);
        }

        if ($filters['status'] !== '' && in_array($filters['status'], HajjOmraPackage::STATUSES, true)) {
            $query->where('status', $filters['status']);
        }

        $packages = $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        $totals = [
            'packages' => (clone $query)->count(),
            'featured' => (clone $query)->where('is_featured', true)->count(),
            'published' => (clone $query)->where('status', HajjOmraPackage::STATUS_PUBLISHED)->count(),
            'requests' => HajjOmraBookingRequest::query()->count(),
        ];

        return view('admin.hajj-omra.index', [
            'packages' => $packages,
            'filters' => $filters,
            'totals' => $totals,
            'typeOptions' => HajjOmraPackage::typeOptions(),
            'statusOptions' => HajjOmraPackage::statusOptions(),
        ]);
    }

    public function create(): View
    {
        $package = new HajjOmraPackage([
            'currency' => 'DH',
            'status' => HajjOmraPackage::STATUS_DRAFT,
            'duration_days' => 10,
            'duration_nights' => 9,
        ]);

        return view('admin.hajj-omra.create', $this->editorPayload($package));
    }

    public function store(HajjOmraPackageRequest $request): RedirectResponse
    {
        $package = $this->packages->save(new HajjOmraPackage, $request->validated());

        // Le programme est pre-rempli des la creation : l'utilisateur n'a pas a cliquer
        // « Ajouter un jour » autant de fois que l'offre dure.
        if ($package->programDays()->doesntExist()) {
            $this->packages->generateProgramDays($package);
        }

        $this->invalidateCaches($package->slug);

        return redirect()
            ->route('admin.hajj-omra.edit', $package)
            ->with('success', 'Offre creee. Le programme a ete pre-rempli selon la duree.');
    }

    public function show(HajjOmraPackage $hajjOmraPackage): View
    {
        $hajjOmraPackage->load(array_merge(self::EDITOR_RELATIONS, ['bookingRequests']));

        return view('admin.hajj-omra.show', [
            'package' => $hajjOmraPackage,
        ]);
    }

    public function edit(HajjOmraPackage $hajjOmraPackage): View
    {
        $hajjOmraPackage->load(self::EDITOR_RELATIONS);

        return view('admin.hajj-omra.edit', $this->editorPayload($hajjOmraPackage));
    }

    public function update(HajjOmraPackageRequest $request, HajjOmraPackage $hajjOmraPackage): RedirectResponse
    {
        $oldSlug = $hajjOmraPackage->slug;

        $this->packages->save($hajjOmraPackage, $request->validated());

        $this->invalidateCaches($oldSlug, $hajjOmraPackage->slug);

        return redirect()
            ->route('admin.hajj-omra.edit', $hajjOmraPackage)
            ->with('success', 'Offre mise a jour.')
            ->with('active_tab', $request->input('active_tab'));
    }

    /**
     * Cree les jours manquants pour couvrir la duree de l'offre.
     *
     * Appele depuis l'onglet Programme : ne remplace jamais un jour deja redige.
     */
    public function generateProgram(Request $request, HajjOmraPackage $hajjOmraPackage): RedirectResponse
    {
        $validated = $request->validate([
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $created = $this->packages->generateProgramDays(
            $hajjOmraPackage,
            $validated['duration_days'] ?? null
        );

        return redirect()
            ->route('admin.hajj-omra.edit', [$hajjOmraPackage, 'tab' => 'programme'])
            ->with('success', $created > 0
                ? $created.' jour(s) ajoute(s) au programme.'
                : 'Le programme couvre deja toute la duree de l\'offre.');
    }

    /**
     * Apercu commercial de l'offre, tel qu'elle se presentera au client.
     */
    public function preview(Request $request, HajjOmraPackage $hajjOmraPackage): View
    {
        $hajjOmraPackage->load(self::EDITOR_RELATIONS);

        $locale = $request->query('locale') === 'ar' ? 'ar' : 'fr';

        return view('admin.hajj-omra.preview', [
            'package' => $hajjOmraPackage,
            'locale' => $locale,
        ]);
    }

    public function destroy(HajjOmraPackage $hajjOmraPackage): RedirectResponse
    {
        $slug = $hajjOmraPackage->slug;

        $this->packages->deleteFiles($hajjOmraPackage);
        $hajjOmraPackage->delete();

        $this->invalidateCaches($slug);

        return redirect()
            ->route('admin.hajj-omra.index')
            ->with('success', 'Offre supprimee.');
    }

    /**
     * Donnees communes aux ecrans de creation et d'edition.
     *
     * @return array<string, mixed>
     */
    private function editorPayload(HajjOmraPackage $package): array
    {
        return [
            'package' => $package,
            'typeOptions' => HajjOmraPackage::typeOptions(),
            'statusOptions' => HajjOmraPackage::statusOptions(),
            'mealPlanOptions' => HajjOmraPackage::mealPlanOptions(),
            'roomTypeOptions' => HajjOmraRoomPrice::roomTypeOptions(),
            'departureStatusOptions' => HajjOmraDeparture::statusOptions(),
            'hotelCityOptions' => HajjOmraPackageHotel::cityOptions(),
            'missingArabic' => $package->exists ? $package->missingArabicFields() : [],
            'activeTab' => request('tab') ?: session('active_tab') ?: 'offre',
        ];
    }

    private function invalidateCaches(string ...$slugs): void
    {
        $keys = ['ajth_hajj_omra_packages_v1'];

        foreach ($slugs as $slug) {
            if ($slug !== '') {
                $keys[] = 'ajth_hajj_omra_package_'.$slug.'_v1';
            }
        }

        WpCatalogCacheInvalidator::invalidate(array_values(array_unique($keys)));
    }
}
