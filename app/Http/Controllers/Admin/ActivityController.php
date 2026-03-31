<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Wp\Activity;
use App\Services\WordPressMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(
        protected WordPressMediaService $mediaService
    ) {}

    public function index(): View
    {
        $activities = Activity::query()
            ->orderBy('region_name')
            ->orderBy('title')
            ->paginate(20);

        return view('admin.circuits.activities.index', compact('activities'));
    }

    public function create(): View
    {
        return view('admin.circuits.activities.create');
    }

    public function store(StoreActivityRequest $request): RedirectResponse|JsonResponse
    {
        $data = $this->prepareActivityData($request, $request->validated());
        $activity = Activity::create($data);

        if ($this->expectsJson($request)) {
            $payload = $this->serializeActivity($activity);

            return response()->json([
                'success' => true,
                'message' => __('ActivitÃ© crÃ©Ã©e avec succÃ¨s.'),
                'data' => $payload,
                'activity' => $payload,
                'errors' => (object) [],
            ]);
        }

        return redirect()
            ->route('admin.circuits.activities.index')
            ->with('success', 'ActivitÃ© crÃ©Ã©e avec succÃ¨s.');
    }

    public function edit(Activity $activity): View
    {
        return view('admin.circuits.activities.edit', compact('activity'));
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse|JsonResponse
    {
        $data = $this->prepareActivityData($request, $request->validated(), $activity);
        $activity->update($data);

        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => __('ActivitÃ© mise Ã  jour.'),
                'data' => $this->serializeActivity($activity->fresh()),
                'errors' => (object) [],
            ]);
        }

        return redirect()
            ->route('admin.circuits.activities.index')
            ->with('success', 'ActivitÃ© mise Ã  jour.');
    }

    public function destroy(Request $request, Activity $activity): RedirectResponse|JsonResponse
    {
        $deletedId = $activity->id;
        $activity->delete();

        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => __('ActivitÃ© supprimÃ©e.'),
                'data' => ['id' => $deletedId],
                'errors' => (object) [],
            ]);
        }

        return redirect()
            ->route('admin.circuits.activities.index')
            ->with('success', 'ActivitÃ© supprimÃ©e.');
    }

    public function ajaxList(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $regionTerms = $this->requestedRegionTerms($request);

        $query = Activity::query()->orderBy('region_name')->orderBy('title');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('activity_type', 'like', '%'.$search.'%')
                    ->orWhere('region_name', 'like', '%'.$search.'%');
            });
        }

        $activities = $query->get()
            ->filter(fn (Activity $activity) => $this->matchesRegionFilter($activity, $regionTerms))
            ->values()
            ->map(fn (Activity $activity) => $this->serializeActivity($activity));

        return response()->json([
            'success' => true,
            'message' => 'Liste des activitÃ©s chargÃ©e.',
            'data' => $activities,
            'errors' => (object) [],
        ]);
    }

    public function ajaxShow(Activity $activity): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'ActivitÃ© chargÃ©e.',
            'data' => $this->serializeActivity($activity),
            'errors' => (object) [],
        ]);
    }

    public function ajaxStore(StoreActivityRequest $request): JsonResponse
    {
        $activity = Activity::create($this->prepareActivityData($request, $request->validated()));

        return response()->json([
            'success' => true,
            'message' => 'ActivitÃ© crÃ©Ã©e avec succÃ¨s.',
            'data' => $this->serializeActivity($activity),
            'errors' => (object) [],
        ]);
    }

    public function ajaxUpdate(UpdateActivityRequest $request, Activity $activity): JsonResponse
    {
        $activity->update($this->prepareActivityData($request, $request->validated(), $activity));

        return response()->json([
            'success' => true,
            'message' => 'ActivitÃ© mise Ã  jour.',
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
            'message' => 'ActivitÃ© supprimÃ©e.',
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
        $galleryIds = $this->extractGalleryIds($activity);
        $gallery = $this->resolveGalleryImageUrls($galleryIds);
        $coverId = (int) ($activity->image_id ?? 0);
        $coverUrl = $coverId > 0 ? $this->mediaService->getAttachmentUrl($coverId) : null;

        if ($coverUrl === null && $gallery !== []) {
            $coverId = (int) ($gallery[0]['id'] ?? 0);
            $coverUrl = $gallery[0]['url'] ?? null;
        }

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'activity_type' => $activity->activity_type,
            'slug' => $activity->slug,
            'description' => $activity->description ?? '',
            'image_id' => $coverId > 0 ? $coverId : null,
            'image_url' => $coverUrl,
            'gallery_image_ids' => $galleryIds,
            'gallery' => $gallery,
            'gallery_images' => $gallery,
            'gallery_count' => count($galleryIds),
            'adult_price' => (float) ($activity->adult_price ?? $activity->base_price ?? 0),
            'child_price' => (float) ($activity->child_price ?? 0),
            'price' => (float) ($activity->adult_price ?? $activity->base_price ?? 0),
            'base_price' => (float) ($activity->adult_price ?? $activity->base_price ?? 0),
            'icon' => $activity->icon,
            'default_duration_minutes' => (int) ($activity->default_duration_minutes ?? 0),
            'duration_minutes' => (int) ($activity->default_duration_minutes ?? 0),
            'min_age' => (int) ($activity->min_age ?? 0),
            'max_age' => (int) ($activity->max_age ?? 0),
            'place_text' => $activity->region_name ?: $activity->location_text,
            'location_text' => $activity->region_name ?: $activity->location_text,
            'region_name' => $activity->region_name ?: $activity->location_text,
            'is_active' => (bool) ($activity->is_active ?? true),
        ];
    }

    private function prepareActivityData(Request $request, array $data, ?Activity $activity = null): array
    {
        $data = $this->normalizeActivityPayload($data);

        $galleryIds = collect($request->input('existing_gallery_image_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if ($request->hasFile('image')) {
            $galleryIds[] = $this->mediaService->uploadAndCreateAttachment($request->file('image'));
        }

        foreach ((array) $request->file('gallery_images', []) as $file) {
            if ($file) {
                $galleryIds[] = $this->mediaService->uploadAndCreateAttachment($file);
            }
        }

        if ($galleryIds === [] && $activity !== null && ! $request->boolean('gallery_state_present')) {
            $galleryIds = $this->extractGalleryIds($activity);
        }

        $galleryIds = array_values(array_unique(array_filter(array_map('intval', $galleryIds))));

        $data['gallery_image_ids'] = $galleryIds === [] ? null : $galleryIds;
        $data['image_id'] = $galleryIds[0] ?? null;
        $data['base_price'] = $data['adult_price'] ?? $data['base_price'] ?? 0;

        unset(
            $data['image'],
            $data['gallery_images'],
            $data['existing_gallery_image_ids'],
            $data['gallery_state_present']
        );

        return $data;
    }

    private function normalizeActivityPayload(array $data): array
    {
        if (! array_key_exists('is_active', $data)) {
            $data['is_active'] = true;
        }

        $region = trim((string) ($data['region_name'] ?? $data['location_text'] ?? $data['place_text'] ?? ''));
        $data['region_name'] = $region !== '' ? $region : null;
        $data['location_text'] = $region !== '' ? $region : null;
        $data['place_text'] = $region !== '' ? $region : null;

        if (! empty($data['adult_price']) && empty($data['base_price'])) {
            $data['base_price'] = $data['adult_price'];
        }

        unset($data['place_text']);

        return $data;
    }

    private function extractGalleryIds(Activity $activity): array
    {
        $value = $activity->gallery_image_ids;
        $ids = [];

        if (is_array($value)) {
            $ids = $value;
        } elseif (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            $ids = is_array($decoded) ? $decoded : explode(',', $value);
        }

        $ids = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if ($ids === [] && (int) ($activity->image_id ?? 0) > 0) {
            $ids[] = (int) $activity->image_id;
        }

        return array_values(array_unique($ids));
    }

    private function resolveGalleryImageUrls(array $ids): array
    {
        $gallery = [];

        foreach ($ids as $id) {
            $url = $this->mediaService->getAttachmentUrl((int) $id);
            if ($url === null) {
                continue;
            }

            $gallery[] = [
                'id' => (int) $id,
                'url' => $url,
            ];
        }

        return $gallery;
    }

    private function requestedRegionTerms(Request $request): array
    {
        $raw = [];
        $single = trim((string) $request->query('region', ''));
        if ($single !== '') {
            $raw[] = $single;
        }

        foreach ((array) $request->query('regions', []) as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $raw[] = $value;
            }
        }

        return collect($raw)
            ->flatMap(function (string $value) {
                return preg_split('/[,;\/|]+/', $value) ?: [];
            })
            ->map(fn ($value) => $this->normalizeRegionTerm((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function matchesRegionFilter(Activity $activity, array $requestedTerms): bool
    {
        if ($requestedTerms === []) {
            return true;
        }

        $activityTerms = collect([
            $activity->region_name,
            $activity->location_text,
        ])->filter()
            ->flatMap(function ($value) {
                return preg_split('/[,;\/|]+/', (string) $value) ?: [];
            })
            ->map(fn ($value) => $this->normalizeRegionTerm((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($activityTerms === []) {
            return false;
        }

        foreach ($activityTerms as $activityTerm) {
            foreach ($requestedTerms as $requestedTerm) {
                if ($activityTerm === $requestedTerm
                    || str_contains($activityTerm, $requestedTerm)
                    || str_contains($requestedTerm, $activityTerm)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalizeRegionTerm(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return $value;
    }
}
