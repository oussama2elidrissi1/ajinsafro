<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Wp\Activity;
use App\Services\WordPressMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(
        protected WordPressMediaService $mediaService
    ) {}

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
        $data = $request->validated();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $attachmentId = $this->mediaService->uploadAndCreateAttachment($request->file('image'));
            $data['image_id'] = $attachmentId;
        }
        
        // Remove 'image' from data as it's not a database column
        unset($data['image']);
        
        Activity::create($data);
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
        $data = $request->validated();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $attachmentId = $this->mediaService->uploadAndCreateAttachment($request->file('image'));
            $data['image_id'] = $attachmentId;
        }
        
        // Remove 'image' from data as it's not a database column
        unset($data['image']);
        
        $activity->update($data);
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
