<?php

namespace App\Services\Wp;

use App\Models\Wp\Activity;
use App\Models\Wp\TourDay;
use App\Models\Wp\TourDayActivity;
use Illuminate\Support\Collection;

class TourProgramService
{
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
     * @param array{sort_order?: int, is_included?: int, is_mandatory?: int, is_editable?: int, custom_title?: string, custom_description?: string, start_time?: string, end_time?: string} $options
     */
    public function addActivityToDay(int $dayId, int $activityId, array $options = []): TourDayActivity
    {
        $day = TourDay::findOrFail($dayId);
        $maxOrder = TourDayActivity::where('day_id', $dayId)->max('sort_order') ?? 0;

        $da = TourDayActivity::create([
            'tour_id' => $day->tour_id,
            'day_id' => $dayId,
            'activity_id' => $activityId,
            'sort_order' => $options['sort_order'] ?? $maxOrder + 1,
            'is_included' => $options['is_included'] ?? 1,
            'is_mandatory' => $options['is_mandatory'] ?? 0,
            'is_editable' => $options['is_editable'] ?? 1,
            'custom_title' => $options['custom_title'] ?? null,
            'custom_description' => $options['custom_description'] ?? null,
            'start_time' => $options['start_time'] ?? null,
            'end_time' => $options['end_time'] ?? null,
        ]);
        return $da->load('activity');
    }

    /**
     * Update a day-activity row.
     */
    public function updateDayActivity(int $dayActivityId, array $data): TourDayActivity
    {
        $da = TourDayActivity::findOrFail($dayActivityId);
        $fillable = ['sort_order', 'is_included', 'is_mandatory', 'is_editable', 'custom_title', 'custom_description', 'start_time', 'end_time'];
        $da->fill(array_intersect_key($data, array_flip($fillable)));
        $da->save();
        return $da->load('activity');
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
}
