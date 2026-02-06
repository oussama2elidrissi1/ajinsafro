<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Wp\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        $activities = Activity::query()->orderBy('title')->paginate(20);
        return view('admin.circuits.activities.index', compact('activities'));
    }

    public function create(): View
    {
        return view('admin.circuits.activities.create');
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        Activity::create($request->validated());
        return redirect()
            ->route('admin.circuits.activities.index')
            ->with('success', 'Activité créée avec succès.');
    }

    public function edit(Activity $activity): View
    {
        return view('admin.circuits.activities.edit', compact('activity'));
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $activity->update($request->validated());
        return redirect()
            ->route('admin.circuits.activities.index')
            ->with('success', 'Activité mise à jour.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $activity->delete();
        return redirect()
            ->route('admin.circuits.activities.index')
            ->with('success', 'Activité supprimée.');
    }
}
