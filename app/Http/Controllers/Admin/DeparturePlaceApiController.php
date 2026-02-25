<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use App\Models\VoyageDeparturePlace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * JSON API for voyage departure places (Starting from).
 * Used by the Vols tab without page refresh.
 */
class DeparturePlaceApiController extends Controller
{
    private function voyageFromTourId(int $tourId): ?Voyage
    {
        return Voyage::where('wp_post_id', $tourId)->first();
    }

    /**
     * GET /admin/circuits/voyages/{id}/departure-places
     */
    public function index(int $id): JsonResponse
    {
        $voyage = $this->voyageFromTourId($id);
        if (!$voyage) {
            return response()->json([
                'success' => false,
                'message' => 'Voyage introuvable pour ce tour.',
                'data' => [],
            ], 404);
        }

        $places = VoyageDeparturePlace::where('voyage_id', $voyage->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'code', 'is_active', 'sort_order']);

        return response()->json([
            'success' => true,
            'data' => $places,
            'message' => '',
        ]);
    }

    /**
     * POST /admin/circuits/voyages/{id}/departure-places
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $voyage = $this->voyageFromTourId($id);
        if (!$voyage) {
            return response()->json([
                'success' => false,
                'message' => 'Voyage introuvable pour ce tour. Enregistrez le voyage d\'abord.',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
                'data' => null,
            ], 422);
        }

        $maxOrder = VoyageDeparturePlace::where('voyage_id', $voyage->id)->max('sort_order') ?? 0;

        $place = VoyageDeparturePlace::create([
            'voyage_id' => $voyage->id,
            'name' => $request->input('name'),
            'code' => $request->input('code') ?: null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'data' => $place->only(['id', 'name', 'code', 'is_active', 'sort_order']),
            'message' => 'Lieu de départ ajouté.',
        ], 201);
    }

    /**
     * PUT /admin/circuits/departure-places/{placeId}
     */
    public function update(Request $request, int $placeId): JsonResponse
    {
        $place = VoyageDeparturePlace::find($placeId);
        if (!$place) {
            return response()->json([
                'success' => false,
                'message' => 'Lieu introuvable.',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
                'data' => null,
            ], 422);
        }

        $place->update([
            'name' => $request->input('name'),
            'code' => $request->input('code') ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'data' => $place->fresh()->only(['id', 'name', 'code', 'is_active', 'sort_order']),
            'message' => 'Lieu mis à jour.',
        ]);
    }

    /**
     * DELETE /admin/circuits/departure-places/{placeId}
     */
    public function destroy(int $placeId): JsonResponse
    {
        $place = VoyageDeparturePlace::find($placeId);
        if (!$place) {
            return response()->json([
                'success' => false,
                'message' => 'Lieu introuvable.',
                'data' => null,
            ], 404);
        }

        \App\Models\VoyageFlightOption::where('voyage_id', $place->voyage_id)
            ->where('departure_place_id', $placeId)
            ->update(['departure_place_id' => null]);

        $place->delete();

        return response()->json([
            'success' => true,
            'data' => ['id' => $placeId],
            'message' => 'Lieu supprimé.',
        ]);
    }

    /**
     * PATCH /admin/circuits/departure-places/{placeId}/toggle
     */
    public function toggle(int $placeId): JsonResponse
    {
        $place = VoyageDeparturePlace::find($placeId);
        if (!$place) {
            return response()->json([
                'success' => false,
                'message' => 'Lieu introuvable.',
                'data' => null,
            ], 404);
        }

        $place->update(['is_active' => !$place->is_active]);

        return response()->json([
            'success' => true,
            'data' => $place->fresh()->only(['id', 'name', 'code', 'is_active', 'sort_order']),
            'message' => $place->is_active ? 'Lieu activé.' : 'Lieu désactivé.',
        ]);
    }
}
