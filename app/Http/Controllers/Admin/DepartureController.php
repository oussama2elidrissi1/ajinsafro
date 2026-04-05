<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Voyage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartureController extends Controller
{
    public function store(Request $request, Voyage $voyage): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'status' => ['required', 'string', Rule::in(Departure::STATUSES)],
            'total_capacity' => 'required|integer|min:0',
            'base_price' => 'nullable|numeric|min:0',
        ]);

        $already = Departure::query()
            ->where('voyage_id', $voyage->id)
            ->whereDate('start_date', $validated['start_date'])
            ->exists();

        Departure::updateOrCreate(
            [
                'voyage_id' => $voyage->id,
                'start_date' => $validated['start_date'],
            ],
            [
                'status' => $validated['status'],
                'total_capacity' => (int) $validated['total_capacity'],
                'available_capacity' => max(0, (int) $validated['total_capacity']),
                'base_price' => $validated['base_price'] ?? null,
            ]
        );

        return redirect()->route('admin.circuits.voyages.edit', $voyage->wp_post_id ?? $voyage->id)
            ->with('success', $already ? 'Départ mis à jour (date déjà existante pour ce voyage).' : 'Départ ajouté.');
    }

    public function update(Request $request, Voyage $voyage, Departure $departure): RedirectResponse
    {
        if ($departure->voyage_id !== $voyage->id) {
            abort(404);
        }
        $validated = $request->validate([
            'start_date' => 'required|date',
            'status' => ['required', 'string', Rule::in(Departure::STATUSES)],
            'total_capacity' => 'required|integer|min:0',
            'base_price' => 'nullable|numeric|min:0',
        ]);

        $other = Departure::query()
            ->where('voyage_id', $voyage->id)
            ->where('id', '!=', $departure->id)
            ->whereDate('start_date', $validated['start_date'])
            ->first();

        if ($other) {
            return redirect()->route('admin.circuits.voyages.edit', $voyage->wp_post_id ?? $voyage->id)
                ->withErrors(['start_date' => 'Une autre ligne existe déjà pour cette date de départ. Modifiez-la ou fusionnez les départs.']);
        }

        $departure->update([
            'start_date' => $validated['start_date'],
            'status' => $validated['status'],
            'total_capacity' => (int) $validated['total_capacity'],
            'available_capacity' => max(0, (int) $validated['total_capacity'] - (int) ($departure->reserved_capacity ?? 0)),
            'base_price' => $validated['base_price'] ?? null,
        ]);
        return redirect()->route('admin.circuits.voyages.edit', $voyage->wp_post_id ?? $voyage->id)
            ->with('success', 'Départ mis à jour.');
    }

    public function destroy(Voyage $voyage, Departure $departure): RedirectResponse
    {
        if ($departure->voyage_id !== $voyage->id) {
            abort(404);
        }
        $departure->delete();
        return redirect()->route('admin.circuits.voyages.edit', $voyage->wp_post_id ?? $voyage->id)
            ->with('success', 'Départ supprimé.');
    }
}
