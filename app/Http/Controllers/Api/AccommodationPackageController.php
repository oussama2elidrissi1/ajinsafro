<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccommodationPackage;
use Illuminate\Http\JsonResponse;

class AccommodationPackageController extends Controller
{
    public function index(): JsonResponse
    {
        $items = AccommodationPackage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (AccommodationPackage $package) {
                $publicBase = rtrim((string) config('app.public_url', config('app.url', 'https://ajinsafro.net')), '/');

                return [
                    'id' => $package->id,
                    'title' => $package->title,
                    'slug' => $package->slug,
                    'detail_url' => $publicBase . '/hebergement/pack/' . $package->slug,
                    'country' => $package->country,
                    'city' => $package->city,
                    'duration_days' => $package->duration_days,
                    'nights' => $package->nights,
                    'duration_label' => "{$package->duration_days} jours / {$package->nights} nuits",
                    'pension_type' => $package->pension_type,
                    'accommodation_type' => $package->accommodation_type,
                    'badge' => $package->badge,
                    'short_description' => $package->short_description,
                    'includes' => $package->includes ?? [],
                    'image_url' => $package->image_url,
                    'price_from' => (float) $package->price_from,
                    'currency' => $package->currency,
                    'is_featured' => $package->is_featured,
                    'is_active' => $package->is_active,
                    'sort_order' => $package->sort_order,
                ];
            })
            ->values();

        return response()->json(['data' => $items]);
    }
}
