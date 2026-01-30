<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelProgramDay;
use App\Models\Voyage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TravelProgramDayController extends Controller
{
    public function store(Request $request, Voyage $voyage): RedirectResponse
    {
        $nextDay = $voyage->programDays()->max('day_number') + 1;
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'day_label' => 'nullable|string|in:inclus,optionnel,libre',
            'content_html' => 'nullable|string',
            'description' => 'nullable|string',
            'nights' => 'nullable|integer|min:0|max:1',
            'day_type' => 'nullable|in:arrivee,visite,transfert,libre',
            'meal_breakfast' => 'boolean',
            'meal_lunch' => 'boolean',
            'meal_dinner' => 'boolean',
        ]);
        $validated['voyage_id'] = $voyage->id;
        $validated['day_number'] = $nextDay;
        $validated['meal_breakfast'] = $request->boolean('meal_breakfast');
        $validated['meal_lunch'] = $request->boolean('meal_lunch');
        $validated['meal_dinner'] = $request->boolean('meal_dinner');
        $validated['meals_json'] = [
            'breakfast' => $validated['meal_breakfast'],
            'lunch' => $validated['meal_lunch'],
            'dinner' => $validated['meal_dinner'],
        ];
        if (empty($validated['content_html']) && !empty($validated['description'])) {
            $validated['content_html'] = '<p>' . nl2br(e($validated['description'])) . '</p>';
        }
        TravelProgramDay::create($validated);
        return redirect()->route('admin.circuits.voyages.edit', $voyage)
            ->with('success', 'Jour ajouté au programme.');
    }

    public function update(Request $request, Voyage $voyage, TravelProgramDay $programDay): RedirectResponse
    {
        $this->ensureDayBelongsToVoyage($voyage, $programDay);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'day_label' => 'nullable|string|in:inclus,optionnel,libre',
            'content_html' => 'nullable|string',
            'description' => 'nullable|string',
            'nights' => 'nullable|integer|min:0|max:1',
            'day_type' => 'nullable|in:arrivee,visite,transfert,libre',
            'meal_breakfast' => 'boolean',
            'meal_lunch' => 'boolean',
            'meal_dinner' => 'boolean',
        ]);
        $validated['meal_breakfast'] = $request->boolean('meal_breakfast');
        $validated['meal_lunch'] = $request->boolean('meal_lunch');
        $validated['meal_dinner'] = $request->boolean('meal_dinner');
        $validated['meals_json'] = [
            'breakfast' => $validated['meal_breakfast'],
            'lunch' => $validated['meal_lunch'],
            'dinner' => $validated['meal_dinner'],
        ];
        if (empty($validated['content_html']) && !empty($validated['description'])) {
            $validated['content_html'] = '<p>' . nl2br(e($validated['description'])) . '</p>';
        }
        $programDay->update($validated);
        return redirect()->route('admin.circuits.voyages.edit', $voyage)
            ->with('success', 'Jour mis à jour.');
    }

    public function destroy(Voyage $voyage, TravelProgramDay $programDay): RedirectResponse
    {
        $this->ensureDayBelongsToVoyage($voyage, $programDay);
        $deletedNumber = $programDay->day_number;
        $programDay->delete();
        TravelProgramDay::where('voyage_id', $voyage->id)
            ->where('day_number', '>', $deletedNumber)
            ->orderBy('day_number')
            ->get()
            ->each(function (TravelProgramDay $day, $index) use ($deletedNumber) {
                $day->update(['day_number' => $deletedNumber + $index]);
            });
        return redirect()->route('admin.circuits.voyages.edit', $voyage)
            ->with('success', 'Jour supprimé. Les jours suivants ont été renumérotés.');
    }

    private function ensureDayBelongsToVoyage(Voyage $voyage, TravelProgramDay $programDay): void
    {
        if ($programDay->voyage_id !== $voyage->id) {
            abort(404);
        }
    }
}
