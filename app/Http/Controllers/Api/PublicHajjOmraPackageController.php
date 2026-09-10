<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HajjOmraPackage;
use App\Services\HajjOmra\HajjOmraCommercialPresenter;
use Illuminate\Http\JsonResponse;

class PublicHajjOmraPackageController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = HajjOmraPackage::query()
            ->with([
                'images',
                'departures' => fn ($query) => $query->orderBy('departure_date'),
                'roomPrices',
                ...HajjOmraCommercialPresenter::RELATIONS,
            ])
            ->whereIn('status', [
                HajjOmraPackage::STATUS_PUBLISHED,
                HajjOmraPackage::STATUS_FULL,
            ])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('start_date')
            ->get();

        $publicBase = rtrim((string) config('app.public_url', config('app.url', 'https://ajinsafro.net')), '/');

        $items = $packages->map(function (HajjOmraPackage $package) use ($publicBase) {
            $nextDeparture = $package->resolveUpcomingDeparture();

            return array_merge([
                'id' => $package->id,
                'title' => $package->title,
                'slug' => $package->slug,
                'type' => $package->type,
                'type_label' => $package->type_label,
                'status' => $package->status,
                'status_label' => $package->status_label,
                'short_description' => $package->short_description,
                'destination' => $package->destination,
                'departure_city' => $package->departure_city,
                'duration_days' => $package->duration_days,
                'duration_nights' => $package->duration_nights,
                'duration_label' => $package->duration_label,
                'departure_date' => $nextDeparture?->departure_date?->toDateString() ?: optional($package->start_date)->toDateString(),
                'return_date' => $nextDeparture?->return_date?->toDateString() ?: optional($package->return_date)->toDateString(),
                'price_from' => $package->price_from_value,
                'currency' => $package->currency,
                'is_featured' => $package->is_featured,
                'remaining_places' => $package->remaining_places,
                'main_image_url' => $package->main_image_url,
                'makkah_hotel' => $package->makkah_hotel,
                'madinah_hotel' => $package->madinah_hotel,
                'detail_url' => $publicBase.'/hajj-omra/'.$package->slug,
                'request_url' => $publicBase.'/hajj-omra/'.$package->slug.'#reservation-form',
                'gallery' => $package->images->map(fn ($image) => $image->image_url)->filter()->values()->all(),
            ], app(HajjOmraCommercialPresenter::class)->package($package));
        })->values();

        $departureCities = $packages
            ->pluck('departure_city')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'types' => HajjOmraPackage::typeOptions(),
                'departure_cities' => $departureCities,
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $package = HajjOmraPackage::query()
            ->with(['images', ...HajjOmraCommercialPresenter::RELATIONS])
            ->where('slug', $slug)
            ->whereIn('status', [
                HajjOmraPackage::STATUS_PUBLISHED,
                HajjOmraPackage::STATUS_FULL,
                HajjOmraPackage::STATUS_EXPIRED,
            ])
            ->firstOrFail();

        $publicBase = rtrim((string) config('app.public_url', config('app.url', 'https://ajinsafro.net')), '/');

        return response()->json([
            'data' => array_merge([
                'id' => $package->id,
                'title' => $package->title,
                'slug' => $package->slug,
                'type' => $package->type,
                'type_label' => $package->type_label,
                'status' => $package->status,
                'status_label' => $package->status_label,
                'main_image_url' => $package->main_image_url,
                'gallery' => collect([$package->main_image_url])
                    ->merge($package->images->map(fn ($image) => $image->image_url))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
                'short_description' => $package->short_description,
                'description' => $package->description,
                'destination' => $package->destination,
                'departure_city' => $package->departure_city,
                'duration_days' => $package->duration_days,
                'duration_nights' => $package->duration_nights,
                'duration_label' => $package->duration_label,
                'start_date' => optional($package->start_date)->toDateString(),
                'return_date' => optional($package->return_date)->toDateString(),
                'adult_price' => $package->adult_price !== null ? (float) $package->adult_price : null,
                'child_price' => $package->child_price !== null ? (float) $package->child_price : null,
                'baby_price' => $package->baby_price !== null ? (float) $package->baby_price : null,
                'price_from' => $package->price_from_value,
                'currency' => $package->currency,
                'available_places' => $package->available_places,
                'reserved_places' => $package->reserved_places,
                'remaining_places' => $package->remaining_places,
                'makkah_hotel' => $package->makkah_hotel,
                'makkah_haram_distance' => $package->makkah_haram_distance,
                'madinah_hotel' => $package->madinah_hotel,
                'madinah_haram_distance' => $package->madinah_haram_distance,
                'room_type' => $package->room_type,
                'transport_included' => $package->transport_included,
                'visa_included' => $package->visa_included,
                'guidance_included' => $package->guidance_included,
                'meal_plan' => $package->meal_plan,
                'meal_plan_label' => HajjOmraPackage::mealPlanOptions()[$package->meal_plan] ?? null,
                'included_items' => $package->included_items ?? [],
                'excluded_items' => $package->excluded_items ?? [],
                'booking_conditions' => $package->booking_conditions,
                'required_documents' => $package->required_documents,
                'meta_title' => $package->meta_title,
                'meta_description' => $package->meta_description,
                'detail_url' => $publicBase.'/hajj-omra/'.$package->slug,
                'booking_endpoint' => rtrim((string) config('app.url'), '/').'/api/public/hajj-omra/packages/'.$package->slug.'/booking-requests',
            ], app(HajjOmraCommercialPresenter::class)->package($package)),
        ]);
    }
}
