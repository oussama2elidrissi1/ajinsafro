<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EconomicOffer;
use App\Models\EconomicOfferDeparture;
use App\Models\EconomicOfferPrice;
use App\Models\EconomicOfferRequest;
use App\Services\WpCatalogCacheInvalidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EconomicOfferController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'offer_type' => trim((string) $request->input('offer_type', '')),
            'destination' => trim((string) $request->input('destination', '')),
            'departure_city' => trim((string) $request->input('departure_city', '')),
            'budget' => trim((string) $request->input('budget', '')),
            'status' => trim((string) $request->input('status', '')),
            'departure_date' => trim((string) $request->input('departure_date', '')),
            'featured' => trim((string) $request->input('featured', '')),
        ];

        $query = EconomicOffer::query()
            ->with(['departures', 'prices'])
            ->withCount('requests');

        if ($filters['q'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder
                    ->where('title', 'like', '%'.$filters['q'].'%')
                    ->orWhere('destination', 'like', '%'.$filters['q'].'%')
                    ->orWhere('departure_city', 'like', '%'.$filters['q'].'%')
                    ->orWhere('short_description', 'like', '%'.$filters['q'].'%')
                    ->orWhere('description', 'like', '%'.$filters['q'].'%')
                    ->orWhere('internal_reference', 'like', '%'.$filters['q'].'%');
            });
        }

        if ($filters['offer_type'] !== '' && in_array($filters['offer_type'], EconomicOffer::TYPES, true)) {
            $query->where('offer_type', $filters['offer_type']);
        }

        if ($filters['destination'] !== '') {
            $query->where('destination', 'like', '%'.$filters['destination'].'%');
        }

        if ($filters['departure_city'] !== '') {
            $query->where('departure_city', 'like', '%'.$filters['departure_city'].'%');
        }

        if ($filters['budget'] !== '' && is_numeric($filters['budget'])) {
            $query->lowBudget((float) $filters['budget']);
        }

        if ($filters['status'] !== '' && in_array($filters['status'], EconomicOffer::STATUSES, true)) {
            $query->where('status', $filters['status']);
        }

        if ($filters['departure_date'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder
                    ->whereDate('departure_date', $filters['departure_date'])
                    ->orWhereHas('departures', fn ($departureQuery) => $departureQuery->whereDate('departure_date', $filters['departure_date']));
            });
        }

        if ($filters['featured'] === '1') {
            $query->where('is_featured', true);
        }

        $offers = $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.economic-offers.index', [
            'offers' => $offers,
            'filters' => $filters,
            'totals' => [
                'offers' => (clone $query)->count(),
                'featured' => (clone $query)->where('is_featured', true)->count(),
                'published' => (clone $query)->where('status', EconomicOffer::STATUS_PUBLISHED)->count(),
                'requests' => EconomicOfferRequest::query()->count(),
            ],
            'typeOptions' => EconomicOffer::typeOptions(),
            'statusOptions' => EconomicOffer::statusOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.economic-offers.create', $this->formViewData(new EconomicOffer([
            'currency' => 'DH',
            'status' => EconomicOffer::STATUS_DRAFT,
            'category' => EconomicOffer::CATEGORY_ECONOMIC,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $offer = DB::transaction(function () use ($request, $validated) {
            $offer = new EconomicOffer();
            $this->persistOffer($offer, $request, $validated);

            return $offer;
        });

        $this->invalidateCaches($offer->slug);

        return redirect()
            ->route('admin.economic-offers.edit', $offer)
            ->with('success', 'Offre economique creee avec succes.');
    }

    public function show(EconomicOffer $economicOffer): View
    {
        $economicOffer->load(['images', 'departures', 'prices', 'requests']);

        return view('admin.economic-offers.show', [
            'offer' => $economicOffer,
        ]);
    }

    public function edit(EconomicOffer $economicOffer): View
    {
        $economicOffer->load(['images', 'departures', 'prices']);

        return view('admin.economic-offers.edit', $this->formViewData($economicOffer));
    }

    public function update(Request $request, EconomicOffer $economicOffer): RedirectResponse
    {
        $validated = $request->validate($this->rules($economicOffer));
        $oldSlug = $economicOffer->slug;

        DB::transaction(function () use ($request, $validated, $economicOffer) {
            $this->persistOffer($economicOffer, $request, $validated);
        });

        $this->invalidateCaches($oldSlug, $economicOffer->slug);

        return redirect()
            ->route('admin.economic-offers.edit', $economicOffer)
            ->with('success', 'Offre economique mise a jour.');
    }

    public function destroy(EconomicOffer $economicOffer): RedirectResponse
    {
        $slug = $economicOffer->slug;
        $this->deleteOfferFiles($economicOffer);
        $economicOffer->delete();

        $this->invalidateCaches($slug);

        return redirect()
            ->route('admin.economic-offers.index')
            ->with('success', 'Offre economique supprimee.');
    }

    private function formViewData(EconomicOffer $offer): array
    {
        return [
            'offer' => $offer,
            'typeOptions' => EconomicOffer::typeOptions(),
            'categoryOptions' => EconomicOffer::categoryOptions(),
            'statusOptions' => EconomicOffer::statusOptions(),
            'availabilityOptions' => EconomicOffer::availabilityOptions(),
            'priceTypeOptions' => EconomicOffer::priceTypeOptions(),
            'mealPlanOptions' => EconomicOffer::mealPlanOptions(),
            'departureStatusOptions' => EconomicOfferDeparture::statusOptions(),
        ];
    }

    private function persistOffer(EconomicOffer $offer, Request $request, array $validated): void
    {
        $offer->fill($this->extractOfferData($request, $validated, $offer));
        $offer->save();

        $this->syncGallery($offer, $request);
        $this->syncPrices($offer, $validated['prices'] ?? []);
        $this->syncDepartures($offer, $validated['departures'] ?? []);
    }

    private function extractOfferData(Request $request, array $validated, EconomicOffer $offer): array
    {
        $data = [
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? null,
            'internal_reference' => $validated['internal_reference'] ?? null,
            'offer_type' => $validated['offer_type'],
            'category' => $validated['category'],
            'status' => $validated['status'],
            'main_image' => $offer->main_image,
            'fallback_image' => $offer->fallback_image,
            'video_url' => $validated['video_url'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'price_from' => $validated['price_from'] ?? null,
            'old_price' => $validated['old_price'] ?? null,
            'currency' => $validated['currency'],
            'price_type' => $validated['price_type'] ?? null,
            'deposit_amount' => $validated['deposit_amount'] ?? null,
            'payment_conditions' => $validated['payment_conditions'] ?? null,
            'included_items' => $this->normalizeTextareaList($validated['included_items_text'] ?? null),
            'excluded_items' => $this->normalizeTextareaList($validated['excluded_items_text'] ?? null),
            'departure_date' => $validated['departure_date'] ?? null,
            'return_date' => $validated['return_date'] ?? null,
            'duration_days' => $validated['duration_days'] ?? null,
            'duration_nights' => $validated['duration_nights'] ?? null,
            'total_places' => $validated['total_places'] ?? 0,
            'available_places' => $validated['available_places'] ?? 0,
            'reserved_places' => $validated['reserved_places'] ?? 0,
            'departure_city' => $validated['departure_city'] ?? null,
            'destination' => $validated['destination'] ?? null,
            'country' => $validated['country'] ?? null,
            'arrival_city' => $validated['arrival_city'] ?? null,
            'address_zone' => $validated['address_zone'] ?? null,
            'key_distance' => $validated['key_distance'] ?? null,
            'transport_included' => $request->boolean('transport_included'),
            'flight_included' => $request->boolean('flight_included'),
            'hotel_included' => $request->boolean('hotel_included'),
            'meals_included' => $request->boolean('meals_included'),
            'guide_included' => $request->boolean('guide_included'),
            'insurance_included' => $request->boolean('insurance_included'),
            'transfer_included' => $request->boolean('transfer_included'),
            'accommodation_type' => $validated['accommodation_type'] ?? null,
            'hotel_name' => $validated['hotel_name'] ?? null,
            'hotel_category' => $validated['hotel_category'] ?? null,
            'room_type' => $validated['room_type'] ?? null,
            'meal_plan' => $validated['meal_plan'] ?? null,
            'program_summary' => $validated['program_summary'] ?? null,
            'cancellation_conditions' => $validated['cancellation_conditions'] ?? null,
            'required_documents' => $validated['required_documents'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'seo_image' => $offer->seo_image,
            'seo_keywords' => $this->normalizeTextareaList($validated['seo_keywords_text'] ?? null),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_featured' => $request->boolean('is_featured'),
        ];

        foreach ([
            'main_image_file' => 'main_image',
            'fallback_image_file' => 'fallback_image',
            'seo_image_file' => 'seo_image',
        ] as $field => $attribute) {
            if ($request->boolean('remove_'.$attribute) && $offer->{$attribute}) {
                Storage::disk('public')->delete($offer->{$attribute});
                $data[$attribute] = null;
            }

            if ($request->hasFile($field)) {
                if ($offer->{$attribute}) {
                    Storage::disk('public')->delete($offer->{$attribute});
                }

                $data[$attribute] = $request->file($field)->store('economic-offers/'.$attribute, 'public');
            }
        }

        return $data;
    }

    private function syncGallery(EconomicOffer $offer, Request $request): void
    {
        $files = $request->file('gallery_images', []);
        $replace = $request->boolean('replace_gallery');

        if ($files === [] && ! $replace) {
            return;
        }

        foreach ($offer->images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        $offer->images()->delete();

        foreach ($files as $index => $file) {
            if (! $file) {
                continue;
            }

            $offer->images()->create([
                'image_path' => $file->store('economic-offers/gallery', 'public'),
                'alt_text' => $offer->title,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function syncPrices(EconomicOffer $offer, array $rows): void
    {
        $offer->prices()->delete();

        foreach (array_values($rows) as $index => $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $price = $row['price'] ?? null;

            if ($label === '' || $price === null || $price === '') {
                continue;
            }

            $offer->prices()->create([
                'label' => $label,
                'type' => trim((string) ($row['type'] ?? '')) ?: null,
                'price' => $price,
                'old_price' => $row['old_price'] !== '' ? ($row['old_price'] ?? null) : null,
                'stock' => (int) ($row['stock'] ?? 0),
                'condition' => trim((string) ($row['condition'] ?? '')) ?: null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function syncDepartures(EconomicOffer $offer, array $rows): void
    {
        $offer->departures()->delete();

        foreach (array_values($rows) as $index => $row) {
            $departureDate = $row['departure_date'] ?? null;
            if (! $departureDate) {
                continue;
            }

            $offer->departures()->create([
                'departure_date' => $departureDate,
                'return_date' => $row['return_date'] ?? null,
                'price_from' => $row['price_from'] !== '' ? ($row['price_from'] ?? null) : null,
                'total_places' => (int) ($row['total_places'] ?? 0),
                'available_places' => (int) ($row['available_places'] ?? 0),
                'reserved_places' => (int) ($row['reserved_places'] ?? 0),
                'status' => $row['status'] ?? EconomicOfferDeparture::STATUS_PUBLISHED,
                'internal_notes' => $row['internal_notes'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function deleteOfferFiles(EconomicOffer $offer): void
    {
        $offer->loadMissing(['images']);

        foreach (['main_image', 'fallback_image', 'seo_image'] as $attribute) {
            if ($offer->{$attribute}) {
                Storage::disk('public')->delete($offer->{$attribute});
            }
        }

        foreach ($offer->images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        }
    }

    private function invalidateCaches(string ...$slugs): void
    {
        $keys = ['ajth_economic_offers_v1'];

        foreach ($slugs as $slug) {
            if ($slug !== '') {
                $keys[] = 'ajth_economic_offer_'.$slug.'_v1';
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

    private function rules(?EconomicOffer $offer = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('economic_offers', 'slug')->ignore($offer?->id)],
            'internal_reference' => ['nullable', 'string', 'max:120'],
            'offer_type' => ['required', Rule::in(EconomicOffer::TYPES)],
            'category' => ['required', Rule::in(EconomicOffer::CATEGORIES)],
            'status' => ['required', Rule::in(EconomicOffer::STATUSES)],
            'main_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'fallback_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'seo_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'replace_gallery' => ['nullable', 'boolean'],
            'short_description' => ['nullable', 'string', 'max:1200'],
            'description' => ['nullable', 'string'],
            'price_from' => ['nullable', 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'price_type' => ['nullable', Rule::in(array_keys(EconomicOffer::priceTypeOptions()))],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_conditions' => ['nullable', 'string'],
            'included_items_text' => ['nullable', 'string'],
            'excluded_items_text' => ['nullable', 'string'],
            'departure_date' => ['nullable', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:departure_date'],
            'duration_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'duration_nights' => ['nullable', 'integer', 'min:0', 'max:365'],
            'total_places' => ['nullable', 'integer', 'min:0'],
            'available_places' => ['nullable', 'integer', 'min:0'],
            'reserved_places' => ['nullable', 'integer', 'min:0'],
            'departure_city' => ['nullable', 'string', 'max:150'],
            'destination' => ['nullable', 'string', 'max:150'],
            'country' => ['nullable', 'string', 'max:120'],
            'arrival_city' => ['nullable', 'string', 'max:150'],
            'address_zone' => ['nullable', 'string', 'max:255'],
            'key_distance' => ['nullable', 'string', 'max:120'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'transport_included' => ['nullable', 'boolean'],
            'flight_included' => ['nullable', 'boolean'],
            'hotel_included' => ['nullable', 'boolean'],
            'meals_included' => ['nullable', 'boolean'],
            'guide_included' => ['nullable', 'boolean'],
            'insurance_included' => ['nullable', 'boolean'],
            'transfer_included' => ['nullable', 'boolean'],
            'accommodation_type' => ['nullable', 'string', 'max:120'],
            'hotel_name' => ['nullable', 'string', 'max:255'],
            'hotel_category' => ['nullable', 'string', 'max:120'],
            'room_type' => ['nullable', 'string', 'max:120'],
            'meal_plan' => ['nullable', Rule::in(array_keys(EconomicOffer::mealPlanOptions()))],
            'program_summary' => ['nullable', 'string'],
            'cancellation_conditions' => ['nullable', 'string'],
            'required_documents' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords_text' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_featured' => ['nullable', 'boolean'],
            'remove_main_image' => ['nullable', 'boolean'],
            'remove_fallback_image' => ['nullable', 'boolean'],
            'remove_seo_image' => ['nullable', 'boolean'],

            'prices' => ['nullable', 'array'],
            'prices.*.label' => ['nullable', 'string', 'max:120'],
            'prices.*.type' => ['nullable', 'string', 'max:120'],
            'prices.*.price' => ['nullable', 'numeric', 'min:0'],
            'prices.*.old_price' => ['nullable', 'numeric', 'min:0'],
            'prices.*.stock' => ['nullable', 'integer', 'min:0'],
            'prices.*.condition' => ['nullable', 'string', 'max:255'],

            'departures' => ['nullable', 'array'],
            'departures.*.departure_date' => ['nullable', 'date'],
            'departures.*.return_date' => ['nullable', 'date'],
            'departures.*.price_from' => ['nullable', 'numeric', 'min:0'],
            'departures.*.total_places' => ['nullable', 'integer', 'min:0'],
            'departures.*.available_places' => ['nullable', 'integer', 'min:0'],
            'departures.*.reserved_places' => ['nullable', 'integer', 'min:0'],
            'departures.*.status' => ['nullable', Rule::in(EconomicOfferDeparture::STATUSES)],
            'departures.*.internal_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
