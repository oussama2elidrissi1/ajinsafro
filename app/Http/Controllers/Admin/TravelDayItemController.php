<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTravelDayItemRequest;
use App\Http\Requests\UpdateTravelDayItemRequest;
use App\Models\TravelDayItem;
use App\Models\Voyage;
use Illuminate\Http\RedirectResponse;

class TravelDayItemController extends Controller
{
    /**
     * Store a new travel day item.
     */
    public function store(StoreTravelDayItemRequest $request, Voyage $voyage): RedirectResponse
    {
        $validated = $request->validated();
        $validated['voyage_id'] = $voyage->id;

        // Parse JSON fields if provided as strings
        if (isset($validated['options_json']) && is_string($validated['options_json'])) {
            $validated['options_json'] = json_decode($validated['options_json'], true);
        }
        if (isset($validated['meta_json']) && is_string($validated['meta_json'])) {
            $validated['meta_json'] = json_decode($validated['meta_json'], true);
        }

        // Set defaults
        if (!isset($validated['start_day'])) {
            $validated['start_day'] = $validated['day_number'];
        }
        if (!isset($validated['included'])) {
            $validated['included'] = true;
        }
        if (!isset($validated['price_delta_per_person'])) {
            $validated['price_delta_per_person'] = 0;
        }
        if (!isset($validated['sort_order'])) {
            // Get max sort_order for this day
            $maxOrder = TravelDayItem::where('voyage_id', $voyage->id)
                ->where('day_number', $validated['day_number'])
                ->max('sort_order') ?? -1;
            $validated['sort_order'] = $maxOrder + 1;
        }

        TravelDayItem::create($validated);

        return redirect()
            ->route('admin.circuits.voyages.edit', $voyage)
            ->with('success', 'Item ajouté au programme avec succès.');
    }

    /**
     * Get item data for editing (AJAX).
     */
    public function edit(Voyage $voyage, TravelDayItem $item)
    {
        if ($item->voyage_id !== $voyage->id) {
            abort(404);
        }

        return response()->json($item);
    }

    /**
     * Update an existing travel day item.
     */
    public function update(UpdateTravelDayItemRequest $request, Voyage $voyage, TravelDayItem $item): RedirectResponse
    {
        if ($item->voyage_id !== $voyage->id) {
            abort(404);
        }

        $validated = $request->validated();

        // Parse JSON fields if provided as strings
        if (isset($validated['options_json']) && is_string($validated['options_json'])) {
            $validated['options_json'] = json_decode($validated['options_json'], true);
        }
        if (isset($validated['meta_json']) && is_string($validated['meta_json'])) {
            $validated['meta_json'] = json_decode($validated['meta_json'], true);
        }

        $item->update($validated);

        return redirect()
            ->route('admin.circuits.voyages.edit', $voyage)
            ->with('success', 'Item mis à jour avec succès.');
    }

    /**
     * Delete a travel day item.
     */
    public function destroy(Voyage $voyage, TravelDayItem $item): RedirectResponse
    {
        if ($item->voyage_id !== $voyage->id) {
            abort(404);
        }

        $item->delete();

        return redirect()
            ->route('admin.circuits.voyages.edit', $voyage)
            ->with('success', 'Item supprimé avec succès.');
    }

    /**
     * Reorder items (AJAX endpoint).
     */
    public function reorder(Voyage $voyage)
    {
        $order = request()->input('order', []);
        
        foreach ($order as $index => $itemId) {
            TravelDayItem::where('voyage_id', $voyage->id)
                ->where('id', $itemId)
                ->update(['sort_order' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ordre mis à jour.',
        ]);
    }
}
