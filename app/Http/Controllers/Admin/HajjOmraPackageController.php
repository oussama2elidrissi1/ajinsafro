<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HajjOmraBookingRequest;
use App\Models\HajjOmraDeparture;
use App\Models\HajjOmraPackage;
use App\Models\HajjOmraRoomPrice;
use App\Services\WpCatalogCacheInvalidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HajjOmraPackageController extends Controller
{
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
        return view('admin.hajj-omra.create', [
            'package' => new HajjOmraPackage([
                'currency' => 'DH',
                'status' => HajjOmraPackage::STATUS_DRAFT,
            ]),
            'typeOptions' => HajjOmraPackage::typeOptions(),
            'statusOptions' => HajjOmraPackage::statusOptions(),
            'mealPlanOptions' => HajjOmraPackage::mealPlanOptions(),
            'roomTypeOptions' => HajjOmraRoomPrice::roomTypeOptions(),
            'departureStatusOptions' => HajjOmraDeparture::statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $package = DB::transaction(function () use ($request, $validated) {
            $package = new HajjOmraPackage();
            $this->persistPackage($package, $request, $validated);

            return $package;
        });

        $this->invalidateCaches($package->slug);

        return redirect()
            ->route('admin.hajj-omra.edit', $package)
            ->with('success', 'Offre Hajj & Omra creee avec succes.');
    }

    public function show(HajjOmraPackage $hajjOmraPackage): View
    {
        $hajjOmraPackage->load(['images', 'departures', 'roomPrices', 'programDays', 'bookingRequests']);

        return view('admin.hajj-omra.show', [
            'package' => $hajjOmraPackage,
        ]);
    }

    public function edit(HajjOmraPackage $hajjOmraPackage): View
    {
        $hajjOmraPackage->load(['images', 'departures', 'roomPrices', 'programDays']);

        return view('admin.hajj-omra.edit', [
            'package' => $hajjOmraPackage,
            'typeOptions' => HajjOmraPackage::typeOptions(),
            'statusOptions' => HajjOmraPackage::statusOptions(),
            'mealPlanOptions' => HajjOmraPackage::mealPlanOptions(),
            'roomTypeOptions' => HajjOmraRoomPrice::roomTypeOptions(),
            'departureStatusOptions' => HajjOmraDeparture::statusOptions(),
        ]);
    }

    public function update(Request $request, HajjOmraPackage $hajjOmraPackage): RedirectResponse
    {
        $validated = $request->validate($this->rules($hajjOmraPackage));
        $oldSlug = $hajjOmraPackage->slug;

        DB::transaction(function () use ($request, $validated, $hajjOmraPackage) {
            $this->persistPackage($hajjOmraPackage, $request, $validated);
        });

        $this->invalidateCaches($oldSlug, $hajjOmraPackage->slug);

        return redirect()
            ->route('admin.hajj-omra.edit', $hajjOmraPackage)
            ->with('success', 'Offre Hajj & Omra mise a jour.');
    }

    public function destroy(HajjOmraPackage $hajjOmraPackage): RedirectResponse
    {
        $slug = $hajjOmraPackage->slug;
        $this->deletePackageFiles($hajjOmraPackage);
        $hajjOmraPackage->delete();

        $this->invalidateCaches($slug);

        return redirect()
            ->route('admin.hajj-omra.index')
            ->with('success', 'Offre Hajj & Omra supprimee.');
    }

    private function persistPackage(HajjOmraPackage $package, Request $request, array $validated): void
    {
        $data = $this->extractPackageData($request, $validated, $package);
        $package->fill($data);
        $package->save();

        $this->syncGallery($package, $request);
        $this->syncRoomPrices($package, $validated['room_prices'] ?? []);
        $this->syncDepartures($package, $validated['departures'] ?? []);
        $this->syncProgramDays($package, $request, $validated['program_days'] ?? []);
    }

    private function extractPackageData(Request $request, array $validated, HajjOmraPackage $package): array
    {
        $data = [
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? null,
            'type' => $validated['type'],
            'status' => $validated['status'],
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'departure_city' => $validated['departure_city'] ?? null,
            'destination' => $validated['destination'] ?? null,
            'duration_days' => $validated['duration_days'] ?? null,
            'duration_nights' => $validated['duration_nights'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'return_date' => $validated['return_date'] ?? null,
            'adult_price' => $validated['adult_price'] ?? null,
            'child_price' => $validated['child_price'] ?? null,
            'baby_price' => $validated['baby_price'] ?? null,
            'currency' => $validated['currency'],
            'available_places' => $validated['available_places'] ?? 0,
            'reserved_places' => $validated['reserved_places'] ?? 0,
            'makkah_hotel' => $validated['makkah_hotel'] ?? null,
            'makkah_haram_distance' => $validated['makkah_haram_distance'] ?? null,
            'madinah_hotel' => $validated['madinah_hotel'] ?? null,
            'madinah_haram_distance' => $validated['madinah_haram_distance'] ?? null,
            'room_type' => $validated['room_type'] ?? null,
            'transport_included' => $request->boolean('transport_included'),
            'visa_included' => $request->boolean('visa_included'),
            'guidance_included' => $request->boolean('guidance_included'),
            'meal_plan' => $validated['meal_plan'] ?? null,
            'included_items' => $this->normalizeTextareaList($validated['included_items_text'] ?? null),
            'excluded_items' => $this->normalizeTextareaList($validated['excluded_items_text'] ?? null),
            'booking_conditions' => $validated['booking_conditions'] ?? null,
            'required_documents' => $validated['required_documents'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_featured' => $request->boolean('is_featured'),
        ];

        if ($request->boolean('remove_main_image') && $package->main_image) {
            Storage::disk('public')->delete($package->main_image);
            $data['main_image'] = null;
        }

        if ($request->hasFile('main_image_file')) {
            if ($package->main_image) {
                Storage::disk('public')->delete($package->main_image);
            }

            $data['main_image'] = $request->file('main_image_file')->store('hajj-omra/packages', 'public');
        } elseif (! $package->exists) {
            $data['main_image'] = null;
        }

        return $data;
    }

    private function syncGallery(HajjOmraPackage $package, Request $request): void
    {
        $files = $request->file('gallery_images', []);
        $replaceGallery = $request->boolean('replace_gallery');

        if ($files === [] && ! $replaceGallery) {
            return;
        }

        foreach ($package->images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        $package->images()->delete();

        foreach ($files as $index => $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('hajj-omra/gallery', 'public');

            $package->images()->create([
                'image_path' => $path,
                'alt_text' => $package->title,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function syncRoomPrices(HajjOmraPackage $package, array $rows): void
    {
        $package->roomPrices()->delete();

        foreach (array_values($rows) as $index => $row) {
            $roomType = trim((string) ($row['room_type'] ?? ''));
            $price = $row['price'] ?? null;

            if ($roomType === '' || $price === null || $price === '') {
                continue;
            }

            $package->roomPrices()->create([
                'room_type' => $roomType,
                'price' => $price,
                'stock' => (int) ($row['stock'] ?? 0),
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function syncDepartures(HajjOmraPackage $package, array $rows): void
    {
        $package->departures()->delete();

        foreach (array_values($rows) as $index => $row) {
            $departureDate = $row['departure_date'] ?? null;
            if (! $departureDate) {
                continue;
            }

            $package->departures()->create([
                'departure_date' => $departureDate,
                'return_date' => $row['return_date'] ?? null,
                'status' => $row['status'] ?? HajjOmraDeparture::STATUS_PUBLISHED,
                'available_places' => (int) ($row['available_places'] ?? 0),
                'reserved_places' => (int) ($row['reserved_places'] ?? 0),
                'price_from' => $row['price_from'] !== '' ? ($row['price_from'] ?? null) : null,
                'internal_notes' => $row['internal_notes'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function syncProgramDays(HajjOmraPackage $package, Request $request, array $rows): void
    {
        $uploadedImages = $request->file('program_day_images', []);

        $package->programDays()->delete();

        foreach (array_values($rows) as $index => $row) {
            $dayNumber = (int) ($row['day_number'] ?? 0);
            $title = trim((string) ($row['title'] ?? ''));
            $description = trim((string) ($row['description'] ?? ''));

            if ($dayNumber <= 0 && $title === '' && $description === '') {
                continue;
            }

            $imagePath = $row['existing_image_path'] ?? null;
            if (isset($uploadedImages[$index]) && $uploadedImages[$index] !== null) {
                $imagePath = $uploadedImages[$index]->store('hajj-omra/program', 'public');
            }

            $package->programDays()->create([
                'day_number' => max(1, $dayNumber ?: ($index + 1)),
                'title' => $title !== '' ? $title : null,
                'description' => $description !== '' ? $description : null,
                'city' => trim((string) ($row['city'] ?? '')) ?: null,
                'image_path' => $imagePath ?: null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function deletePackageFiles(HajjOmraPackage $package): void
    {
        $package->loadMissing(['images', 'programDays']);

        if ($package->main_image) {
            Storage::disk('public')->delete($package->main_image);
        }

        foreach ($package->images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        foreach ($package->programDays as $programDay) {
            if ($programDay->image_path) {
                Storage::disk('public')->delete($programDay->image_path);
            }
        }
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

    private function normalizeTextareaList(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
        $items = array_values(array_filter(array_map(static fn ($item) => trim((string) $item), $lines)));

        return $items !== [] ? $items : null;
    }

    private function rules(?HajjOmraPackage $package = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('hajj_omra_packages', 'slug')->ignore($package?->id)],
            'type' => ['required', Rule::in(HajjOmraPackage::TYPES)],
            'status' => ['required', Rule::in(HajjOmraPackage::STATUSES)],
            'main_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_main_image' => ['nullable', 'boolean'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'replace_gallery' => ['nullable', 'boolean'],
            'short_description' => ['nullable', 'string', 'max:1200'],
            'description' => ['nullable', 'string'],
            'departure_city' => ['nullable', 'string', 'max:150'],
            'destination' => ['nullable', 'string', 'max:150'],
            'duration_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'duration_nights' => ['nullable', 'integer', 'min:0', 'max:365'],
            'start_date' => ['nullable', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'adult_price' => ['nullable', 'numeric', 'min:0'],
            'child_price' => ['nullable', 'numeric', 'min:0'],
            'baby_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'available_places' => ['nullable', 'integer', 'min:0'],
            'reserved_places' => ['nullable', 'integer', 'min:0'],
            'makkah_hotel' => ['nullable', 'string', 'max:255'],
            'makkah_haram_distance' => ['nullable', 'string', 'max:100'],
            'madinah_hotel' => ['nullable', 'string', 'max:255'],
            'madinah_haram_distance' => ['nullable', 'string', 'max:100'],
            'room_type' => ['nullable', Rule::in(HajjOmraRoomPrice::ROOM_TYPES)],
            'transport_included' => ['nullable', 'boolean'],
            'visa_included' => ['nullable', 'boolean'],
            'guidance_included' => ['nullable', 'boolean'],
            'meal_plan' => ['nullable', Rule::in(array_keys(HajjOmraPackage::mealPlanOptions()))],
            'included_items_text' => ['nullable', 'string'],
            'excluded_items_text' => ['nullable', 'string'],
            'booking_conditions' => ['nullable', 'string'],
            'required_documents' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'is_featured' => ['nullable', 'boolean'],

            'room_prices' => ['nullable', 'array'],
            'room_prices.*.room_type' => ['nullable', Rule::in(HajjOmraRoomPrice::ROOM_TYPES)],
            'room_prices.*.price' => ['nullable', 'numeric', 'min:0'],
            'room_prices.*.stock' => ['nullable', 'integer', 'min:0'],

            'departures' => ['nullable', 'array'],
            'departures.*.departure_date' => ['nullable', 'date'],
            'departures.*.return_date' => ['nullable', 'date'],
            'departures.*.status' => ['nullable', Rule::in(HajjOmraDeparture::STATUSES)],
            'departures.*.available_places' => ['nullable', 'integer', 'min:0'],
            'departures.*.reserved_places' => ['nullable', 'integer', 'min:0'],
            'departures.*.price_from' => ['nullable', 'numeric', 'min:0'],
            'departures.*.internal_notes' => ['nullable', 'string', 'max:2000'],

            'program_days' => ['nullable', 'array'],
            'program_days.*.day_number' => ['nullable', 'integer', 'min:1', 'max:90'],
            'program_days.*.title' => ['nullable', 'string', 'max:255'],
            'program_days.*.description' => ['nullable', 'string'],
            'program_days.*.city' => ['nullable', 'string', 'max:120'],
            'program_days.*.existing_image_path' => ['nullable', 'string', 'max:255'],
            'program_day_images' => ['nullable', 'array'],
            'program_day_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
