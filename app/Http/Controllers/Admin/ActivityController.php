<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\WpPostmeta;
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

    public function ajaxList(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $query = Activity::query()->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        $activities = $query->get()->map(fn (Activity $activity) => $this->serializeActivity($activity));

        return response()->json([
            'success' => true,
            'message' => 'Liste des activités chargée.',
            'data' => $activities,
            'errors' => (object) [],
        ]);
    }

    public function ajaxShow(Activity $activity): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Activité chargée.',
            'data' => $this->serializeActivity($activity),
            'errors' => (object) [],
        ]);
    }

    public function ajaxStore(StoreActivityRequest $request): JsonResponse
    {
        $data = $this->normalizeActivityPayload($request->validated());

        if ($request->hasFile('image')) {
            $attachmentId = $this->mediaService->uploadAndCreateAttachment($request->file('image'));
            $data['image_id'] = $attachmentId;
        }

        unset($data['image']);

        $activity = Activity::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Activité créée avec succès.',
            'data' => $this->serializeActivity($activity),
            'errors' => (object) [],
        ]);
    }

    public function ajaxUpdate(UpdateActivityRequest $request, Activity $activity): JsonResponse
    {
        $data = $this->normalizeActivityPayload($request->validated());

        if ($request->hasFile('image')) {
            $attachmentId = $this->mediaService->uploadAndCreateAttachment($request->file('image'));
            $data['image_id'] = $attachmentId;
        }

        unset($data['image']);

        $activity->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Activité mise à jour.',
            'data' => $this->serializeActivity($activity->fresh()),
            'errors' => (object) [],
        ]);
    }

    public function ajaxDestroy(Activity $activity): JsonResponse
    {
        $deletedId = $activity->id;
        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activité supprimée.',
            'data' => ['id' => $deletedId],
            'errors' => (object) [],
        ]);
    }

    private function expectsJson(Request $request): bool
    {
        return $request->ajax() || $request->expectsJson() || $request->wantsJson();
    }

    private function serializeActivity(Activity $activity): array
    {
        $imageUrl = null;
        if (!empty($activity->image_id)) {
            $file = WpPostmeta::getMeta((int) $activity->image_id, '_wp_attached_file');
            if ($file) {
                $uploadsUrl = config('wordpress.uploads_url', url('/wp-content/uploads'));
                $imageUrl = rtrim($uploadsUrl, '/') . '/' . ltrim($file, '/');
            }
        }

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'slug' => $activity->slug,
            'description' => $activity->description ?? '',
            'image_id' => $activity->image_id,
            'image_url' => $imageUrl,
            'price' => $activity->base_price,
            'base_price' => $activity->base_price,
            'icon' => $activity->icon,
            'default_duration_minutes' => $activity->default_duration_minutes,
            'place_text' => $activity->location_text,
            'location_text' => $activity->location_text,
            'is_active' => (bool) ($activity->is_active ?? true),
        ];
    }

    private function normalizeActivityPayload(array $data): array
    {
        if (!array_key_exists('is_active', $data)) {
            $data['is_active'] = true;
        }

        if (!empty($data['place_text']) && empty($data['location_text'])) {
            $data['location_text'] = $data['place_text'];
        }

        unset($data['place_text']);

        return $data;
    }
}
