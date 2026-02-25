<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * JSON API for airlines (CRUD) used by the Vols tab modal without page refresh.
 */
class AirlineApiController extends Controller
{
    /**
     * GET /admin/circuits/api/airlines
     */
    public function index(): JsonResponse
    {
        $airlines = Airline::query()->orderBy('name')->get(['id', 'name', 'code_iata', 'logo_path', 'is_active']);

        return response()->json([
            'success' => true,
            'data' => $airlines->map(function ($a) {
                return [
                    'id' => $a->id,
                    'name' => $a->name,
                    'code_iata' => $a->code_iata,
                    'iata_code' => $a->code_iata,
                    'logo_url' => $a->logo_path,
                    'is_active' => (bool) $a->is_active,
                ];
            }),
            'message' => '',
        ]);
    }

    /**
     * POST /admin/circuits/api/airlines
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:airlines,name',
            'code_iata' => 'nullable|string|max:10',
            'iata_code' => 'nullable|string|max:10',
            'logo_url' => 'nullable|string|max:500',
            'logo_path' => 'nullable|string|max:500',
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

        $code = $request->input('code_iata') ?: $request->input('iata_code');
        $logo = $request->input('logo_path') ?: $request->input('logo_url');

        $airline = Airline::create([
            'name' => $request->input('name'),
            'code_iata' => $code ?: null,
            'logo_path' => $logo ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $airline->id,
                'name' => $airline->name,
                'code_iata' => $airline->code_iata,
                'iata_code' => $airline->code_iata,
                'logo_url' => $airline->logo_path,
                'is_active' => (bool) $airline->is_active,
            ],
            'message' => 'Compagnie aérienne créée.',
        ], 201);
    }

    /**
     * PUT /admin/circuits/api/airlines/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $airline = Airline::find($id);
        if (!$airline) {
            return response()->json([
                'success' => false,
                'message' => 'Compagnie introuvable.',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:airlines,name,' . $id,
            'code_iata' => 'nullable|string|max:10',
            'iata_code' => 'nullable|string|max:10',
            'logo_url' => 'nullable|string|max:500',
            'logo_path' => 'nullable|string|max:500',
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

        $code = $request->input('code_iata') ?? $request->input('iata_code');
        $logo = $request->input('logo_path') ?? $request->input('logo_url');

        $airline->update([
            'name' => $request->input('name'),
            'code_iata' => $code ?: null,
            'logo_path' => $logo ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $airline->id,
                'name' => $airline->name,
                'code_iata' => $airline->code_iata,
                'iata_code' => $airline->code_iata,
                'logo_url' => $airline->logo_path,
                'is_active' => (bool) $airline->is_active,
            ],
            'message' => 'Compagnie mise à jour.',
        ]);
    }

    /**
     * DELETE /admin/circuits/api/airlines/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $airline = Airline::find($id);
        if (!$airline) {
            return response()->json([
                'success' => false,
                'message' => 'Compagnie introuvable.',
                'data' => null,
            ], 404);
        }

        $airline->delete();

        return response()->json([
            'success' => true,
            'data' => ['id' => $id],
            'message' => 'Compagnie supprimée.',
        ]);
    }
}
