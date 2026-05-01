<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityOffer;
use Illuminate\Http\JsonResponse;

class ActivityOfferController extends Controller
{
    public function index(): JsonResponse
    {
        $items = ActivityOffer::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (ActivityOffer $offer) {
                $publicBase = rtrim((string) config('app.public_url', config('app.url', 'https://ajinsafro.net')), '/');

                return [
                    'id' => $offer->id,
                    'title' => $offer->title,
                    'slug' => $offer->slug,
                    'detail_url' => $publicBase . '/activites/activite/' . $offer->slug,
                    'country' => $offer->country,
                    'city' => $offer->city,
                    'category' => $offer->category,
                    'duration_label' => $offer->duration_label,
                    'badge' => $offer->badge,
                    'short_description' => $offer->short_description,
                    'includes' => $offer->includes ?? [],
                    'image_url' => $offer->image_url,
                    'price_from' => (float) $offer->price_from,
                    'currency' => $offer->currency,
                    'availability_label' => $offer->availability_label,
                    'is_featured' => $offer->is_featured,
                    'is_active' => $offer->is_active,
                    'sort_order' => $offer->sort_order,
                ];
            })
            ->values();

        return response()->json(['data' => $items]);
    }

    public function filters(): JsonResponse
    {
        $baseQuery = ActivityOffer::query()->where('is_active', true);

        $countries = (clone $baseQuery)
            ->select('country')
            ->distinct()
            ->orderBy('country')
            ->pluck('country')
            ->values();

        $categories = (clone $baseQuery)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        $cityRows = (clone $baseQuery)
            ->select('country', 'city')
            ->distinct()
            ->orderBy('country')
            ->orderBy('city')
            ->get();

        $citiesByCountry = [];
        foreach ($cityRows as $row) {
            $citiesByCountry[$row->country] ??= [];
            $citiesByCountry[$row->country][] = $row->city;
        }

        return response()->json([
            'data' => [
                'countries' => $countries,
                'cities' => $citiesByCountry,
                'categories' => $categories,
                'min_price' => (float) ((clone $baseQuery)->min('price_from') ?? 0),
                'max_price' => (float) ((clone $baseQuery)->max('price_from') ?? 0),
            ],
        ]);
    }
}
