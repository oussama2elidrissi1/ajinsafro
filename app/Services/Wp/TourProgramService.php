<?php

namespace App\Services\Wp;

use App\Models\Wp\Activity;
use App\Models\Wp\TourDay;
use App\Models\Wp\TourDayActivity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TourProgramService
{
    /**
     * Cached list of available columns on wp.aj_tour_day_activities.
     *
     * @var array<string, bool>|null
     */
    private ?array $dayActivityColumns = null;

    /**
     * Load full program for a tour: days + activities sorted.
     *
     * @param int $tourId wp_posts.ID (st_tours)
     * @return Collection<int, array{day: TourDay, activities: Collection}>
     */
    public function loadProgram(int $tourId): Collection
    {
        $days = TourDay::query()
            ->where('tour_id', $tourId)
            ->with(['dayActivities' => fn ($q) => $q->with('activity')->orderBy('sort_order')])
            ->orderBy('day_number')
            ->get();

        return $days->map(fn (TourDay $day) => [
            'day' => $day,
            'activities' => $day->dayActivities,
        ]);
    }

    /**
     * Ensure a tour has exactly $count days (create missing, do not delete extra).
     * @deprecated Prefer addDay() / dynamic days; use only for migration or initial seed.
     */
    public function ensureDaysExist(int $tourId, int $count): void
    {
        $existing = TourDay::where('tour_id', $tourId)->pluck('day_number')->sort()->values();
        for ($n = 1; $n <= $count; $n++) {
            if (!$existing->contains($n)) {
                TourDay::create([
                    'tour_id' => $tourId,
                    'day_number' => $n,
                    'title' => 'Jour ' . $n,
                    'mode' => 'program',
                ]);
            }
        }
    }

    /**
     * Add a new day to the tour (day_number = last + 1).
     *
     * @param int $tourId wp_posts.ID (st_tours)
     * @return TourDay
     */
    public function addDay(int $tourId): TourDay
    {
        $max = TourDay::where('tour_id', $tourId)->max('day_number') ?? 0;
        $next = (int) $max + 1;
        return TourDay::create([
            'tour_id' => $tourId,
            'day_number' => $next,
            'title' => 'Jour ' . $next,
            'mode' => 'program',
        ]);
    }

    /**
     * Delete a day and renumber remaining days to 1..N.
     * Deletes all day activities first.
     *
     * @param int $tourId wp_posts.ID (st_tours)
     * @param int $dayId aj_tour_days.id
     * @return void
     */
    public function deleteDay(int $tourId, int $dayId): void
    {
        $day = TourDay::where('id', $dayId)->where('tour_id', $tourId)->firstOrFail();
        TourDayActivity::where('day_id', $dayId)->delete();
        $day->delete();

        $remaining = TourDay::where('tour_id', $tourId)->orderBy('day_number')->get();
        foreach ($remaining as $index => $d) {
            $newNumber = $index + 1;
            if ((int) $d->day_number !== $newNumber) {
                $d->day_number = $newNumber;
                $d->save();
            }
        }
    }

    /**
     * Count days for a tour (for updating duration_day meta).
     *
     * @param int $tourId
     * @return int
     */
    public function countDays(int $tourId): int
    {
        return TourDay::where('tour_id', $tourId)->count();
    }

    /**
     * Set day_number for a single day (used after reorder).
     *
     * @param int $dayId aj_tour_days.id
     * @param int $dayNumber 1-based
     */
    public function setDayNumber(int $dayId, int $dayNumber): void
    {
        TourDay::where('id', $dayId)->update(['day_number' => $dayNumber]);
    }

    /**
     * Renumber days to 1..N according to the given order. Deletes any tour day not in the list.
     *
     * @param int $tourId wp_posts.ID
     * @param int[] $orderedDayIds [day_id, ...] in display order
     */
    public function reorderAndRenumberDays(int $tourId, array $orderedDayIds): void
    {
        $orderedDayIds = array_values(array_filter(array_map('intval', $orderedDayIds)));
        $existingIds = TourDay::where('tour_id', $tourId)->pluck('id')->toArray();
        $toDelete = array_diff($existingIds, $orderedDayIds);
        foreach ($toDelete as $dayId) {
            $this->deleteDay($tourId, (int) $dayId);
        }
        foreach ($orderedDayIds as $index => $dayId) {
            $this->setDayNumber($dayId, $index + 1);
        }
    }

    /**
     * Update day mode (free|program) and optional fields.
     */
    public function saveDayMode(int $dayId, string $mode, array $data = []): TourDay
    {
        $day = TourDay::where('id', $dayId)->firstOrFail();
        $day->mode = $mode;
        if (array_key_exists('day_title', $data)) {
            $day->day_title = $data['day_title'];
        }
        if (array_key_exists('notes', $data)) {
            $day->notes = $data['notes'];
        }
        if (array_key_exists('title', $data)) {
            $day->title = $data['title'];
        }
        if (array_key_exists('description', $data)) {
            $day->description = $data['description'];
        }
        $day->save();
        return $day;
    }

    /**
     * Update a day (title, description, mode, day_title, notes).
     */
    public function updateDay(int $dayId, array $data): TourDay
    {
        $day = TourDay::findOrFail($dayId);
        $day->fill(array_intersect_key($data, array_flip($day->getFillable())));
        $day->save();
        return $day;
    }

    /**
     * Add an activity to a day.
     *
     * @param int $dayId aj_tour_days.id
     * @param int $activityId aj_activities.id
    * @param array{sort_order?: int, is_included?: int, is_mandatory?: int, is_editable?: int, day_scope?: string, custom_title?: string, custom_description?: string, custom_price?: mixed, start_time?: string, end_time?: string} $options
     */
    public function addActivityToDay(int $dayId, int $activityId, array $options = []): TourDayActivity
    {
        $day = TourDay::findOrFail($dayId);
        $maxOrder = TourDayActivity::where('day_id', $dayId)->max('sort_order') ?? 0;

        $payload = $this->filterDayActivityPayload([
            'tour_id' => $day->tour_id,
            'day_id' => $dayId,
            'activity_id' => $activityId,
            'sort_order' => $options['sort_order'] ?? $maxOrder + 1,
            'is_included' => $options['is_included'] ?? 1,
            'status' => $options['status'] ?? (($options['is_included'] ?? 1) ? 'included' : 'optional'),
            'day_scope' => ($options['day_scope'] ?? 'fixed') === 'open' ? 'open' : 'fixed',
            'is_mandatory' => $options['is_mandatory'] ?? 0,
            'is_editable' => $options['is_editable'] ?? 1,
            'custom_title' => $options['custom_title'] ?? null,
            'custom_description' => $options['custom_description'] ?? null,
            'custom_price' => $options['custom_price'] ?? null,
            'start_time' => $options['start_time'] ?? null,
            'end_time' => $options['end_time'] ?? null,
        ]);

        $da = TourDayActivity::create($payload);
        return $da->load('activity');
    }

    /**
     * Update a day-activity row.
     */
    public function updateDayActivity(int $dayActivityId, array $data): TourDayActivity
    {
        $da = TourDayActivity::findOrFail($dayActivityId);
        $fillable = ['day_id', 'sort_order', 'is_included', 'status', 'day_scope', 'is_mandatory', 'is_editable', 'custom_title', 'custom_description', 'custom_price', 'start_time', 'end_time'];
        $payload = array_intersect_key($data, array_flip($fillable));
        $payload = $this->filterDayActivityPayload($payload);
        $da->fill($payload);
        $da->save();
        return $da->load('activity');
    }

    /**
     * Keep only columns available on wp.aj_tour_day_activities.
     *
     * This avoids SQL errors when some production environments lag behind
     * schema updates (for example day_scope/custom_price).
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function filterDayActivityPayload(array $payload): array
    {
        $available = $this->resolveDayActivityColumns();

        return array_filter(
            $payload,
            static fn ($value, string $key): bool => isset($available[$key]),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Resolve table columns from WP connection, with a safe fallback.
     *
     * @return array<string, bool>
     */
    private function resolveDayActivityColumns(): array
    {
        if (is_array($this->dayActivityColumns)) {
            return $this->dayActivityColumns;
        }

        try {
            $columns = DB::connection('wp')
                ->getSchemaBuilder()
                ->getColumnListing('aj_tour_day_activities');

            if (is_array($columns) && $columns !== []) {
                $this->dayActivityColumns = array_fill_keys(
                    array_map(static fn ($column) => (string) $column, $columns),
                    true
                );

                return $this->dayActivityColumns;
            }
        } catch (\Throwable $e) {
            // Fall back to the baseline schema below.
        }

        // Baseline columns guaranteed by the initial table creation.
        $this->dayActivityColumns = array_fill_keys([
            'id',
            'tour_id',
            'day_id',
            'activity_id',
            'sort_order',
            'is_included',
            'is_mandatory',
            'is_editable',
            'custom_title',
            'custom_description',
            'start_time',
            'end_time',
            'created_at',
            'updated_at',
        ], true);

        return $this->dayActivityColumns;
    }

    /**
     * Remove an activity from a day. Fails if is_mandatory.
     */
    public function removeDayActivity(int $dayActivityId): bool
    {
        $da = TourDayActivity::findOrFail($dayActivityId);
        if ($da->is_mandatory) {
            return false;
        }
        $da->delete();
        return true;
    }

    /**
     * Reorder activities for a day (array of day_activity ids in order).
     */
    public function reorderDayActivities(int $dayId, array $dayActivityIdsInOrder): void
    {
        foreach ($dayActivityIdsInOrder as $order => $id) {
            TourDayActivity::where('id', $id)->where('day_id', $dayId)->update(['sort_order' => $order]);
        }
    }

    /**
     * One-time import: if day 1 notes are empty and WP meta tours_program exists,
     * convert tours_program to HTML and save into day 1 notes.
     * Front will then use notes only (no WP programme items in a day).
     *
     * @param int $tourId wp_posts.ID (st_tours)
     */
    public function importWpToursProgramToDayNotesIfEmpty(int $tourId): void
    {
        $day1 = TourDay::where('tour_id', $tourId)->where('day_number', 1)->first();
        if (!$day1 || trim((string) ($day1->notes ?? '')) !== '') {
            return;
        }

        $raw = DB::connection('wp')
            ->table('postmeta')
            ->where('post_id', $tourId)
            ->where('meta_key', 'tours_program')
            ->value('meta_value');

        if ($raw === null || $raw === '') {
            return;
        }

        $data = @unserialize($raw);
        if (!is_array($data) || empty($data)) {
            return;
        }

        $parts = [];
        foreach ($data as $item) {
            $title = '';
            $desc = '';
            if (is_array($item)) {
                $title = trim((string) ($item['title'] ?? ''));
                $desc = trim((string) ($item['desc'] ?? $item['description'] ?? $item['content'] ?? ''));
            } elseif (is_string($item)) {
                $desc = trim($item);
            }
            $descSafe = strip_tags($desc, '<p><br><strong><em><a><ul><ol><li>');
            $parts[] = '<p><strong>' . e($title) . '</strong><br>' . $descSafe . '</p>';
        }

        $html = implode("\n", $parts);
        $day1->notes = $html;
        $day1->save();
    }
}
