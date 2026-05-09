<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EconomicOffer;
use Illuminate\Http\JsonResponse;

class PublicEconomicOfferController extends Controller
{
    public function index(): JsonResponse
    {
        $offers = EconomicOffer::query()
            ->with([
                'images',
                'departures' => fn ($query) => $query->orderBy('departure_date'),
                'prices',
            ])
            ->whereIn('status', [
                EconomicOffer::STATUS_PUBLISHED,
                EconomicOffer::STATUS_FULL,
                EconomicOffer::STATUS_EXPIRED,
            ])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('departure_date')
            ->get();

        $publicBase = rtrim((string) config('app.public_url', config('app.url', 'https://ajinsafro.net')), '/');

        $items = $offers->map(function (EconomicOffer $offer) use ($publicBase) {
            $nextDeparture = $offer->resolveUpcomingDeparture();

            return [
                'id' => $offer->id,
                'title' => $offer->title,
                'slug' => $offer->slug,
                'internal_reference' => $offer->internal_reference,
                'offer_type' => $offer->offer_type,
                'type_label' => $offer->type_label,
                'category' => $offer->category,
                'category_label' => $offer->category_label,
                'status' => $offer->status,
                'status_label' => $offer->status_label,
                'availability_status' => $offer->availability_status,
                'availability_label' => $offer->availability_label,
                'short_description' => $offer->short_description,
                'destination' => $offer->destination,
                'country' => $offer->country,
                'departure_city' => $offer->departure_city,
                'arrival_city' => $offer->arrival_city,
                'duration_days' => $offer->duration_days,
                'duration_nights' => $offer->duration_nights,
                'duration_label' => $offer->duration_label,
                'departure_date' => $nextDeparture?->departure_date?->toDateString() ?: optional($offer->departure_date)->toDateString(),
                'return_date' => $nextDeparture?->return_date?->toDateString() ?: optional($offer->return_date)->toDateString(),
                'price_from' => $offer->price_from_value,
                'old_price' => $offer->old_price !== null ? (float) $offer->old_price : null,
                'currency' => $offer->currency,
                'price_type' => $offer->price_type,
                'is_featured' => $offer->is_featured,
                'is_promoted' => $offer->is_promoted,
                'remaining_places' => $offer->remaining_places,
                'main_image_url' => $offer->main_image_url ?: $offer->fallback_image_url,
                'hotel_name' => $offer->hotel_name,
                'detail_url' => $publicBase.'/formule-economique/'.$offer->slug,
                'request_url' => $publicBase.'/formule-economique/'.$offer->slug.'#reservation-form',
                'gallery' => $offer->images->map(fn ($image) => $image->image_url)->filter()->values()->all(),
                'departures' => $offer->departures->map(fn ($departure) => [
                    'id' => $departure->id,
                    'departure_date' => optional($departure->departure_date)->toDateString(),
                    'return_date' => optional($departure->return_date)->toDateString(),
                    'status' => $departure->status,
                    'status_label' => $departure->status_label,
                    'total_places' => $departure->total_places,
                    'available_places' => $departure->available_places,
                    'reserved_places' => $departure->reserved_places,
                    'remaining_places' => $departure->remaining_places,
                    'price_from' => $departure->price_from !== null ? (float) $departure->price_from : null,
                ])->values()->all(),
            ];
        })->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'types' => EconomicOffer::typeOptions(),
                'departure_cities' => $offers->pluck('departure_city')->filter()->unique()->sort()->values()->all(),
                'destinations' => $offers->pluck('destination')->filter()->unique()->sort()->values()->all(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $offer = EconomicOffer::query()
            ->with(['images', 'departures', 'prices'])
            ->where('slug', $slug)
            ->whereIn('status', [
                EconomicOffer::STATUS_PUBLISHED,
                EconomicOffer::STATUS_FULL,
                EconomicOffer::STATUS_EXPIRED,
            ])
            ->firstOrFail();

        $publicBase = rtrim((string) config('app.public_url', config('app.url', 'https://ajinsafro.net')), '/');

        return response()->json([
            'data' => [
                'id' => $offer->id,
                'title' => $offer->title,
                'slug' => $offer->slug,
                'internal_reference' => $offer->internal_reference,
                'offer_type' => $offer->offer_type,
                'type_label' => $offer->type_label,
                'category' => $offer->category,
                'category_label' => $offer->category_label,
                'status' => $offer->status,
                'status_label' => $offer->status_label,
                'availability_status' => $offer->availability_status,
                'availability_label' => $offer->availability_label,
                'main_image_url' => $offer->main_image_url ?: $offer->fallback_image_url,
                'fallback_image_url' => $offer->fallback_image_url,
                'gallery' => collect([$offer->main_image_url, $offer->fallback_image_url])
                    ->merge($offer->images->map(fn ($image) => $image->image_url))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
                'short_description' => $offer->short_description,
                'description' => $offer->description,
                'destination' => $offer->destination,
                'country' => $offer->country,
                'departure_city' => $offer->departure_city,
                'arrival_city' => $offer->arrival_city,
                'address_zone' => $offer->address_zone,
                'key_distance' => $offer->key_distance,
                'duration_days' => $offer->duration_days,
                'duration_nights' => $offer->duration_nights,
                'duration_label' => $offer->duration_label,
                'departure_date' => optional($offer->departure_date)->toDateString(),
                'return_date' => optional($offer->return_date)->toDateString(),
                'price_from' => $offer->price_from_value,
                'old_price' => $offer->old_price !== null ? (float) $offer->old_price : null,
                'currency' => $offer->currency,
                'price_type' => $offer->price_type,
                'deposit_amount' => $offer->deposit_amount !== null ? (float) $offer->deposit_amount : null,
                'payment_conditions' => $offer->payment_conditions,
                'included_items' => $offer->included_items ?? [],
                'excluded_items' => $offer->excluded_items ?? [],
                'available_places' => $offer->available_places,
                'reserved_places' => $offer->reserved_places,
                'remaining_places' => $offer->remaining_places,
                'transport_included' => $offer->transport_included,
                'flight_included' => $offer->flight_included,
                'hotel_included' => $offer->hotel_included,
                'meals_included' => $offer->meals_included,
                'guide_included' => $offer->guide_included,
                'insurance_included' => $offer->insurance_included,
                'transfer_included' => $offer->transfer_included,
                'accommodation_type' => $offer->accommodation_type,
                'hotel_name' => $offer->hotel_name,
                'hotel_category' => $offer->hotel_category,
                'room_type' => $offer->room_type,
                'meal_plan' => $offer->meal_plan,
                'meal_plan_label' => EconomicOffer::mealPlanOptions()[$offer->meal_plan] ?? null,
                'program_summary' => $offer->program_summary,
                'cancellation_conditions' => $offer->cancellation_conditions,
                'required_documents' => $offer->required_documents,
                'detail_url' => $publicBase.'/formule-economique/'.$offer->slug,
                'booking_endpoint' => rtrim((string) config('app.url'), '/').'/api/public/economic-offers/'.$offer->slug.'/requests',
                'departures' => $offer->departures->map(fn ($departure) => [
                    'id' => $departure->id,
                    'departure_date' => optional($departure->departure_date)->toDateString(),
                    'return_date' => optional($departure->return_date)->toDateString(),
                    'status' => $departure->status,
                    'status_label' => $departure->status_label,
                    'total_places' => $departure->total_places,
                    'available_places' => $departure->available_places,
                    'reserved_places' => $departure->reserved_places,
                    'remaining_places' => $departure->remaining_places,
                    'price_from' => $departure->price_from !== null ? (float) $departure->price_from : null,
                    'internal_notes' => $departure->internal_notes,
                ])->values()->all(),
                'prices' => $offer->prices->map(fn ($price) => [
                    'label' => $price->label,
                    'type' => $price->type,
                    'price' => (float) $price->price,
                    'old_price' => $price->old_price !== null ? (float) $price->old_price : null,
                    'stock' => $price->stock,
                    'condition' => $price->condition,
                ])->values()->all(),
            ],
        ]);
    }
}
