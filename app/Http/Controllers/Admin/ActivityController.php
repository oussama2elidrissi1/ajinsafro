<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Wp\Activity;
use App\Services\WordPressMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function store(StoreActivityRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $attachmentId = $this->mediaService->uploadAndCreateAttachment($request->file('image'));
            $data['image_id'] = $attachmentId;
        }

        // Remove 'image' from data as it's not a database column
        unset($data['image']);

        $activity = Activity::create($data);

        if ($this->expectsJson($request)) {
            $payload = $this->serializeActivity($activity);

            return response()->json([
                'success' => true,
                'message' => __('Activité créée avec succès.'),
                'data' => $payload,
                'activity' => $payload,
                'errors' => (object) [],
            ]);
        }

        return redirect()
            ->route('admin.circuits.activities.index')
            ->with('success', 'Activité créée avec succès.');
    }

    public function edit(Activity $activity): View
    {
        return view('admin.circuits.activities.edit', compact('activity'));
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse|JsonResponse
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

        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => __('Activité mise à jour.'),
                'data' => $this->serializeActivity($activity->fresh()),
                'errors' => (object) [],
            ]);
        }

        return redirect()
            ->route('admin.circuits.activities.index')
            ->with('success', 'Activité mise à jour.');
    }

    public function destroy(Request $request, Activity $activity): RedirectResponse|JsonResponse
    {
        $deletedId = $activity->id;
        $activity->delete();

        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => __('Activité supprimée.'),
                'data' => ['id' => $deletedId],
                'errors' => (object) [],
            ]);
        }

        return redirect()
            ->route('admin.circuits.activities.index')
            ->with('success', 'Activité supprimée.');
    }

    private function expectsJson(Request $request): bool
    {
        return $request->ajax() || $request->expectsJson() || $request->wantsJson();
    }

    private function serializeActivity(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'description' => $activity->description ?? '',
            'image_url' => null,
            'price' => $activity->base_price,
            'base_price' => $activity->base_price,
        ];
    }
}
