<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Wp\WpTourRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WpTourController extends Controller
{
    protected WpTourRepository $repository;

    public function __construct(WpTourRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display listing of WordPress tours.
     */
    public function index(Request $request): View
    {
        $tours = $this->repository->listTours(20);

        // Eager load metas for display
        $tours->getCollection()->transform(function ($tour) {
            $tour->adult_price = $tour->getMeta('adult_price');
            $tour->duration_day = $tour->getMeta('duration_day');
            $tour->address = $tour->getMeta('address');
            return $tour;
        });

        return view('admin.wp-tours.index', compact('tours'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return view('admin.wp-tours.create');
    }

    /**
     * Store new tour.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'destination' => 'nullable|string|max:255',
            'duration_text' => 'nullable|string|max:100',
            'adult_price' => 'nullable|numeric|min:0',
            'child_price' => 'nullable|numeric|min:0',
            'min_price' => 'nullable|numeric|min:0',
            'min_people' => 'nullable|integer|min:1',
            'thumbnail_id' => 'nullable|integer',
            'gallery_ids' => 'nullable|string',
            'post_status' => 'nullable|in:publish,draft,pending',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Convert gallery CSV to array if needed
        if (!empty($validated['gallery_ids'])) {
            $validated['gallery_ids'] = explode(',', str_replace(' ', '', $validated['gallery_ids']));
        }

        try {
            $tour = $this->repository->createTour($validated);

            return redirect()
                ->route('admin.wp-tours.edit', $tour->ID)
                ->with('success', 'Tour créé avec succès dans WordPress !');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Erreur lors de la création : ' . $e->getMessage()]);
        }
    }

    /**
     * Show edit form.
     */
    public function edit(int $id): View
    {
        $tour = $this->repository->getTourWithMetas($id);

        return view('admin.wp-tours.edit', compact('tour'));
    }

    /**
     * Update tour.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'destination' => 'nullable|string|max:255',
            'duration_text' => 'nullable|string|max:100',
            'adult_price' => 'nullable|numeric|min:0',
            'child_price' => 'nullable|numeric|min:0',
            'min_price' => 'nullable|numeric|min:0',
            'min_people' => 'nullable|integer|min:1',
            'thumbnail_id' => 'nullable|integer',
            'gallery_ids' => 'nullable|string',
            'post_status' => 'nullable|in:publish,draft,pending',
        ]);

        // Convert gallery CSV to array if needed
        if (!empty($validated['gallery_ids'])) {
            $validated['gallery_ids'] = explode(',', str_replace(' ', '', $validated['gallery_ids']));
        }

        try {
            $this->repository->updateTour($id, $validated);

            return redirect()
                ->route('admin.wp-tours.edit', $id)
                ->with('success', 'Tour mis à jour avec succès dans WordPress !');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Erreur lors de la mise à jour : ' . $e->getMessage()]);
        }
    }

    /**
     * Delete tour.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->repository->deleteTour($id);

            return redirect()
                ->route('admin.wp-tours.index')
                ->with('success', 'Tour supprimé avec succès de WordPress !');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
        }
    }
}
