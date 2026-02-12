<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wp\WpPost;
use App\Services\Wp\ProgramJsonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Programme (aj_program_json) : GET programme, POST save.
 * Utilisé par l’onglet Programme en autosave ou save global.
 */
class ProgramApiController extends Controller
{
    protected ProgramJsonService $programService;

    public function __construct(ProgramJsonService $programService)
    {
        $this->programService = $programService;
    }

    /**
     * GET /admin/circuits/voyages/{id}/program
     * Retourne le programme JSON (program_days avec items).
     */
    public function show(int $id): JsonResponse
    {
        $post = WpPost::tours()->where('ID', $id)->first();
        if (!$post) {
            return response()->json(['error' => 'Tour not found'], 404);
        }
        $program = $this->programService->getProgram($id);
        return response()->json($program);
    }

    /**
     * POST /admin/circuits/voyages/{id}/program
     * Enregistre le programme (body JSON: { program_days: [...] }).
     */
    public function save(Request $request, int $id): JsonResponse
    {
        $post = WpPost::tours()->where('ID', $id)->first();
        if (!$post) {
            return response()->json(['error' => 'Tour not found'], 404);
        }
        $program = $request->all();
        if (!isset($program['program_days']) || !is_array($program['program_days'])) {
            $program = ['program_days' => is_array($program) ? $program : []];
        }
        try {
            $this->programService->saveProgram($id, $program);
            $saved = $this->programService->getProgram($id);
            return response()->json([
                'success' => true,
                'message' => __('Programme enregistré.', 'default'),
                'program' => $saved,
                'days_count' => count($saved['program_days'] ?? []),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
