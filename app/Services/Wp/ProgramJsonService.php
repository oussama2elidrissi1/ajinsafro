<?php

namespace App\Services\Wp;

use App\Models\Wp\TourDay;
use App\Models\Wp\TourDayActivity;
use App\Models\Wp\WpPost;
use App\Services\Wp\WpTourRepository;
use Illuminate\Support\Str;

/**
 * Programme stocké en postmeta aj_program_json.
 * Structure: program_days[] avec day_uid, day_number, title, notes, items[] (type, ref_id, sort, meta_snapshot).
 * Synchronise avec aj_tour_days et aj_tour_day_activities pour le front.
 */
class ProgramJsonService
{
    public const META_KEY = 'aj_program_json';

    public const ITEM_TYPE_FLIGHT = 'flight';
    public const ITEM_TYPE_TRANSFER = 'transfer';
    public const ITEM_TYPE_HOTEL = 'hotel';
    public const ITEM_TYPE_ACTIVITY = 'activity';

    protected TourProgramService $programService;

    protected WpTourRepository $repository;

    public function __construct(TourProgramService $programService, WpTourRepository $repository)
    {
        $this->programService = $programService;
        $this->repository = $repository;
    }

    /**
     * Charge le programme (JSON) ou le construit depuis aj_tour_days si vide.
     */
    public function getProgram(int $postId): array
    {
        $post = WpPost::tours()->where('ID', $postId)->first();
        if (!$post) {
            return ['program_days' => []];
        }

        $raw = $post->getMeta(self::META_KEY);
        if ($raw !== null && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['program_days'])) {
                return $this->normalizeProgram($decoded);
            }
        }

        return $this->buildProgramFromDays($postId);
    }

    /**
     * Sauvegarde le programme en meta puis synchronise aj_tour_days et activités.
     */
    public function saveProgram(int $postId, array $program): void
    {
        $program = $this->normalizeProgram($program);
        $post = WpPost::tours()->where('ID', $postId)->firstOrFail();
        $post->setMeta(self::META_KEY, json_encode($program));
        $this->syncToTourDays($postId, $program);
    }

    /**
     * Recalcule day_number 1..N et nettoie les UID.
     */
    protected function normalizeProgram(array $program): array
    {
        $days = $program['program_days'] ?? [];
        if (!is_array($days)) {
            $days = [];
        }
        $out = [];
        foreach ($days as $i => $day) {
            $dayNumber = $i + 1;
            $items = $day['items'] ?? [];
            if (!is_array($items)) {
                $items = [];
            }
            $normalizedItems = [];
            foreach ($items as $j => $item) {
                $normalizedItems[] = [
                    'uid' => $item['uid'] ?? Str::uuid()->toString(),
                    'type' => $this->normalizeItemType($item['type'] ?? ''),
                    'ref_id' => (int) ($item['ref_id'] ?? 0),
                    'sort' => (int) ($item['sort'] ?? $j),
                    'meta_snapshot' => $item['meta_snapshot'] ?? null,
                ];
            }
            usort($normalizedItems, fn ($a, $b) => $a['sort'] <=> $b['sort']);
            foreach ($normalizedItems as $j => $it) {
                $normalizedItems[$j]['sort'] = $j;
            }
            $out[] = [
                'day_uid' => $day['day_uid'] ?? Str::uuid()->toString(),
                'day_number' => $dayNumber,
                'title' => $day['title'] ?? '',
                'notes' => $day['notes'] ?? '',
                'mode' => in_array($day['mode'] ?? '', ['free', 'program'], true) ? $day['mode'] : 'program',
                'items' => $normalizedItems,
            ];
        }
        return ['program_days' => $out];
    }

    protected function normalizeItemType(string $type): string
    {
        $allowed = [self::ITEM_TYPE_FLIGHT, self::ITEM_TYPE_TRANSFER, self::ITEM_TYPE_HOTEL, self::ITEM_TYPE_ACTIVITY];
        return in_array($type, $allowed, true) ? $type : self::ITEM_TYPE_ACTIVITY;
    }

    /**
     * Construit la structure programme depuis aj_tour_days + aj_tour_day_activities.
     */
    protected function buildProgramFromDays(int $tourId): array
    {
        $collection = $this->programService->loadProgram($tourId);
        $program_days = [];
        foreach ($collection as $index => $entry) {
            $day = $entry['day'];
            $activities = $entry['activities'];
            $items = [];
            foreach ($activities as $k => $da) {
                $items[] = [
                    'uid' => 'act-' . $da->id,
                    'type' => self::ITEM_TYPE_ACTIVITY,
                    'ref_id' => (int) $da->activity_id,
                    'sort' => $k,
                    'meta_snapshot' => [
                        'title' => $da->activity->title ?? null,
                        'is_included' => (bool) $da->is_included,
                        'is_mandatory' => (bool) $da->is_mandatory,
                    ],
                ];
            }
            $program_days[] = [
                'day_uid' => 'day-' . $day->id,
                'day_number' => (int) $day->day_number,
                'title' => $day->day_title ?? $day->title ?? '',
                'notes' => $day->notes ?? $day->description ?? '',
                'mode' => $day->mode ?? 'program',
                'items' => $items,
            ];
        }
        return ['program_days' => $program_days];
    }

    /**
     * Synchronise program_days vers aj_tour_days et items type=activity vers aj_tour_day_activities.
     */
    protected function syncToTourDays(int $tourId, array $program): void
    {
        $days = $program['program_days'] ?? [];
        $existingDays = TourDay::where('tour_id', $tourId)->get()->keyBy('id');
        $orderedDayIds = [];

        foreach ($days as $i => $dayRow) {
            $dayNumber = $i + 1;
            $dayUid = (string) ($dayRow['day_uid'] ?? '');
            $title = $dayRow['title'] ?? '';
            $notes = $dayRow['notes'] ?? '';
            $mode = in_array($dayRow['mode'] ?? '', ['free', 'program'], true) ? $dayRow['mode'] : 'program';

            $dayId = null;
            if (preg_match('/^day-(\d+)$/', $dayUid, $m)) {
                $dayId = (int) $m[1];
                if ($existingDays->has($dayId)) {
                    $day = $existingDays->get($dayId);
                    $day->day_number = $dayNumber;
                    $day->day_title = $title ?: null;
                    $day->notes = $notes ?: null;
                    $day->mode = $mode;
                    $day->title = $title ?: $day->title;
                    $day->save();
                    $orderedDayIds[] = $dayId;
                    continue;
                }
            }

            $day = TourDay::create([
                'tour_id' => $tourId,
                'day_number' => $dayNumber,
                'title' => $title ?: 'Jour ' . $dayNumber,
                'day_title' => $title ?: null,
                'notes' => $notes ?: null,
                'mode' => $mode,
            ]);
            $orderedDayIds[] = $day->id;
        }

        if (!empty($orderedDayIds)) {
            $this->programService->reorderAndRenumberDays($tourId, $orderedDayIds);
        }

        $daysAfterReorder = TourDay::where('tour_id', $tourId)->orderBy('day_number')->get();
        foreach ($days as $i => $dayRow) {
            $day = $daysAfterReorder[$i] ?? null;
            if (!$day) {
                continue;
            }
            $items = $dayRow['items'] ?? [];
            $activityRefs = [];
            foreach ($items as $item) {
                if (($item['type'] ?? '') === self::ITEM_TYPE_ACTIVITY && !empty($item['ref_id'])) {
                    $activityRefs[] = (int) $item['ref_id'];
                }
            }
            $current = TourDayActivity::where('day_id', $day->id)->get();
            foreach ($current as $da) {
                if (!in_array((int) $da->activity_id, $activityRefs, true) && !$da->is_mandatory) {
                    $this->programService->removeDayActivity($da->id);
                }
            }
            $current = TourDayActivity::where('day_id', $day->id)->get();
            $order = 0;
            foreach ($activityRefs as $refId) {
                $existing = $current->firstWhere('activity_id', $refId);
                if ($existing) {
                    $this->programService->updateDayActivity($existing->id, ['sort_order' => $order]);
                } else {
                    $this->programService->addActivityToDay($day->id, $refId, ['sort_order' => $order]);
                }
                $order++;
            }
        }

        $this->repository->updateTour($tourId, ['duration_day' => TourDay::where('tour_id', $tourId)->count()]);
    }

    public function countDays(int $postId): int
    {
        $program = $this->getProgram($postId);
        return count($program['program_days'] ?? []);
    }
}
