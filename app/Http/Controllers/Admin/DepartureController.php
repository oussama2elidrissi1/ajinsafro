<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Voyage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DepartureController extends Controller
{
    public function store(Request $request, Voyage $voyage): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'status' => 'required|in:open,full,canceled',
        ]);
        $validated['voyage_id'] = $voyage->id;
        Departure::create($validated);
        return redirect()->route('admin.circuits.voyages.edit', $voyage)
            ->with('success', 'Départ ajouté.');
    }

    public function update(Request $request, Voyage $voyage, Departure $departure): RedirectResponse
    {
        if ($departure->voyage_id !== $voyage->id) {
            abort(404);
        }
        $validated = $request->validate([
            'start_date' => 'required|date',
            'status' => 'required|in:open,full,canceled',
        ]);
        $departure->update($validated);
        return redirect()->route('admin.circuits.voyages.edit', $voyage)
            ->with('success', 'Départ mis à jour.');
    }

    public function destroy(Voyage $voyage, Departure $departure): RedirectResponse
    {
        if ($departure->voyage_id !== $voyage->id) {
            abort(404);
        }
        $departure->delete();
        return redirect()->route('admin.circuits.voyages.edit', $voyage)
            ->with('success', 'Départ supprimé.');
    }
}
