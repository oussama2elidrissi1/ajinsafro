<?php

namespace App\Services;

use App\Models\Voyage;
use App\Models\VoyageFlightOption;
use Illuminate\Support\Facades\DB;

class VoyageFlightOptionService
{
    /**
     * Get all flight options for a voyage, grouped by type.
     *
     * @return array{outbound: \Illuminate\Support\Collection, return: \Illuminate\Support\Collection, segment: \Illuminate\Support\Collection}
     */
    public function getOptionsForVoyage(int $voyageId): array
    {
        $options = VoyageFlightOption::where('voyage_id', $voyageId)
            ->with('airline')
            ->orderByRaw("CASE type WHEN 'outbound' THEN 1 WHEN 'return' THEN 2 ELSE 3 END")
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return [
            'outbound' => $options->where('type', VoyageFlightOption::TYPE_OUTBOUND)->values(),
            'return' => $options->where('type', VoyageFlightOption::TYPE_RETURN)->values(),
            'segment' => $options->where('type', VoyageFlightOption::TYPE_SEGMENT)->values(),
        ];
    }

    /**
     * Save flight options from request (flight_options array).
     * Replaces all options for this voyage. Syncs to WP after.
     */
    public function syncOptions(int $voyageId, array $items, ?int $lastDayNumber = null): void
    {
        $voyage = Voyage::find($voyageId);
        if (!$voyage) {
            return;
        }
        $lastDay = $lastDayNumber ?? 1;

        $idsToKeep = [];
        $sortOrder = 0;
        foreach ($items as $i => $row) {
            $type = $this->normalizeType($row['type'] ?? '');
            if ($type === '') {
                continue;
            }
            $dayNumber = isset($row['day_number']) && $row['day_number'] !== '' ? (int) $row['day_number'] : null;
            if ($type === VoyageFlightOption::TYPE_OUTBOUND && $dayNumber === null) {
                $dayNumber = 1;
            }
            if ($type === VoyageFlightOption::TYPE_RETURN && $dayNumber === null) {
                $dayNumber = $lastDay;
            }

            $filled = !empty($row['airline_id']) || !empty($row['from_city']) || !empty($row['to_city'])
                || !empty($row['departure_date']) || !empty($row['departure_datetime']) || !empty($row['flight_number']);
            if (!$filled && empty($row['id'])) {
                continue;
            }

            $data = [
                'voyage_id' => $voyageId,
                'type' => $type,
                'day_number' => $dayNumber,
                'from_city' => trim((string) ($row['from_city'] ?? '')),
                'to_city' => trim((string) ($row['to_city'] ?? '')),
                'depart_at' => $this->parseDateTime($row['departure_datetime'] ?? $row['departure_date'] ?? null),
                'arrive_at' => $this->parseDateTime($row['arrival_datetime'] ?? $row['arrival_date'] ?? null),
                'airline_id' => isset($row['airline_id']) && $row['airline_id'] !== '' ? (int) $row['airline_id'] : null,
                'flight_number' => isset($row['flight_number']) ? trim((string) $row['flight_number']) : null,
                'cabin' => in_array($row['cabin'] ?? '', ['economy', 'business', 'first'], true) ? $row['cabin'] : 'economy',
                'baggage_cabin_kg' => isset($row['baggage_cabin_kg']) && $row['baggage_cabin_kg'] !== '' ? (int) $row['baggage_cabin_kg'] : null,
                'baggage_checkin_kg' => isset($row['baggage_checkin_kg']) && $row['baggage_checkin_kg'] !== '' ? (int) $row['baggage_checkin_kg'] : null,
                'price' => isset($row['price']) && $row['price'] !== '' ? (float) $row['price'] : null,
                'is_included' => !empty($row['is_included']) && (string) $row['is_included'] !== '0',
                'is_optional' => !empty($row['is_optional']) && (string) $row['is_optional'] !== '0',
                'group_key' => $row['group_key'] ?? ($type === VoyageFlightOption::TYPE_SEGMENT ? 'SEGMENT_DAY_' . ($dayNumber ?? 0) : strtoupper($type)),
                'sort_order' => $sortOrder++,
                'is_tentative' => !empty($row['is_tentative']) && (string) $row['is_tentative'] === '1',
                'notes' => isset($row['notes']) ? trim((string) $row['notes']) : null,
            ];

            $existingId = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null;
            if ($existingId) {
                $opt = VoyageFlightOption::where('voyage_id', $voyageId)->where('id', $existingId)->first();
                if ($opt) {
                    if ($filled) {
                        $opt->update($data);
                        $idsToKeep[] = $opt->id;
                    } else {
                        $opt->delete();
                    }
                    continue;
                }
            }
            if ($filled) {
                $opt = VoyageFlightOption::create($data);
                $idsToKeep[] = $opt->id;
            }
        }

        VoyageFlightOption::where('voyage_id', $voyageId)->whereNotIn('id', $idsToKeep)->delete();

        if ($voyage->wp_post_id) {
            $this->syncOptionsToWp($voyageId, (int) $voyage->wp_post_id, $lastDay);
        }
    }

    /**
     * Sync all voyage_flight_options to WP aj_tour_flights (multi-row).
     * Allows multiple outbound/inbound/segment per tour (drops UNIQUE if present).
     */
    public function syncOptionsToWp(int $voyageId, int $wpPostId, int $lastDayNumber = 1): void
    {
        try {
            $wp = DB::connection('wp');
            $prefix = $wp->getTablePrefix();
            $table = 'aj_tour_flights';
            $fullTable = $prefix . $table;

            // Supprimer la contrainte UNIQUE(tour_id, flight_type) si elle existe pour autoriser plusieurs vols par type
            $this->dropUniqueTourFlightTypeIfExists($wp, $fullTable);

            $options = VoyageFlightOption::where('voyage_id', $voyageId)
                ->orderByRaw("CASE type WHEN 'outbound' THEN 1 WHEN 'return' THEN 2 ELSE 3 END")
                ->orderBy('sort_order')->orderBy('id')->get();

            \Log::info('VoyageFlightOptionService::syncOptionsToWp', [
                'voyage_id' => $voyageId,
                'wp_post_id' => $wpPostId,
                'options_count' => $options->count(),
                'types' => $options->pluck('type')->toArray(),
            ]);

            $wp->table($table)->where('tour_id', $wpPostId)->delete();

            foreach ($options as $opt) {
                try {
                    $ft = $opt->type === VoyageFlightOption::TYPE_RETURN ? 'inbound' : ($opt->type === VoyageFlightOption::TYPE_SEGMENT ? 'segment' : 'outbound');
                    $depAt = $opt->depart_at ? $opt->depart_at->format('Y-m-d H:i:s') : null;
                    $arrAt = $opt->arrive_at ? $opt->arrive_at->format('Y-m-d H:i:s') : null;

                    $row = [
                        'tour_id' => $wpPostId,
                        'flight_type' => $ft,
                        'airline_id' => $opt->airline_id,
                        'cabin_class' => $opt->cabin ?? 'economy',
                        'from_city' => $opt->from_city,
                        'to_city' => $opt->to_city,
                        'depart_date' => $depAt ? substr($depAt, 0, 10) : null,
                        'depart_time' => $depAt ? substr($depAt, 11, 8) : null,
                        'arrive_date' => $arrAt ? substr($arrAt, 0, 10) : null,
                        'arrive_time' => $arrAt ? substr($arrAt, 11, 8) : null,
                        'baggage_cabin_kg' => $opt->baggage_cabin_kg,
                        'baggage_checkin_kg' => $opt->baggage_checkin_kg,
                        'is_tentative' => (bool) $opt->is_tentative,
                        'notes' => $opt->notes,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];

                    if ($this->wpTableHasColumn($wp, $fullTable, 'sort_order')) {
                        $row['sort_order'] = $opt->sort_order ?? 0;
                    }
                    if ($this->wpTableHasColumn($wp, $fullTable, 'day_number')) {
                        // Segment sans jour → jour 1 pour affichage dans le programme
                        $row['day_number'] = $opt->day_number ?? ($opt->type === VoyageFlightOption::TYPE_SEGMENT ? 1 : null);
                    }
                    if ($this->wpTableHasColumn($wp, $fullTable, 'is_optional')) {
                        $row['is_optional'] = (bool) $opt->is_optional;
                    }
                    if ($this->wpTableHasColumn($wp, $fullTable, 'laravel_option_id')) {
                        $row['laravel_option_id'] = $opt->id;
                    }

                    $wp->table($table)->insert($row);
                } catch (\Throwable $e) {
                    \Log::warning('VoyageFlightOptionService::syncOptionsToWp row insert failed', [
                        'voyage_id' => $voyageId,
                        'wp_post_id' => $wpPostId,
                        'option_id' => $opt->id,
                        'type' => $opt->type,
                        'from_city' => $opt->from_city,
                        'to_city' => $opt->to_city,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('VoyageFlightOptionService::syncOptionsToWp failed', [
                'voyage_id' => $voyageId,
                'wp_post_id' => $wpPostId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function dropUniqueTourFlightTypeIfExists($wp, string $fullTable): void
    {
        try {
            $indexes = $wp->select("SHOW INDEX FROM `{$fullTable}` WHERE Column_name IN ('tour_id','flight_type')");
            $byKey = [];
            foreach ($indexes as $idx) {
                $keyName = $idx->Key_name ?? null;
                if (!$keyName) {
                    continue;
                }
                if (!isset($byKey[$keyName])) {
                    $byKey[$keyName] = ['non_unique' => (int) ($idx->Non_unique ?? 1), 'columns' => []];
                }
                $col = $idx->Column_name ?? null;
                if ($col && !in_array($col, $byKey[$keyName]['columns'], true)) {
                    $byKey[$keyName]['columns'][] = $col;
                }
            }
            foreach ($byKey as $keyName => $info) {
                if (($info['non_unique'] ?? 1) === 0
                    && in_array('tour_id', $info['columns'], true)
                    && in_array('flight_type', $info['columns'], true)) {
                    $wp->statement("ALTER TABLE `{$fullTable}` DROP INDEX `{$keyName}`");
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function wpTableHasColumn($wp, string $fullTable, string $column): bool
    {
        try {
            $r = $wp->selectOne(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                [$fullTable, $column]
            );
            return $r !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function normalizeType(string $type): string
    {
        $t = strtolower(trim($type));
        if (in_array($t, [VoyageFlightOption::TYPE_OUTBOUND, VoyageFlightOption::TYPE_RETURN, VoyageFlightOption::TYPE_SEGMENT], true)) {
            return $t;
        }
        if ($t === 'inbound') {
            return VoyageFlightOption::TYPE_RETURN;
        }
        return '';
    }

    private function parseDateTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            $d = \Carbon\Carbon::parse($value);
            return $d->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
