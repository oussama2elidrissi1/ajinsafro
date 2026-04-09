<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VoyageTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LaravelVoyageThemeController extends Controller
{
    /**
     * Liste des thèmes (JSON pour AJAX, même usage que circuits/taxonomy-terms).
     */
    public function index(): JsonResponse
    {
        $themes = VoyageTheme::query()
            ->ordered()
            ->get(['id', 'name', 'slug', 'is_active', 'sort_order']);

        return response()->json([
            'success' => true,
            'themes' => $themes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
        ]);

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug($data['name']);
        }
        $slug = $this->uniqueSlug($slug);

        $theme = VoyageTheme::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => (int) (VoyageTheme::query()->max('sort_order') ?? 0) + 10,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thème créé.',
            'theme' => $theme->only(['id', 'name', 'slug', 'is_active', 'sort_order']),
        ]);
    }

    public function update(Request $request, VoyageTheme $voyageTheme): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
        ]);

        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug($data['name']);
        }
        $slug = $this->uniqueSlug($slug, $voyageTheme->id);

        $voyageTheme->update([
            'name' => $data['name'],
            'slug' => $slug,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thème mis à jour.',
            'theme' => $voyageTheme->fresh()->only(['id', 'name', 'slug', 'is_active', 'sort_order']),
        ]);
    }

    public function destroy(VoyageTheme $voyageTheme): JsonResponse
    {
        $voyageTheme->delete();

        return response()->json([
            'success' => true,
            'message' => 'Thème supprimé.',
        ]);
    }

    private function uniqueSlug(string $baseSlug, ?int $exceptId = null): string
    {
        $slug = $baseSlug !== '' ? $baseSlug : 'theme';
        $candidate = $slug;
        $n = 2;
        while (VoyageTheme::query()
            ->where('slug', $candidate)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists()) {
            $candidate = $slug.'-'.$n;
            $n++;
        }

        return $candidate;
    }
}
