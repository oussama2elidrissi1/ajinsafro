<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use Illuminate\Http\JsonResponse;

class PublicToursListController extends Controller
{
    /**
     * GET /api/public/tours
     * 
     * List all active tours for WordPress import.
     */
    public function index(): JsonResponse
    {
        $voyages = Voyage::where('status', 'actif')
            ->with(['images'])
            ->orderBy('created_at', 'desc')
            ->get();

        $tours = $voyages->map(function ($voyage) {
            return [
                'id' => $voyage->id,
                'wp_post_id' => $voyage->wp_post_id,
                'slug' => $voyage->slug,
                'name' => $voyage->name,
                'description' => $voyage->description,
                'accroche' => $voyage->accroche,
                'destination' => $voyage->destination,
                'duration_text' => $voyage->duration_text,
                'price_from' => $voyage->price_from,
                'old_price' => $voyage->old_price,
                'currency' => $voyage->currency,
                'status' => $voyage->status,
                'featured_image' => $voyage->featured_image_url,
                'gallery' => $voyage->images->map(fn($img) => $img->url)->toArray(),
                'created_at' => $voyage->created_at->toIso8601String(),
                'updated_at' => $voyage->updated_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $tours->count(),
            'data' => $tours,
        ]);
    }
}
