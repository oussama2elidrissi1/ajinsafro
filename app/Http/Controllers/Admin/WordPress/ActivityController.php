<?php

namespace App\Http\Controllers\Admin\WordPress;

use App\Http\Controllers\Controller;
use App\Http\Requests\WordPressActivityStoreRequest;
use App\Http\Requests\WordPressActivityUpdateRequest;
use App\Models\CatalogActivity;
use App\Models\Wp\StActivity;
use App\Models\Wp\WpPost;
use App\Services\WordPressCatalogSyncService;
use App\Services\WordPressMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(
        protected WordPressMediaService $media,
        protected WordPressCatalogSyncService $sync
    ) {}

    public function index(Request $request): View
    {
        $postsTable = (new WpPost())->getTable();
        $activityTable = (new StActivity())->getTable();

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $type = trim((string) $request->query('type_activity', ''));

        $activities = WpPost::query()
            ->leftJoin($activityTable, $postsTable.'.ID', '=', $activityTable.'.post_id')
            ->select($postsTable.'.*')
            ->with('stActivity')
            ->where($postsTable.'.post_type', 'st_activity')
            ->whereIn($postsTable.'.post_status', ['publish', 'draft'])
            ->when($search !== '', function ($query) use ($search, $postsTable, $activityTable) {
                $query->where(function ($inner) use ($search, $postsTable, $activityTable) {
                    $inner->where($postsTable.'.post_title', 'like', '%'.$search.'%')
                        ->orWhere($postsTable.'.post_name', 'like', '%'.$search.'%')
                        ->orWhere($activityTable.'.address', 'like', '%'.$search.'%')
                        ->orWhere($activityTable.'.type_activity', 'like', '%'.$search.'%');
                });
            })
            ->when(in_array($status, ['publish', 'draft'], true), fn ($query) => $query->where($postsTable.'.post_status', $status))
            ->when($type !== '', fn ($query) => $query->where($activityTable.'.type_activity', $type))
            ->orderByDesc($postsTable.'.post_modified')
            ->paginate(15)
            ->withQueryString();

        $typeOptions = StActivity::query()
            ->whereNotNull('type_activity')
            ->where('type_activity', '!=', '')
            ->distinct()
            ->orderBy('type_activity')
            ->pluck('type_activity');

        return view('admin.wordpress.activities.index', [
            'activities' => $activities,
            'typeOptions' => $typeOptions,
            'media' => $this->media,
            'filters' => compact('search', 'status', 'type'),
        ]);
    }

    public function create(): View
    {
        return view('admin.wordpress.activities.create');
    }

    public function store(WordPressActivityStoreRequest $request): RedirectResponse
    {
        $this->sync->saveActivityFromRequest($request->validated(), $request);

        return redirect()
            ->route('admin.wordpress.activities.index')
            ->with('success', 'Activit? cr??e avec succ?s.');
    }

    public function edit(int $activity): View
    {
        $record = $this->sync->syncActivityRecordFromWpPostId($activity);
        $wpPost = $this->sync->getWpPost($activity, 'st_activity');

        return view('admin.wordpress.activities.edit', [
            'activity' => $wpPost,
            'stActivity' => (object) [
                'post_id' => $wpPost->ID,
                'address' => $record->address,
                'type_activity' => $record->type_activity,
                'adult_price' => $record->adult_price,
                'child_price' => $record->child_price,
                'min_price' => $record->min_price,
                'duration' => $record->duration,
                'max_people' => $record->max_people,
                'rate_review' => $record->rate_review,
                'is_featured' => $record->is_featured ? 'on' : 'off',
            ],
            'featuredUrl' => $this->media->getFeaturedImageUrlVerified($wpPost->ID),
            'galleryUrls' => $this->media->getGalleryUrlsVerified($wpPost->ID),
            'meta' => [
                'aj_activity_category' => $record->category,
                'aj_activity_place_text' => $record->place_text,
                'aj_activity_min_age' => $record->min_age,
                'aj_activity_max_age' => $record->max_age,
            ],
        ]);
    }

    public function update(WordPressActivityUpdateRequest $request, int $activity): RedirectResponse
    {
        $record = CatalogActivity::query()->where('wp_post_id', $activity)->first()
            ?? $this->sync->syncActivityRecordFromWpPostId($activity);

        $this->sync->saveActivityFromRequest($request->validated(), $request, $record);

        return redirect()
            ->route('admin.wordpress.activities.index')
            ->with('success', 'Activit? mise ? jour.');
    }

    public function destroy(int $activity): RedirectResponse
    {
        $this->sync->trashActivityByWpPostId($activity);

        return redirect()
            ->route('admin.wordpress.activities.index')
            ->with('success', 'Activit? d?plac?e dans la corbeille.');
    }
}
