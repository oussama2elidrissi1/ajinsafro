<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Wp\WpTourRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxonomyTermController extends Controller
{
    public function __construct(
        protected WpTourRepository $tourRepository
    ) {}

    /**
     * List terms for a taxonomy (JSON for AJAX).
     */
    public function index(Request $request): JsonResponse
    {
        $taxonomy = $request->input('taxonomy');
        if (!in_array($taxonomy, WpTourRepository::TOUR_TAXONOMIES, true)) {
            return response()->json(['success' => false, 'message' => 'Taxonomie invalide.'], 400);
        }
        $terms = $this->tourRepository->getTermsByTaxonomy($taxonomy);
        return response()->json(['success' => true, 'terms' => $terms]);
    }

    /**
     * Store a new term.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'taxonomy' => 'required|string|in:' . implode(',', WpTourRepository::TOUR_TAXONOMIES),
            'slug' => 'nullable|string|max:255',
        ]);
        try {
            $term = $this->tourRepository->createTaxonomyTerm(
                $request->input('name'),
                $request->input('taxonomy'),
                (string) ($request->input('slug') ?? '')
            );
            return response()->json([
                'success' => true,
                'message' => 'Catégorie créée.',
                'term' => $term,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la création.'], 500);
        }
    }

    /**
     * Update a term.
     */
    public function update(Request $request, int $termId): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
        ]);
        try {
            $this->tourRepository->updateTaxonomyTerm(
                $termId,
                $request->input('name'),
                (string) ($request->input('slug') ?? '')
            );
            $terms = DB::connection('wp')
                ->table('terms')
                ->where('term_id', $termId)
                ->select('term_id', 'name', 'slug')
                ->first();
            return response()->json([
                'success' => true,
                'message' => 'Catégorie mise à jour.',
                'term' => $terms,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la mise à jour.'], 500);
        }
    }

    /**
     * Delete a term.
     */
    public function destroy(int $termId): JsonResponse
    {
        try {
            $this->tourRepository->deleteTaxonomyTerm($termId);
            return response()->json(['success' => true, 'message' => 'Catégorie supprimée.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression.'], 500);
        }
    }
}
