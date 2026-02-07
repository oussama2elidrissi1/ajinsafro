<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAirlineRequest;
use App\Http\Requests\UpdateAirlineRequest;
use App\Models\AjAirline;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AirlineController extends Controller
{
    public function index(): View
    {
        $airlines = AjAirline::query()->orderBy('name')->paginate(20);
        return view('admin.circuits.airlines.index', compact('airlines'));
    }

    public function create(): View
    {
        return view('admin.circuits.airlines.create');
    }

    public function store(StoreAirlineRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        if (empty($data['slug'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }
        AjAirline::create($data);
        return redirect()
            ->route('admin.circuits.airlines.index')
            ->with('success', 'Compagnie aérienne créée avec succès.');
    }

    public function edit(AjAirline $airline): View
    {
        return view('admin.circuits.airlines.edit', compact('airline'));
    }

    public function update(UpdateAirlineRequest $request, AjAirline $airline): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $airline->update($data);
        return redirect()
            ->route('admin.circuits.airlines.index')
            ->with('success', 'Compagnie aérienne mise à jour.');
    }

    public function destroy(AjAirline $airline): RedirectResponse
    {
        $airline->delete();
        return redirect()
            ->route('admin.circuits.airlines.index')
            ->with('success', 'Compagnie aérienne supprimée.');
    }
}
