<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWpTourRequest;
use App\Http\Requests\UpdateWpTourRequest;
use App\Models\Wp\WpPost;
use App\Services\Wp\WpTourRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VoyageController extends Controller
{
    protected WpTourRepository $repository;

    public function __construct(WpTourRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display listing of WordPress tours.
     */
    public function index(): View
    {
        // Récupérer les tours WordPress avec pagination
        $tours = WpPost::tours()
            ->orderByDesc('ID')
            ->paginate(20);

        // Charger les metas pour affichage
        $tours->getCollection()->transform(function ($tour) {
            $tour->adult_price = $tour->getMeta('adult_price');
            $tour->duration_day = $tour->getMeta('duration_day');
            $tour->address = $tour->getMeta('address');
            $tour->child_price = $tour->getMeta('child_price');
            return $tour;
        });

        return view('admin.circuits.voyages.index', compact('tours'));
    }

    /**
     * Show single tour (détail).
     */
    public function show(int $id): View
    {
        $tour = $this->repository->getTourWithMetas($id);
        return view('admin.circuits.voyages.show', compact('tour'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return view('admin.circuits.voyages.create');
    }

    /**
     * Store new tour in WordPress.
     */
    public function store(StoreWpTourRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Générer slug si vide
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Convertir gallery CSV en array
        if (!empty($validated['gallery_ids'])) {
            $validated['gallery_ids'] = array_filter(array_map('trim', explode(',', $validated['gallery_ids'])));
        }

        try {
            $tour = $this->repository->createTour($validated);

            return redirect()
                ->route('admin.circuits.voyages.edit', $tour->ID)
                ->with('success', 'Tour créé avec succès dans WordPress ! Visible immédiatement sur ajinsafro.net');
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
        
        // Convertir array gallery en CSV pour le form
        if (is_array($tour['gallery'])) {
            $tour['gallery_csv'] = implode(',', $tour['gallery']);
        } else {
            $tour['gallery_csv'] = $tour['gallery'] ?? '';
        }

        return view('admin.circuits.voyages.edit', compact('tour'));
    }

    /**
     * Update tour in WordPress.
     */
    public function update(UpdateWpTourRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();

        // Convertir gallery CSV en array
        if (!empty($validated['gallery_ids'])) {
            $validated['gallery_ids'] = array_filter(array_map('trim', explode(',', $validated['gallery_ids'])));
        }

        try {
            $this->repository->updateTour($id, $validated);

            return redirect()
                ->route('admin.circuits.voyages.edit', $id)
                ->with('success', 'Tour mis à jour avec succès dans WordPress ! Modifications visibles immédiatement.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Erreur lors de la mise à jour : ' . $e->getMessage()]);
        }
    }

    /**
     * Delete tour from WordPress.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->repository->deleteTour($id);

            return redirect()
                ->route('admin.circuits.voyages.index')
                ->with('success', 'Tour supprimé avec succès de WordPress !');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
        }
    }
}
