<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use App\Models\VoyageImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Str;

class VoyageController extends Controller
{
    public function index(): View
    {
        $voyages = Voyage::withCount('programDays')->orderBy('updated_at', 'desc')->get();
        return view('admin.circuits.voyages.index', compact('voyages'));
    }

    public function show(Voyage $voyage): View
    {
        $voyage->load(['programDays', 'departures']);
        return view('admin.circuits.voyages.show', compact('voyage'));
    }

    public function create(): View
    {
        return view('admin.circuits.voyages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateVoyage($request);
        $baseSlug = Str::slug($validated['name']);
        $validated['slug'] = $baseSlug;
        $n = 1;
        while (Voyage::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $n++;
        }

        unset($validated['featured_image'], $validated['gallery_images']);

        DB::beginTransaction();
        try {
            $voyage = Voyage::create($validated);

            if ($request->hasFile('featured_image')) {
                $path = $request->file('featured_image')->store('travels/featured', 'public');
                $voyage->update(['featured_image' => $path]);
            }

            if ($request->hasFile('gallery_images')) {
                $sortOrder = 0;
                foreach ($request->file('gallery_images') as $file) {
                    $path = $file->store('travels/gallery', 'public');
                    VoyageImage::create([
                        'voyage_id' => $voyage->id,
                        'path' => $path,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('admin.circuits.voyages.edit', $voyage)
            ->with('success', 'Voyage créé. Vous pouvez maintenant ajouter le programme et les départs.');
    }

    public function edit(Voyage $voyage): View
    {
        $voyage->load(['programDays', 'departures', 'images']);
        return view('admin.circuits.voyages.edit', compact('voyage'));
    }

    public function update(Request $request, Voyage $voyage): RedirectResponse
    {
        $validated = $this->validateVoyage($request, $voyage);
        $baseSlug = Str::slug($validated['name']);
        $validated['slug'] = $baseSlug;
        $n = 1;
        while (Voyage::where('slug', $validated['slug'])->where('id', '!=', $voyage->id)->exists()) {
            $validated['slug'] = $baseSlug . '-' . $n++;
        }

        $removeGalleryIds = $request->input('remove_gallery_ids', []);
        unset($validated['featured_image'], $validated['gallery_images']);

        DB::beginTransaction();
        try {
            foreach ((array) $removeGalleryIds as $id) {
                $img = VoyageImage::where('voyage_id', $voyage->id)->find($id);
                if ($img) {
                    Storage::disk('public')->delete($img->path);
                    $img->delete();
                }
            }

            if ($request->hasFile('featured_image')) {
                if ($voyage->featured_image) {
                    Storage::disk('public')->delete($voyage->featured_image);
                }
                $path = $request->file('featured_image')->store('travels/featured', 'public');
                $validated['featured_image'] = $path;
            }

            $voyage->update($validated);

            if ($request->hasFile('gallery_images')) {
                $maxOrder = $voyage->images()->max('sort_order') ?? -1;
                $sortOrder = $maxOrder + 1;
                foreach ($request->file('gallery_images') as $file) {
                    $path = $file->store('travels/gallery', 'public');
                    VoyageImage::create([
                        'voyage_id' => $voyage->id,
                        'path' => $path,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('admin.circuits.voyages.edit', $voyage)
            ->with('success', 'Voyage mis à jour.');
    }

    public function destroy(Voyage $voyage): RedirectResponse
    {
        $voyage->delete();
        return redirect()->route('admin.circuits.voyages.index')
            ->with('success', 'Voyage supprimé.');
    }

    /**
     * Remove a single gallery image (AJAX or form submit).
     */
    public function destroyImage(Request $request, Voyage $voyage, VoyageImage $voyageImage): RedirectResponse
    {
        if ($voyageImage->voyage_id !== $voyage->id) {
            abort(404);
        }
        Storage::disk('public')->delete($voyageImage->path);
        $voyageImage->delete();
        return redirect()->route('admin.circuits.voyages.edit', $voyage)
            ->with('success', 'Image supprimée.');
    }

    private function validateVoyage(Request $request, ?Voyage $voyage = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'accroche' => 'nullable|string',
            'destination' => 'nullable|string|max:255',
            'duration_text' => 'nullable|string|max:255',
            'price_from' => 'nullable|integer|min:0',
            'old_price' => 'nullable|integer|min:0',
            'currency' => 'nullable|string|max:10',
            'min_people' => 'nullable|integer|min:1',
            'departure_policy' => 'nullable|string',
            'status' => 'nullable|string|in:actif,inactif',
        ]);

        if ($request->hasFile('featured_image')) {
            $featuredFile = $request->file('featured_image');
            if (!$featuredFile->isValid()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'featured_image' => 'The featured image upload failed.'
                ]);
            }
            $maxSizeKB = 5120;
            if ($featuredFile->getSize() > $maxSizeKB * 1024) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'featured_image' => "The featured image may not be greater than {$maxSizeKB} kilobytes."
                ]);
            }
            $allowedMimes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array(strtolower($featuredFile->getClientOriginalExtension()), $allowedMimes)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'featured_image' => 'The featured image must be a file of type: jpeg, png, gif, webp.'
                ]);
            }
        }

        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $idx => $file) {
                if (!$file->isValid()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "gallery_images.{$idx}" => 'Gallery image upload failed.'
                    ]);
                }
                $maxSizeKB = 5120;
                if ($file->getSize() > $maxSizeKB * 1024) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "gallery_images.{$idx}" => "Gallery image may not be greater than {$maxSizeKB} kilobytes."
                    ]);
                }
                $allowedMimes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedMimes)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "gallery_images.{$idx}" => 'Gallery image must be a file of type: jpeg, png, gif, webp.'
                    ]);
                }
            }
        }

        return $validated;
    }
}
