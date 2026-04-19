<?php
/**
 * Laravel Repository - Custom Tables Data
 *
 * Handles fetching tour data from Laravel custom tables
 * Tables: aj_tour_days, aj_tour_sections, aj_tour_pricing_rules
 *
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AJTB_Laravel_Repository
{

    /**
     * WordPress database instance
     * @var wpdb
     */
    private $wpdb;

    /**
     * Tour ID (WP post_id)
     * @var int
     */
    private $tour_id;

    /**
     * Constructor
     *
     * @param int $tour_id Tour/Post ID
     */
    public function __construct($tour_id)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tour_id = (int) $tour_id;
    }

    /**
     * Get table name (single prefix via ajtb_table)
     *
     * @param string $short Short name: tour_days, tour_day_activities, activities, tour_sections, tour_pricing_rules
     * @return string Full table name
     */
    private function table($short)
    {
        return ajtb_table('aj_' . $short);
    }

    /**
     * Check if table exists
     *
     * @param string $table_name Full table name
     * @return bool
     */
    private function table_exists($table_name)
    {
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare("SHOW TABLES LIKE %s", $table_name)
        );
        return $result === $table_name;
    }

    /**
     * Get all Laravel data for a tour
     *
     * @param string|null $session_token Optional; if set, client activity selections are applied to days
     * @return array
     */
    public function get_all_data($session_token = null)
    {
        return [
            'days' => $this->get_days($session_token),
            'sections' => $this->get_sections(),
            'pricing_rules' => $this->get_pricing_rules(),
            'activities_catalog' => $this->get_activities_catalog(),
            'flights' => $this->get_flights($session_token),
            'laravel_voyage_flights' => $this->get_voyage_flights_from_db(),
            'has_data' => $this->has_any_data(),
        ];
    }

    /**
     * Fetch outbound/inbound flights directly from DB (table aj_tour_flights).
     * Vol Aller = Jour 1, Vol Retour = Dernier jour. No API call.
     * Requires aj_tour_flights with tour_id = WP post ID and flight_type 'outbound' / 'inbound'.
     *
     * @return array { outbound: array|null, inbound: array|null }
     */
    public function get_voyage_flights_from_db()
    {
        return $this->get_tour_flights_for_days();
    }

    /**
     * Get flights for this tour from aj_tour_flights + aj_airlines.
     * If $session_token is set, apply client selections (aj_tour_flight_selections): default show only is_default=1,
     * then apply added/removed per session.
     *
     * @param string|null $session_token Optional; apply add/remove flight selections
     * @return array List of flight rows with airline_name, formatted dates, labels, etc.
     */
    public function get_flights($session_token = null)
    {
        $flights = $this->get_flights_internal();
        if (empty($flights)) {
            return [];
        }
        if (!empty($session_token)) {
            $flights = $this->apply_flight_selections($flights, $session_token);
        } else {
            $has_flight_type = !empty($flights) && isset($flights[0]['flight_type']);
            if (!$has_flight_type && count($flights) > 1) {
                $flights = array_values(array_filter($flights, function ($f) {
                    return !empty($f['is_default']);
                }));
            }
        }
        return $flights;
    }

    /**
     * Internal: fetch all flights for this tour (no session filter).
     *
     * @return array List of flight rows with airline_name, formatted dates, labels, etc.
     */
    private function get_flights_internal()
    {
        $table_flights = function_exists('ajtb_flights_table') ? ajtb_flights_table($this->tour_id) : $this->table('tour_flights');
        $table_airlines = $this->table('airlines');

        if (!$this->table_exists($table_flights)) {
            return [];
        }

        $airlines_exist = $this->table_exists($table_airlines);
        $has_departure_place_id = $this->wpdb->get_var($this->wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'departure_place_id'", $table_flights));
        $table_places = preg_replace('/aj_tour_flights$/', 'aj_travel_departure_places', $table_flights);
        $places_join = ($has_departure_place_id && $this->table_exists($table_places)) ? " LEFT JOIN {$table_places} dp ON dp.id = f.departure_place_id" : '';

        $sql = "SELECT f.*";
        if ($airlines_exist) {
            $sql .= ", a.name AS airline_name, a.iata_code AS airline_iata";
        }
        if ($places_join !== '') {
            $sql .= ", dp.name AS departure_place_name, dp.code AS departure_place_code";
        }
        $sql .= " FROM {$table_flights} f";
        if ($airlines_exist) {
            $sql .= " LEFT JOIN {$table_airlines} a ON a.id = f.airline_id";
        }
        $sql .= $places_join;
        $has_flight_type = $this->wpdb->get_var($this->wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'flight_type'", $table_flights));
        $order_by = $has_flight_type ? "f.flight_type ASC" : "f.segment_number ASC";
        $sql .= " WHERE f.tour_id = %d ORDER BY {$order_by}";
        $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, $this->tour_id), ARRAY_A);
        if (!$rows) {
            return [];
        }

        $flights = [];
        foreach ($rows as $r) {
            $dep_date = !empty($r['depart_date']) ? $r['depart_date'] : null;
            $arr_date = !empty($r['arrive_date']) ? $r['arrive_date'] : null;
            $from_city = isset($r['from_city']) ? ($r['from_city'] ?? '') : ($r['depart_city'] ?? '');
            $to_city = isset($r['to_city']) ? ($r['to_city'] ?? '') : ($r['arrive_city'] ?? '');
            $baggage_cabin = isset($r['baggage_cabin_kg']) && $r['baggage_cabin_kg'] !== null && $r['baggage_cabin_kg'] !== '' ? ((int) $r['baggage_cabin_kg']) . ' KGS' : (isset($r['cabin_baggage']) && $r['cabin_baggage'] !== '' ? $r['cabin_baggage'] : '—');
            $baggage_checkin = isset($r['baggage_checkin_kg']) && $r['baggage_checkin_kg'] !== null && $r['baggage_checkin_kg'] !== '' ? ((int) $r['baggage_checkin_kg']) . ' KGS' : (isset($r['checkin_baggage']) && $r['checkin_baggage'] !== '' ? $r['checkin_baggage'] : '—');
            $departure_place_id = isset($r['departure_place_id']) && $r['departure_place_id'] !== '' && $r['departure_place_id'] !== null ? (int) $r['departure_place_id'] : null;
            $departure_place_name = isset($r['departure_place_name']) ? trim((string) $r['departure_place_name']) : '';
            $departure_place_code = isset($r['departure_place_code']) ? trim((string) $r['departure_place_code']) : '';
            $flights[] = [
                'id' => (int) $r['id'],
                'tour_id' => (int) $r['tour_id'],
                'flight_type' => $r['flight_type'] ?? (isset($r['segment_number']) && (int) $r['segment_number'] === 1 ? 'outbound' : 'inbound'),
                'segment_number' => isset($r['segment_number']) ? (int) $r['segment_number'] : (($r['flight_type'] ?? '') === 'inbound' ? 2 : 1),
                'airline_id' => isset($r['airline_id']) ? (int) $r['airline_id'] : null,
                'airline_name' => $airlines_exist ? ($r['airline_name'] ?? '') : '',
                'airline_iata' => $airlines_exist ? ($r['airline_iata'] ?? '') : '',
                'departure_place_id' => $departure_place_id,
                'departure_place_name' => $departure_place_name,
                'departure_place_code' => $departure_place_code,
                'cabin_class' => $r['cabin_class'] ?? 'economy',
                'flight_number' => $r['flight_number'] ?? '',
                'from_city' => $from_city,
                'to_city' => $to_city,
                'depart_date' => $dep_date,
                'depart_time' => $r['depart_time'] ?? null,
                'depart_date_formatted' => $dep_date ? date('D, d M', strtotime($dep_date)) : '—',
                'depart_city' => $from_city,
                'depart_airport' => $r['depart_airport'] ?? '',
                'depart_label' => $from_city !== '' ? $from_city : '—',
                'arrive_date' => $arr_date,
                'arrive_time' => $r['arrive_time'] ?? null,
                'arrive_date_formatted' => $arr_date ? date('D, d M', strtotime($arr_date)) : '—',
                'arrive_city' => $to_city,
                'arrive_airport' => $r['arrive_airport'] ?? '',
                'arrive_label' => $to_city !== '' ? $to_city : '—',
                'baggage_cabin_kg' => isset($r['baggage_cabin_kg']) ? (int) $r['baggage_cabin_kg'] : null,
                'baggage_checkin_kg' => isset($r['baggage_checkin_kg']) ? (int) $r['baggage_checkin_kg'] : null,
                'cabin_baggage' => $baggage_cabin,
                'checkin_baggage' => $baggage_checkin,
                'is_tentative' => !empty($r['is_tentative']),
                'is_default' => !empty($r['is_default']),
                'notes' => $r['notes'] ?? null,
            ];
        }
        return $flights;
    }

    /**
     * Get all flights for this tour grouped by type (multi-vols: outbound, inbound, segment).
     * Compat: maps flight_type 'return' => 'inbound'. Segments use day_number (fallback 1 if null/0).
     *
     * @return array ['outbound' => [rows], 'inbound' => [rows], 'segments_by_day' => [day => [rows]]]
     */
    private function get_flights_grouped_for_days()
    {
        $table_flights = function_exists('ajtb_flights_table') ? ajtb_flights_table($this->tour_id) : $this->table('tour_flights');
        $table_airlines = $this->table('airlines');
        $out = ['outbound' => [], 'inbound' => [], 'segments_by_day' => []];
        if (!$this->table_exists($table_flights)) {
            if (function_exists('error_log')) {
                error_log('[AJTB flights] Table does not exist: ' . $table_flights . ' (tour_id=' . $this->tour_id . ')');
            }
            return $out;
        }
        $airlines_exist = $this->table_exists($table_airlines);
        $has_flight_type = $this->wpdb->get_var($this->wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'flight_type'", $table_flights));
        $has_day_number = $this->wpdb->get_var($this->wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'day_number'", $table_flights));
        $has_sort_order = $this->wpdb->get_var($this->wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'sort_order'", $table_flights));
        $has_departure_place_id = $this->wpdb->get_var($this->wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'departure_place_id'", $table_flights));
        $table_places = preg_replace('/aj_tour_flights$/', 'aj_travel_departure_places', $table_flights);
        $places_join = ($has_departure_place_id && $this->table_exists($table_places)) ? " LEFT JOIN {$table_places} dp ON dp.id = f.departure_place_id" : '';
        $order_parts = [];
        if ($has_flight_type) {
            $order_parts[] = "CASE WHEN LOWER(TRIM(COALESCE(f.flight_type,''))) = 'return' THEN 'inbound' ELSE LOWER(TRIM(COALESCE(f.flight_type,'outbound'))) END ASC";
        } else {
            $order_parts[] = "f.segment_number ASC";
        }
        if ($has_day_number) {
            $order_parts[] = "COALESCE(NULLIF(f.day_number, 0), 1) ASC";
        }
        if ($has_sort_order) {
            $order_parts[] = "f.sort_order ASC";
        }
        $order_parts[] = "f.id ASC";
        $order_by = implode(', ', $order_parts);

        $sql = "SELECT f.*";
        if ($airlines_exist) {
            $sql .= ", a.name AS airline_name, a.iata_code AS airline_iata";
        }
        if ($places_join !== '') {
            $sql .= ", dp.name AS departure_place_name, dp.code AS departure_place_code";
        }
        $sql .= " FROM {$table_flights} f";
        if ($airlines_exist) {
            $sql .= " LEFT JOIN {$table_airlines} a ON a.id = f.airline_id";
        }
        $sql .= $places_join;
        $sql .= " WHERE f.tour_id = %d ORDER BY " . $order_by;
        $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, $this->tour_id), ARRAY_A);
        $total_rows = is_array($rows) ? count($rows) : 0;
        if (function_exists('error_log')) {
            error_log('[AJTB flights] tour_id=' . $this->tour_id . ' table=' . $table_flights . ' total_rows=' . $total_rows);
        }
        if (!$rows) {
            return $out;
        }

        foreach ($rows as $r) {
            $ft = isset($r['flight_type']) ? trim(strtolower((string) $r['flight_type'])) : '';
            if ($ft === 'return') {
                $ft = 'inbound';
            }
            if ($ft === '') {
                $ft = (int) ($r['segment_number'] ?? 0) === 1 ? 'outbound' : 'inbound';
            }
            $day_num = $has_day_number && isset($r['day_number']) ? (int) $r['day_number'] : 0;
            if ($ft === 'segment') {
                $day_num = $day_num > 0 ? $day_num : 1;
                if (!isset($out['segments_by_day'][$day_num])) {
                    $out['segments_by_day'][$day_num] = [];
                }
            }

            $dep_date = !empty($r['depart_date']) ? $r['depart_date'] : null;
            $arr_date = !empty($r['arrive_date']) ? $r['arrive_date'] : null;
            $from_city = isset($r['from_city']) ? ($r['from_city'] ?? '') : ($r['depart_city'] ?? '');
            $to_city = isset($r['to_city']) ? ($r['to_city'] ?? '') : ($r['arrive_city'] ?? '');
            $departure_place_id = isset($r['departure_place_id']) && $r['departure_place_id'] !== '' && $r['departure_place_id'] !== null ? (int) $r['departure_place_id'] : null;
            $departure_place_name = isset($r['departure_place_name']) ? trim((string) $r['departure_place_name']) : '';
            $departure_place_code = isset($r['departure_place_code']) ? trim((string) $r['departure_place_code']) : '';
            $row = [
                'id' => (int) $r['id'],
                'flight_type' => $ft,
                'day_number' => $day_num,
                'departure_place_id' => $departure_place_id,
                'departure_place_name' => $departure_place_name,
                'departure_place_code' => $departure_place_code,
                'from_city' => $from_city,
                'to_city' => $to_city,
                'depart_label' => $from_city !== '' ? $from_city : '—',
                'arrive_label' => $to_city !== '' ? $to_city : '—',
                'depart_date' => $dep_date,
                'depart_time' => $r['depart_time'] ?? null,
                'depart_date_formatted' => $dep_date ? date('D, d M', strtotime($dep_date)) : '—',
                'arrive_date' => $arr_date,
                'arrive_time' => $r['arrive_time'] ?? null,
                'arrive_date_formatted' => $arr_date ? date('D, d M', strtotime($arr_date)) : '—',
                'cabin_class' => $r['cabin_class'] ?? 'economy',
                'baggage_cabin_kg' => isset($r['baggage_cabin_kg']) ? (int) $r['baggage_cabin_kg'] : null,
                'baggage_checkin_kg' => isset($r['baggage_checkin_kg']) ? (int) $r['baggage_checkin_kg'] : null,
                'cabin_baggage_display' => isset($r['baggage_cabin_kg']) && $r['baggage_cabin_kg'] !== '' && $r['baggage_cabin_kg'] !== null ? ((int) $r['baggage_cabin_kg']) . ' KGS' : '—',
                'checkin_baggage_display' => isset($r['baggage_checkin_kg']) && $r['baggage_checkin_kg'] !== '' && $r['baggage_checkin_kg'] !== null ? ((int) $r['baggage_checkin_kg']) . ' KGS' : '—',
                'is_tentative' => !empty($r['is_tentative']),
                'is_optional' => isset($r['is_optional']) ? !empty($r['is_optional']) : false,
                'laravel_option_id' => isset($r['laravel_option_id']) ? (int) $r['laravel_option_id'] : null,
                'notes' => $r['notes'] ?? null,
                'airline_name' => $airlines_exist ? ($r['airline_name'] ?? '') : '',
            ];
            if ($ft === 'outbound') {
                $out['outbound'][] = $row;
            } elseif ($ft === 'inbound') {
                $out['inbound'][] = $row;
            } else {
                $dn = ($ft === 'segment' ? $day_num : ($day_num > 0 ? $day_num : 1));
                if (!isset($out['segments_by_day'][$dn])) {
                    $out['segments_by_day'][$dn] = [];
                }
                $out['segments_by_day'][$dn][] = $row;
            }
        }

        if (function_exists('error_log')) {
            error_log('[AJTB flights grouped] tour_id=' . $this->tour_id . ' total_raw=' . $total_rows . ' outbound=' . count($out['outbound']) . ' inbound=' . count($out['inbound']) . ' segments_by_day keys=' . implode(',', array_keys($out['segments_by_day'])));
        }
        return $out;
    }

    /**
     * Get flights grouped for attaching to days (outbound / inbound / segments).
     * Returns arrays so template can loop over multiple vols. Used by get_voyage_flights_from_db and get_days.
     *
     * @return array ['outbound' => [rows], 'inbound' => [rows], 'segments_by_day' => [day => [rows]]]
     */
    private function get_tour_flights_for_days()
    {
        return $this->get_flights_grouped_for_days();
    }

    /**
     * Public API: get flights grouped by day for Programme du Circuit.
     * Reads from aj_tour_flights only. Returns structure: dayFlights[day] = list of flight rows.
     *
     * @param int $last_day_number Last day of the tour (inbound attached to this day).
     * @return array ['dayFlights' => [1=>[], 2=>[], ...], 'outbound'=>[], 'inbound'=>[], 'segments_by_day'=>[], '_debug'=>[]]
     */
    public function get_flights_for_program($last_day_number = 0)
    {
        $last_day_number = (int) $last_day_number;
        $grouped = $this->get_flights_grouped_for_days();
        $outbound = $grouped['outbound'] ?? [];
        $inbound = $grouped['inbound'] ?? [];
        $segments_by_day = $grouped['segments_by_day'] ?? [];
        $dayFlights = [];
        if ($last_day_number >= 1) {
            for ($d = 1; $d <= $last_day_number; $d++) {
                $dayFlights[$d] = [];
            }
        }
        foreach ($outbound as $row) {
            $day = 1;
            if (!isset($dayFlights[$day])) {
                $dayFlights[$day] = [];
            }
            $dayFlights[$day][] = $row;
        }
        foreach ($segments_by_day as $day => $rows) {
            if (!isset($dayFlights[$day])) {
                $dayFlights[$day] = [];
            }
            foreach ($rows as $row) {
                $dayFlights[$day][] = $row;
            }
        }
        if ($last_day_number > 0) {
            foreach ($inbound as $row) {
                $day = $last_day_number;
                if (!isset($dayFlights[$day])) {
                    $dayFlights[$day] = [];
                }
                $dayFlights[$day][] = $row;
            }
        }
        $debug = [
            'tour_id' => $this->tour_id,
            'total_outbound' => count($outbound),
            'total_inbound' => count($inbound),
            'segments_by_day_keys' => array_keys($segments_by_day),
            'dayFlights_keys' => array_keys($dayFlights),
        ];
        return [
            'dayFlights' => $dayFlights,
            'outbound' => $outbound,
            'inbound' => $inbound,
            'segments_by_day' => $segments_by_day,
            '_debug' => $debug,
        ];
    }

    /**
     * Debug: table name, existence, and flight counts for this tour. Use in template to diagnose "vols non affichés".
     *
     * @return array { table, table_exists, tour_id, total_rows, outbound, inbound, segments_keys }
     */
    public function get_flights_debug_info()
    {
        $table = function_exists('ajtb_flights_table') ? ajtb_flights_table($this->tour_id) : $this->table('tour_flights');
        $exists = $this->table_exists($table);
        $info = [
            'table' => $table,
            'table_exists' => $exists,
            'tour_id' => $this->tour_id,
        ];
        if (!$exists) {
            $info['total_rows'] = 0;
            $info['outbound'] = 0;
            $info['inbound'] = 0;
            $info['segments_keys'] = [];
            return $info;
        }
        $total = (int) $this->wpdb->get_var($this->wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE tour_id = %d", $this->tour_id));
        $grouped = $this->get_flights_grouped_for_days();
        $info['total_rows'] = $total;
        $info['outbound'] = count($grouped['outbound'] ?? []);
        $info['inbound'] = count($grouped['inbound'] ?? []);
        $info['segments_keys'] = array_keys($grouped['segments_by_day'] ?? []);
        return $info;
    }

    /**
     * Get transfers for this tour: arrival (day 1) and departure (last day).
     * Multi-row: returns arrays so templates can loop. Backward compat when no by_day_direction.
     *
     * @return array ['arrival' => array[], 'departure' => array[], 'by_day_direction' => array|null]
     */
    private function get_tour_transfers()
    {
        $grouped = $this->get_tour_transfers_grouped();
        $out = ['arrival' => [], 'departure' => []];
        if (!empty($grouped['by_day_direction'])) {
            foreach ($grouped['by_day_direction'] as $day => $dirs) {
                foreach (isset($dirs['arrival']) ? $dirs['arrival'] : [] as $r) {
                    $out['arrival'][] = $r;
                }
                foreach (isset($dirs['departure']) ? $dirs['departure'] : [] as $r) {
                    $out['departure'][] = $r;
                }
            }
        }
        return $out;
    }

    /**
     * Get transfers grouped by day_number and direction for programme display.
     * Returns ['by_day_direction' => [1 => ['arrival' => [...], 'departure' => [...]], ...]].
     * When day_number is missing: arrival -> day 1, departure -> last_day (from aj_tour_days count).
     *
     * @return array{by_day_direction: array<int, array{arrival: array, departure: array}}}
     */
    public function get_tour_transfers_grouped()
    {
        $t = $this->table('tour_transfers');
        if (!$this->table_exists($t)) {
            return ['by_day_direction' => []];
        }
        $last_day = 1;
        $days_table = $this->table('tour_days');
        if ($this->table_exists($days_table)) {
            $last_day = (int) $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$days_table} WHERE tour_id = %d",
                $this->tour_id
            ));
            if ($last_day < 1) {
                $last_day = 1;
            }
        }
        $has_day = $this->wpdb->get_var($this->wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'day_number'", $t));
        $has_sort = $this->wpdb->get_var($this->wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'sort_order'", $t));
        $order = ' ORDER BY ' . ($has_day ? 'COALESCE(day_number, 1) ASC, ' : '') . "CASE direction WHEN 'arrival' THEN 1 ELSE 2 END ASC, " . ($has_sort ? 'sort_order ASC, ' : '') . 'id ASC';
        $rows = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$t} WHERE tour_id = %d" . $order,
            $this->tour_id
        ), ARRAY_A);
        $by_day_direction = [];
        foreach ($rows ?: [] as $r) {
            if (isset($r['image_id']) && $r['image_id']) {
                if (function_exists('ajtb_get_attachment_image_url')) {
                    $r['image_url'] = ajtb_get_attachment_image_url((int) $r['image_id'], 'medium') ?: '';
                } elseif (function_exists('wp_get_attachment_image_url')) {
                    $r['image_url'] = wp_get_attachment_image_url((int) $r['image_id'], 'medium') ?: '';
                } else {
                    $r['image_url'] = '';
                }
            } else {
                $r['image_url'] = '';
            }
            $dir = isset($r['direction']) ? trim(strtolower((string) $r['direction'])) : '';
            if ($dir !== 'arrival' && $dir !== 'departure') {
                continue;
            }
            $day = (isset($r['day_number']) && $r['day_number'] !== '' && $r['day_number'] !== null) ? (int) $r['day_number'] : null;
            if ($day === null) {
                $day = ($dir === 'arrival') ? 1 : $last_day;
            }
            if ($day < 1) {
                $day = 1;
            }
            if (!isset($by_day_direction[$day])) {
                $by_day_direction[$day] = ['arrival' => [], 'departure' => []];
            }
            $by_day_direction[$day][$dir][] = $r;
        }
        return ['by_day_direction' => $by_day_direction];
    }

    /**
     * Get the main hotel for this tour (first; backward compat).
     *
     * @return array|null
     */
    private function get_tour_hotel()
    {
        $hotels = $this->get_tour_hotels();
        return isset($hotels[0]) ? $hotels[0] : null;
    }

    /**
     * Get all hotels for this tour (multi-row).
     *
     * @return array list of hotel rows (each with image_url)
     */
    private function get_tour_hotels()
    {
        $grouped = $this->get_tour_hotels_grouped();
        return isset($grouped['all']) ? $grouped['all'] : [];
    }

    /**
     * Get hotels grouped by day_number for programme display.
     * Returns ['by_day' => [1 => [row, row], 2 => [...]], 'all' => [flat list]].
     * When day_number column is missing, all rows fallback to day 1.
     *
     * @return array{by_day: array<int, array>, all: array}
     */
    public function get_tour_hotels_grouped()
    {
        $t = $this->table('tour_hotels');
        if (!$this->table_exists($t)) {
            return ['by_day' => [], 'all' => []];
        }
        $has_day = $this->wpdb->get_var($this->wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'day_number'", $t));
        $has_sort = $this->wpdb->get_var($this->wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'sort_order'", $t));
        $order = ' ORDER BY ' . ($has_day ? 'COALESCE(day_number, 1) ASC, ' : '') . ($has_sort ? 'sort_order ASC, ' : '') . 'id ASC';
        $rows = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$t} WHERE tour_id = %d" . $order,
            $this->tour_id
        ), ARRAY_A);
        $by_day = [];
        $all = [];
        foreach ($rows ?: [] as $row) {
            if (isset($row['image_id']) && $row['image_id']) {
                if (function_exists('ajtb_get_attachment_image_url')) {
                    $row['image_url'] = ajtb_get_attachment_image_url((int) $row['image_id'], 'medium') ?: '';
                } elseif (function_exists('wp_get_attachment_image_url')) {
                    $row['image_url'] = wp_get_attachment_image_url((int) $row['image_id'], 'medium') ?: '';
                } else {
                    $row['image_url'] = '';
                }
            } else {
                $row['image_url'] = '';
            }
            $day = (isset($row['day_number']) && $row['day_number'] !== '' && $row['day_number'] !== null) ? (int) $row['day_number'] : 1;
            if ($day < 1) {
                $day = 1;
            }
            if (!isset($by_day[$day])) {
                $by_day[$day] = [];
            }
            $by_day[$day][] = $row;
            $all[] = $row;
        }
        return ['by_day' => $by_day, 'all' => $all];
    }

    /**
     * Get all flights for this tour (no session filter). Used on front to show "Add this flight" for non-displayed segments.
     *
     * @return array Same structure as get_flights()
     */
    public function get_raw_flights()
    {
        return $this->get_flights_internal();
    }

    /**
     * Apply aj_tour_flight_selections to the flights list for a session.
     * Default: show only is_default=1. Then: added => include that flight; removed => exclude that flight.
     *
     * @param array $flights Full list from get_flights (before selection filter)
     * @param string $session_token
     * @return array Filtered list for display
     */
    private function apply_flight_selections(array $flights, $session_token)
    {
        $table_sel = $this->table('tour_flight_selections');
        if (!$this->table_exists($table_sel)) {
            if (count($flights) === 1) {
                return $flights;
            }
            return array_values(array_filter($flights, function ($f) {
                return !empty($f['is_default']);
            }));
        }

        $ids = array_column($flights, 'id');
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $sel = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT flight_id, action FROM {$table_sel} WHERE tour_id = %d AND session_token = %s AND flight_id IN ($placeholders) ORDER BY created_at DESC",
            array_merge([$this->tour_id, $session_token], $ids)
        ), ARRAY_A);

        $by_flight = [];
        foreach ($sel as $row) {
            $fid = (int) $row['flight_id'];
            if (!isset($by_flight[$fid])) {
                $by_flight[$fid] = $row['action'];
            }
        }

        $default_id = null;
        foreach ($flights as $f) {
            if (!empty($f['is_default'])) {
                $default_id = $f['id'];
                break;
            }
        }
        // If only one flight, treat it as default so it is shown unless user removed it
        if ($default_id === null && count($flights) === 1) {
            $default_id = $flights[0]['id'];
        }

        $out = [];
        foreach ($flights as $f) {
            $action = isset($by_flight[$f['id']]) ? $by_flight[$f['id']] : null;
            if ($f['id'] == $default_id) {
                if ($action !== 'removed') {
                    $out[] = $f;
                }
            } else {
                if ($action === 'added') {
                    $out[] = $f;
                }
            }
        }
        return $out;
    }

    /**
     * Get activities catalog (id, title) for "Add activity" dropdown on front.
     *
     * @return array [['id'=>, 'title'=>], ...]
     */
    public function get_activities_catalog()
    {
        $table = $this->table('activities');
        if (!$this->table_exists($table)) {
            return [];
        }
        $rows = $this->wpdb->get_results("SELECT id, title FROM {$table} ORDER BY title ASC", ARRAY_A);
        return $rows ?: [];
    }

    /**
     * Get activities for modal (with image, price, duration).
     * Used for modal activity picker.
     *
     * @param array $exclude_ids Activity IDs to exclude (already in day)
     * @param string $search Search term
     * @param int $page Page number (1-based)
     * @param int $per_page Items per page
     * @return array ['items' => [...], 'total' => int, 'page' => int, 'per_page' => int]
     */
    /**
     * Get activity IDs linked to this tour (present in programme: aj_tour_day_activities).
     * Used to restrict the frontend modal to only these activities.
     *
     * @return int[] Non-empty list of activity_id, or empty if none / table missing
     */
    public function get_tour_activity_ids()
    {
        $table_activities = $this->table('tour_day_activities');
        if (!$this->table_exists($table_activities)) {
            return [];
        }
        $ids = $this->wpdb->get_col(
            $this->wpdb->prepare(
                "SELECT DISTINCT activity_id FROM {$table_activities} WHERE tour_id = %d AND activity_id > 0",
                $this->tour_id
            )
        );
        return is_array($ids) ? array_map('intval', $ids) : [];
    }

    public function get_activities_for_modal($exclude_ids = [], $search = '', $page = 1, $per_page = 12, $allowed_activity_ids = null)
    {
        $table = $this->table('activities');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJTB get_activities_for_modal: table=' . $table . ', exists=' . ($this->table_exists($table) ? 'yes' : 'no'));
        }

        if (!$this->table_exists($table)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AJTB get_activities_for_modal: Table does not exist: ' . $table);
            }
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $per_page, 'total_pages' => 0];
        }

        $where = [];
        $params = [];

        if ($allowed_activity_ids !== null && is_array($allowed_activity_ids)) {
            $allowed_activity_ids = array_filter(array_map('intval', $allowed_activity_ids));
            if (empty($allowed_activity_ids)) {
                return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $per_page, 'total_pages' => 0];
            }
            $placeholders = implode(',', array_fill(0, count($allowed_activity_ids), '%d'));
            $where[] = "id IN ($placeholders)";
            $params = array_merge($params, array_values($allowed_activity_ids));
        }

        if (!empty($exclude_ids) && is_array($exclude_ids)) {
            $placeholders = implode(',', array_fill(0, count($exclude_ids), '%d'));
            $where[] = "id NOT IN ($placeholders)";
            $params = array_merge($params, $exclude_ids);
        }

        if (!empty($search)) {
            $where[] = "(title LIKE %s OR description LIKE %s)";
            $search_like = '%' . $this->wpdb->esc_like($search) . '%';
            $params[] = $search_like;
            $params[] = $search_like;
        }

        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total
        $count_query = "SELECT COUNT(*) FROM {$table}";
        if (!empty($where_sql)) {
            $count_query .= " {$where_sql}";
        }
        if (!empty($params)) {
            $count_query = $this->wpdb->prepare($count_query, $params);
        }
        $total = (int) $this->wpdb->get_var($count_query);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJTB get_activities_for_modal: total=' . $total . ', count_query=' . $count_query . ', last_error=' . ($this->wpdb->last_error ?: 'none'));
        }

        // Check which columns exist (to handle cases where migrations haven't run)
        $columns_check = $this->wpdb->get_results("SHOW COLUMNS FROM {$table}", ARRAY_A);
        $available_columns = [];
        if (is_array($columns_check)) {
            foreach ($columns_check as $col) {
                if (isset($col['Field'])) {
                    $available_columns[] = $col['Field'];
                }
            }
        }

        // Build SELECT list with only existing columns
        $select_cols = ['id', 'title', 'description', 'default_duration_minutes', 'location_text'];
        $optional_cols = ['image_id', 'base_price'];
        foreach ($optional_cols as $col) {
            if (in_array($col, $available_columns, true)) {
                $select_cols[] = $col;
            }
        }
        $select_list = implode(', ', array_map(function ($col) {
            return "`{$col}`";
        }, $select_cols));

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJTB get_activities_for_modal: available_columns=' . json_encode($available_columns) . ', select_list=' . $select_list);
        }

        // Get items with pagination
        $offset = ($page - 1) * $per_page;
        $query = "SELECT {$select_list} FROM {$table}";
        if (!empty($where_sql)) {
            $query .= " {$where_sql}";
        }
        $query .= " ORDER BY title ASC LIMIT %d OFFSET %d";

        // Always prepare with LIMIT/OFFSET params, merge with WHERE params if any
        $query_params = !empty($params) ? array_merge($params, [$per_page, $offset]) : [$per_page, $offset];
        $query = $this->wpdb->prepare($query, $query_params);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJTB get_activities_for_modal: query=' . $query . ', params=' . print_r($query_params, true));
        }

        $rows = $this->wpdb->get_results($query, ARRAY_A);

        if ($this->wpdb->last_error) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AJTB get_activities_for_modal: DB error=' . $this->wpdb->last_error . ', query=' . $query);
            }
            // Return empty result on error
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $per_page, 'total_pages' => 0];
        }

        if (!is_array($rows)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AJTB get_activities_for_modal: get_results returned non-array: ' . gettype($rows));
            }
            $rows = [];
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJTB get_activities_for_modal: rows count=' . count($rows));
        }

        // Format results with image URLs
        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $image_url = null;
            // Check if image_id column exists and has a value
            if (isset($row['image_id']) && !empty($row['image_id'])) {
                // Use reliable helper function that works with Laravel-uploaded images
                $image_url = function_exists('ajtb_get_attachment_image_url')
                    ? ajtb_get_attachment_image_url((int) $row['image_id'], 'medium')
                    : wp_get_attachment_image_url((int) $row['image_id'], 'medium');
            }

            $items[] = [
                'id' => isset($row['id']) ? (int) $row['id'] : 0,
                'title' => isset($row['title']) ? (string) $row['title'] : '',
                'description' => isset($row['description']) ? (string) $row['description'] : '',
                'image_url' => $image_url,
                'base_price' => isset($row['base_price']) && $row['base_price'] !== null ? (float) $row['base_price'] : null,
                'duration_minutes' => isset($row['default_duration_minutes']) && $row['default_duration_minutes'] !== null ? (int) $row['default_duration_minutes'] : null,
                'location_text' => isset($row['location_text']) ? (string) $row['location_text'] : '',
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => (int) ceil($total / $per_page),
        ];
    }

    /**
     * Check if any Laravel data exists for this tour
     *
     * @return bool
     */
    public function has_any_data()
    {
        $days = $this->get_days();
        $sections = $this->get_sections();

        return !empty($days) || !empty($sections);
    }

    /**
     * Get tour days/itinerary from aj_tour_days
     * With activities from aj_tour_day_activities + aj_activities when tables exist.
     * If $session_token is provided, client selections (aj_tour_activity_selections) are applied.
     *
     * @param string|null $session_token Optional; apply client add/remove selections
     * @return array
     */
    public function get_days($session_token = null)
    {
        $table_days = $this->table('tour_days');

        if (!$this->table_exists($table_days)) {
            return [];
        }

        try {
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$table_days} WHERE tour_id = %d ORDER BY day_number ASC",
                    $this->tour_id
                ),
                ARRAY_A
            );

            if (!$results) {
                return [];
            }

            $table_activities = $this->table('tour_day_activities');
            $table_catalog = $this->table('activities');
            $has_activities = $this->table_exists($table_activities) && $this->table_exists($table_catalog);

            $days_by_id = [];
            foreach ($results as $row) {
                $day_id = (int) $row['id'];
                $days_by_id[$day_id] = [
                    'id' => $day_id,
                    'day' => (int) $row['day_number'],
                    'title' => $row['title'] ?? '',
                    'description' => $row['description'] ?? '',
                    'content_html' => isset($row['content_html']) ? $row['content_html'] : '',
                    'meals' => $row['meals'] ?? '',
                    'accommodation' => $row['accommodation'] ?? '',
                    'image' => '',
                    'mode' => isset($row['mode']) ? $row['mode'] : 'program',
                    'day_title' => isset($row['day_title']) ? $row['day_title'] : '',
                    'notes' => isset($row['notes']) ? $row['notes'] : '',
                    'activities' => [],
                ];
            }

            // Enrich from CRUD detailed program (travel_program_days.content_html)
            // so front can display "Description detaillee" when available.
            $crud_day_content_map = $this->get_travel_program_day_content_map();
            if (!empty($crud_day_content_map)) {
                foreach ($days_by_id as &$day_row) {
                    $dn = isset($day_row['day']) ? (int) $day_row['day'] : 0;
                    if ($dn < 1 || empty($crud_day_content_map[$dn])) {
                        continue;
                    }
                    $crud = $crud_day_content_map[$dn];
                    if (!empty($crud['content_html'])) {
                        $day_row['content_html'] = (string) $crud['content_html'];
                    }
                    if (!empty($crud['description'])) {
                        $day_row['description'] = (string) $crud['description'];
                    }
                }
                unset($day_row);
            }

            if ($has_activities) {
                $day_ids = array_keys($days_by_id);
                if (!empty($day_ids)) {
                    $placeholders = implode(',', array_fill(0, count($day_ids), '%d'));
                    $query = $this->wpdb->prepare(
                        "SELECT da.id, da.day_id, da.activity_id, da.sort_order, da.is_included, da.is_mandatory, da.custom_title, da.custom_description, da.custom_price, da.start_time, da.end_time, " .
                        "a.title AS activity_title, a.description AS activity_description, a.image_id AS activity_image_id, a.base_price AS activity_base_price " .
                        "FROM {$table_activities} da " .
                        "INNER JOIN {$table_catalog} a ON a.id = da.activity_id " .
                        "WHERE da.tour_id = %d AND da.day_id IN ($placeholders) " .
                        "ORDER BY da.day_id ASC, da.sort_order ASC",
                        array_merge([$this->tour_id], $day_ids)
                    );
                    $activities_rows = $this->wpdb->get_results($query, ARRAY_A);
                    if ($activities_rows) {
                        foreach ($activities_rows as $ar) {
                            $day_id = (int) $ar['day_id'];
                            if (!isset($days_by_id[$day_id])) {
                                continue;
                            }
                            $image_url = null;
                            if (!empty($ar['activity_image_id'])) {
                                if (function_exists('ajtb_get_attachment_image_url')) {
                                    $image_url = ajtb_get_attachment_image_url((int) $ar['activity_image_id'], 'medium');
                                } else {
                                    $image_url = wp_get_attachment_image_url((int) $ar['activity_image_id'], 'medium');
                                }
                            }
                            $days_by_id[$day_id]['activities'][] = [
                                'id' => (int) $ar['id'],
                                'activity_id' => (int) $ar['activity_id'],
                                'title' => !empty($ar['custom_title']) ? $ar['custom_title'] : ($ar['activity_title'] ?? ''),
                                'description' => !empty($ar['custom_description']) ? $ar['custom_description'] : ($ar['activity_description'] ?? ''),
                                'custom_price' => $ar['custom_price'] !== null ? (float) $ar['custom_price'] : null,
                                'base_price' => $ar['activity_base_price'] !== null ? (float) $ar['activity_base_price'] : null,
                                'image_url' => $image_url,
                                'activity_image_id' => !empty($ar['activity_image_id']) ? (int) $ar['activity_image_id'] : null,
                                'start_time' => $ar['start_time'] ?? null,
                                'end_time' => $ar['end_time'] ?? null,
                                'is_mandatory' => !empty($ar['is_mandatory']),
                                'is_included' => !empty($ar['is_included']),
                            ];
                        }
                    }
                }
            }

            // Merge activities from CRUD tab "s-activities" (travel_day_items, source=voyage_activities_tab)
            // so V1 can display the same selected activities from edit-v2#s-activities.
            $voyage_tab_activities_by_day = $this->get_voyage_tab_activities_by_day();
            if (!empty($voyage_tab_activities_by_day) && !empty($days_by_id)) {
                $day_ids_by_number = [];
                foreach ($days_by_id as $existing_day_id => $existing_day_row) {
                    $existing_day_number = isset($existing_day_row['day']) ? (int) $existing_day_row['day'] : 0;
                    if ($existing_day_number < 1) {
                        $existing_day_number = 1;
                    }
                    if (!isset($day_ids_by_number[$existing_day_number])) {
                        $day_ids_by_number[$existing_day_number] = [];
                    }
                    $day_ids_by_number[$existing_day_number][] = (int) $existing_day_id;
                }
                $fallback_day_ids = !empty($day_ids_by_number) ? reset($day_ids_by_number) : [];
                if (!is_array($fallback_day_ids)) {
                    $fallback_day_ids = [];
                }

                foreach ($voyage_tab_activities_by_day as $tab_day_number => $tab_activities) {
                    if (!is_array($tab_activities) || empty($tab_activities)) {
                        continue;
                    }

                    $target_day_number = (int) $tab_day_number;
                    if ($target_day_number < 1) {
                        $target_day_number = 1;
                    }
                    $target_day_ids = $day_ids_by_number[$target_day_number] ?? $fallback_day_ids;
                    if (!is_array($target_day_ids) || empty($target_day_ids)) {
                        continue;
                    }

                    foreach ($target_day_ids as $target_day_id) {
                        if (!isset($days_by_id[$target_day_id])) {
                            continue;
                        }
                        if (!isset($days_by_id[$target_day_id]['activities']) || !is_array($days_by_id[$target_day_id]['activities'])) {
                            $days_by_id[$target_day_id]['activities'] = [];
                        }

                        $seen_activity_ids = [];
                        $seen_titles = [];
                        foreach ($days_by_id[$target_day_id]['activities'] as $existing_activity) {
                            $existing_activity_id = isset($existing_activity['activity_id']) ? (int) $existing_activity['activity_id'] : 0;
                            if ($existing_activity_id > 0) {
                                $seen_activity_ids[$existing_activity_id] = true;
                            }
                            $existing_title = strtolower(trim((string) ($existing_activity['title'] ?? '')));
                            if ($existing_title !== '') {
                                $seen_titles[$existing_title] = true;
                            }
                        }

                        foreach ($tab_activities as $tab_activity) {
                            if (!is_array($tab_activity)) {
                                continue;
                            }
                            $candidate_activity_id = isset($tab_activity['activity_id']) ? (int) $tab_activity['activity_id'] : 0;
                            $candidate_title = strtolower(trim((string) ($tab_activity['title'] ?? '')));

                            if ($candidate_activity_id > 0 && isset($seen_activity_ids[$candidate_activity_id])) {
                                continue;
                            }
                            if ($candidate_activity_id <= 0 && $candidate_title !== '' && isset($seen_titles[$candidate_title])) {
                                continue;
                            }

                            $days_by_id[$target_day_id]['activities'][] = $tab_activity;
                            if ($candidate_activity_id > 0) {
                                $seen_activity_ids[$candidate_activity_id] = true;
                            }
                            if ($candidate_title !== '') {
                                $seen_titles[$candidate_title] = true;
                            }
                        }
                    }
                }
            }

            foreach ($results as $row) {
                $day_id = (int) $row['id'];
                if (!isset($days_by_id[$day_id])) {
                    continue;
                }
                $day_image = '';
                if (!empty($row['image_url'])) {
                    $day_image = (string) $row['image_url'];
                } elseif (!empty($row['image_id'])) {
                    if (function_exists('ajtb_get_attachment_image_url')) {
                        $day_image = (string) ajtb_get_attachment_image_url((int) $row['image_id'], 'large');
                    } elseif (function_exists('wp_get_attachment_image_url')) {
                        $day_image = (string) wp_get_attachment_image_url((int) $row['image_id'], 'large');
                    }
                }
                $days_by_id[$day_id]['image'] = $day_image;
            }

            $days_array = array_values($days_by_id);
            if (!empty($session_token)) {
                $selections = new AJTB_Activity_Selections();
                $selections_list = $selections->get_selections($this->tour_id, $session_token);
                if (!empty($selections_list)) {
                    $days_array = $selections->apply_to_days($days_array, $selections_list, $this->tour_id);
                }
            }

            // Normalize notes to string (no null) for template stability
            foreach ($days_array as &$d) {
                if (!isset($d['notes']) || $d['notes'] === null) {
                    $d['notes'] = '';
                }
                $d['notes'] = (string) $d['notes'];
            }
            unset($d);

            // Attach flights: outbound => day 1, inbound => last day, segments by day_number
            $flights_grouped = $this->get_tour_flights_for_days();
            $last_day_number = count($days_array) > 0 ? (int) $days_array[count($days_array) - 1]['day'] : 0;
            $outbound = $flights_grouped['outbound'] ?? [];
            $inbound = $flights_grouped['inbound'] ?? [];
            $segments_by_day = $flights_grouped['segments_by_day'] ?? [];

            // Hotels and transfers by day (multi-day circuits)
            $hotels_grouped = $this->get_tour_hotels_grouped();
            $transfers_grouped = $this->get_tour_transfers_grouped();
            $hotels_by_day = $hotels_grouped['by_day'] ?? [];
            $transfers_by_day_direction = $transfers_grouped['by_day_direction'] ?? [];
            // Backward compat: when no by_day_direction, use flat arrival/departure on day 1 and last day
            if (empty($transfers_by_day_direction)) {
                $transfers_flat = $this->get_tour_transfers();
                if (!empty($transfers_flat['arrival'])) {
                    $transfers_by_day_direction[1] = ['arrival' => $transfers_flat['arrival'], 'departure' => []];
                }
                if (!empty($transfers_flat['departure']) && $last_day_number > 0) {
                    if (!isset($transfers_by_day_direction[$last_day_number])) {
                        $transfers_by_day_direction[$last_day_number] = ['arrival' => [], 'departure' => []];
                    }
                    $transfers_by_day_direction[$last_day_number]['departure'] = $transfers_flat['departure'];
                }
            }
            foreach ($days_array as &$day) {
                $day['flight'] = [];
                $day['flight_return'] = [];
                $day['transfer'] = [];
                $day['transfer_return'] = [];
                $day['hotel'] = null;
                $day['hotels'] = [];
                $day['hotel_checkout'] = false;
                $dn = (int) ($day['day'] ?? 0);
                if ($dn < 1) {
                    $dn = 1;
                }
                $segments_this_day = isset($segments_by_day[$dn]) ? $segments_by_day[$dn] : [];
                $day_transfers = isset($transfers_by_day_direction[$dn]) ? $transfers_by_day_direction[$dn] : ['arrival' => [], 'departure' => []];
                $day_hotels = isset($hotels_by_day[$dn]) ? $hotels_by_day[$dn] : [];

                $day['transfer'] = isset($day_transfers['arrival']) ? $day_transfers['arrival'] : [];
                $day['transfer_return'] = isset($day_transfers['departure']) ? $day_transfers['departure'] : [];
                $day['hotels'] = $day_hotels;
                $day['hotel'] = isset($day_hotels[0]) ? $day_hotels[0] : null;
                if ($last_day_number > 0 && $dn === $last_day_number && !empty($day_hotels)) {
                    $day['hotel_checkout'] = true;
                }

                if ($dn === 1) {
                    $day['flight'] = array_merge($outbound, $segments_this_day);
                } elseif ($last_day_number > 0 && $dn === $last_day_number) {
                    $day['flight'] = $segments_this_day;
                    $day['flight_return'] = $inbound;
                } else {
                    $day['flight'] = $segments_this_day;
                }
            }
            unset($day);

            return $days_array;
        } catch (Exception $e) {
            $this->log_error('get_days', $e);
            return [];
        }
    }

    /**
     * Get voyage activities saved in edit-v2 "s-activities" tab.
     * Data source: travel_day_items (type=activity, meta_json.source=voyage_activities_tab|NULL).
     *
     * @return array<int, array<int, array<string,mixed>>> keyed by day_number
     */
    private function get_voyage_tab_activities_by_day()
    {
        $voyage_table = $this->find_first_existing_table([
            'voyages',
            'aj_voyages',
            $this->wpdb->prefix . 'voyages',
            $this->wpdb->prefix . 'aj_voyages',
        ]);
        if ($voyage_table === '') {
            return [];
        }

        $day_items_table = $this->find_first_existing_table([
            'travel_day_items',
            'aj_travel_day_items',
            $this->wpdb->prefix . 'travel_day_items',
            $this->wpdb->prefix . 'aj_travel_day_items',
        ]);
        if ($day_items_table === '') {
            return [];
        }

        $has_col = function ($table, $column) {
            return (bool) $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                $table,
                $column
            ));
        };

        if (!$has_col($voyage_table, 'id') || !$has_col($voyage_table, 'wp_post_id')) {
            return [];
        }
        if (!$has_col($day_items_table, 'voyage_id') || !$has_col($day_items_table, 'type')) {
            return [];
        }

        $voyage_id = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT id FROM {$voyage_table} WHERE wp_post_id = %d ORDER BY id DESC LIMIT 1",
                $this->tour_id
            )
        );
        if ($voyage_id <= 0) {
            return [];
        }

        $has_day_number = $has_col($day_items_table, 'day_number');
        $has_title = $has_col($day_items_table, 'title');
        $has_details = $has_col($day_items_table, 'details');
        $has_included = $has_col($day_items_table, 'included');
        $has_price_delta = $has_col($day_items_table, 'price_delta_per_person');
        $has_options_json = $has_col($day_items_table, 'options_json');
        $has_meta_json = $has_col($day_items_table, 'meta_json');
        $has_sort_order = $has_col($day_items_table, 'sort_order');

        $select_parts = ['i.id'];
        $select_parts[] = $has_day_number ? 'i.day_number' : '1 AS day_number';
        $select_parts[] = $has_title ? 'i.title' : "'' AS title";
        $select_parts[] = $has_details ? 'i.details' : "'' AS details";
        $select_parts[] = $has_included ? 'i.included' : '1 AS included';
        $select_parts[] = $has_price_delta ? 'i.price_delta_per_person' : 'NULL AS price_delta_per_person';
        $select_parts[] = $has_options_json ? 'i.options_json' : 'NULL AS options_json';
        $select_parts[] = $has_meta_json ? 'i.meta_json' : 'NULL AS meta_json';
        $select_parts[] = $has_sort_order ? 'i.sort_order' : '0 AS sort_order';

        $order_by = $has_day_number ? 'i.day_number ASC' : 'i.id ASC';
        if ($has_sort_order) {
            $order_by .= ', i.sort_order ASC';
        }
        $order_by .= ', i.id ASC';

        $sql = "SELECT " . implode(', ', $select_parts) .
            " FROM {$day_items_table} i" .
            " WHERE i.voyage_id = %d AND i.type = %s" .
            " ORDER BY {$order_by}";

        try {
            $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, $voyage_id, 'activity'), ARRAY_A);
            if (!$rows) {
                return [];
            }

            $activity_ids = [];
            foreach ($rows as $row) {
                $options = $this->decode_json_assoc($row['options_json'] ?? null);
                $activity_id = isset($options['activity_id']) ? (int) $options['activity_id'] : 0;
                if ($activity_id > 0) {
                    $activity_ids[$activity_id] = true;
                }
            }

            $catalog_by_id = [];
            $activities_table = $this->table('activities');
            if ($this->table_exists($activities_table) && !empty($activity_ids)) {
                $activity_ids = array_values(array_map('intval', array_keys($activity_ids)));
                $activity_ids = array_values(array_filter($activity_ids, static function ($id) {
                    return $id > 0;
                }));

                if (!empty($activity_ids)) {
                    $placeholders = implode(',', array_fill(0, count($activity_ids), '%d'));
                    $catalog_has_col = function ($column) use ($activities_table, $has_col) {
                        return $has_col($activities_table, $column);
                    };
                    $catalog_select = ['id'];
                    if ($catalog_has_col('title')) {
                        $catalog_select[] = 'title';
                    }
                    if ($catalog_has_col('description')) {
                        $catalog_select[] = 'description';
                    }
                    if ($catalog_has_col('image_id')) {
                        $catalog_select[] = 'image_id';
                    }
                    if ($catalog_has_col('adult_price')) {
                        $catalog_select[] = 'adult_price';
                    }
                    if ($catalog_has_col('base_price')) {
                        $catalog_select[] = 'base_price';
                    }

                    $catalog_sql = "SELECT " . implode(', ', $catalog_select) . " FROM {$activities_table} WHERE id IN ({$placeholders})";
                    $catalog_rows = $this->wpdb->get_results($this->wpdb->prepare($catalog_sql, $activity_ids), ARRAY_A);
                    if (is_array($catalog_rows)) {
                        foreach ($catalog_rows as $catalog_row) {
                            $catalog_id = isset($catalog_row['id']) ? (int) $catalog_row['id'] : 0;
                            if ($catalog_id > 0) {
                                $catalog_by_id[$catalog_id] = $catalog_row;
                            }
                        }
                    }
                }
            }

            $by_day = [];
            foreach ($rows as $row) {
                $meta = $this->decode_json_assoc($row['meta_json'] ?? null);
                $source = isset($meta['source']) ? strtolower(trim((string) $meta['source'])) : '';
                if ($source !== '' && $source !== 'voyage_activities_tab') {
                    continue;
                }

                $options = $this->decode_json_assoc($row['options_json'] ?? null);
                $day_number = isset($row['day_number']) ? (int) $row['day_number'] : 1;
                if ($day_number < 1) {
                    $day_number = 1;
                }
                $activity_id = isset($options['activity_id']) ? (int) $options['activity_id'] : 0;
                $catalog = $activity_id > 0 && isset($catalog_by_id[$activity_id]) ? $catalog_by_id[$activity_id] : [];

                $image_url = '';
                if (!empty($catalog['image_id'])) {
                    if (function_exists('ajtb_get_attachment_image_url')) {
                        $image_url = (string) ajtb_get_attachment_image_url((int) $catalog['image_id'], 'medium');
                    } elseif (function_exists('wp_get_attachment_image_url')) {
                        $image_url = (string) wp_get_attachment_image_url((int) $catalog['image_id'], 'medium');
                    }
                }

                $title = trim((string) ($row['title'] ?? ''));
                if ($title === '' && !empty($options['title'])) {
                    $title = trim((string) $options['title']);
                }
                if ($title === '' && !empty($catalog['title'])) {
                    $title = trim((string) $catalog['title']);
                }
                if ($title === '') {
                    $title = 'Activite';
                }

                $description = trim((string) ($row['details'] ?? ''));
                if ($description === '' && !empty($options['description'])) {
                    $description = trim((string) $options['description']);
                }
                if ($description === '' && !empty($catalog['description'])) {
                    $description = trim((string) $catalog['description']);
                }

                $custom_price = null;
                if (isset($options['unit_price']) && $options['unit_price'] !== '' && $options['unit_price'] !== null) {
                    $custom_price = (float) $options['unit_price'];
                } elseif (isset($row['price_delta_per_person']) && $row['price_delta_per_person'] !== null && $row['price_delta_per_person'] !== '') {
                    $price_cents = (int) $row['price_delta_per_person'];
                    if ($price_cents !== 0) {
                        $custom_price = (float) ($price_cents / 100);
                    }
                }

                $base_price = null;
                if (isset($catalog['adult_price']) && $catalog['adult_price'] !== null && $catalog['adult_price'] !== '') {
                    $base_price = (float) $catalog['adult_price'];
                } elseif (isset($catalog['base_price']) && $catalog['base_price'] !== null && $catalog['base_price'] !== '') {
                    $base_price = (float) $catalog['base_price'];
                }

                if (!isset($by_day[$day_number])) {
                    $by_day[$day_number] = [];
                }

                $by_day[$day_number][] = [
                    'id' => isset($row['id']) ? (int) $row['id'] : 0,
                    'activity_id' => $activity_id,
                    'title' => $title,
                    'description' => $description,
                    'custom_price' => $custom_price,
                    'base_price' => $base_price,
                    'image_url' => $image_url,
                    'activity_image_id' => !empty($catalog['image_id']) ? (int) $catalog['image_id'] : null,
                    'start_time' => !empty($options['start_time']) ? (string) $options['start_time'] : null,
                    'end_time' => !empty($options['end_time']) ? (string) $options['end_time'] : null,
                    'is_mandatory' => false,
                    'is_included' => isset($row['included']) ? !empty($row['included']) : true,
                ];
            }

            return $by_day;
        } catch (Exception $e) {
            $this->log_error('get_voyage_tab_activities_by_day', $e);
            return [];
        }
    }

    /**
     * Decode JSON column (or passthrough array) to associative array.
     *
     * @param mixed $value
     * @return array
     */
    private function decode_json_assoc($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Return first existing table from candidates.
     *
     * @param array $candidates
     * @return string
     */
    private function find_first_existing_table(array $candidates)
    {
        $candidates = array_values(array_unique(array_filter(array_map('strval', $candidates))));
        foreach ($candidates as $candidate) {
            if ($candidate !== '' && $this->table_exists($candidate)) {
                return $candidate;
            }
        }
        return '';
    }

    /**
     * Fetch per-day detailed content from CRUD tables when present.
     * Joins voyages (wp_post_id) -> travel_program_days by day_number.
     *
     * @return array<int, array{content_html: string, description: string}>
     */
    private function get_travel_program_day_content_map()
    {
        $voyage_tables = array_values(array_unique([
            'voyages',
            'aj_voyages',
            $this->wpdb->prefix . 'voyages',
            $this->wpdb->prefix . 'aj_voyages',
        ]));

        $day_tables = array_values(array_unique([
            'travel_program_days',
            'aj_travel_program_days',
            $this->wpdb->prefix . 'travel_program_days',
            $this->wpdb->prefix . 'aj_travel_program_days',
        ]));

        $voyage_table = '';
        foreach ($voyage_tables as $t) {
            if (is_string($t) && $t !== '' && $this->table_exists($t)) {
                $voyage_table = $t;
                break;
            }
        }
        if ($voyage_table === '') {
            return [];
        }

        $day_table = '';
        foreach ($day_tables as $t) {
            if (is_string($t) && $t !== '' && $this->table_exists($t)) {
                $day_table = $t;
                break;
            }
        }
        if ($day_table === '') {
            return [];
        }

        $has_col = function ($table, $column) {
            return (bool) $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                $table,
                $column
            ));
        };

        if (!$has_col($voyage_table, 'id') || !$has_col($voyage_table, 'wp_post_id')) {
            return [];
        }
        if (!$has_col($day_table, 'day_number') || !$has_col($day_table, 'content_html')) {
            return [];
        }

        $fk_col = $has_col($day_table, 'voyage_id')
            ? 'voyage_id'
            : ($has_col($day_table, 'travel_id') ? 'travel_id' : '');
        if ($fk_col === '') {
            return [];
        }

        $has_description = $has_col($day_table, 'description');

        try {
            $sql = "SELECT d.day_number, d.content_html";
            if ($has_description) {
                $sql .= ", d.description";
            } else {
                $sql .= ", '' AS description";
            }
            $sql .= " FROM {$day_table} d";
            $sql .= " INNER JOIN {$voyage_table} v ON v.id = d.{$fk_col}";
            $sql .= " WHERE v.wp_post_id = %d";
            $sql .= " ORDER BY d.day_number ASC";

            $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, $this->tour_id), ARRAY_A);
            if (!$rows) {
                return [];
            }

            $map = [];
            foreach ($rows as $row) {
                $day_number = isset($row['day_number']) ? (int) $row['day_number'] : 0;
                if ($day_number < 1) {
                    continue;
                }
                $content_html = isset($row['content_html']) ? trim((string) $row['content_html']) : '';
                $description = isset($row['description']) ? trim((string) $row['description']) : '';
                if ($content_html === '' && $description === '') {
                    continue;
                }
                $map[$day_number] = [
                    'content_html' => $content_html,
                    'description' => $description,
                ];
            }

            return $map;
        } catch (Exception $e) {
            $this->log_error('get_travel_program_day_content_map', $e);
            return [];
        }
    }

    /**
     * Get tour sections from aj_tour_sections
     *
     * @param string|null $section_key Optional specific section key
     * @return array
     */
    public function get_sections($section_key = null)
    {
        $table = $this->table('tour_sections');

        if (!$this->table_exists($table)) {
            return [];
        }

        try {
            $sql = "SELECT * FROM {$table} WHERE tour_id = %d";
            $params = [$this->tour_id];

            if ($section_key) {
                $sql .= " AND section_key = %s";
                $params[] = $section_key;
            }

            $sql .= " ORDER BY sort_order ASC";

            $results = $this->wpdb->get_results(
                $this->wpdb->prepare($sql, $params),
                ARRAY_A
            );

            if (!$results) {
                return [];
            }

            // If specific key requested, return single section content
            if ($section_key && !empty($results)) {
                return $results[0]['content'] ?? '';
            }

            // Return all sections as associative array
            $sections = [];
            foreach ($results as $row) {
                $key = $row['section_key'];
                $sections[$key] = [
                    'id' => (int) $row['id'],
                    'key' => $key,
                    'content' => $row['content'] ?? '',
                    'sort_order' => (int) $row['sort_order'],
                ];
            }

            return $sections;

        } catch (Exception $e) {
            $this->log_error('get_sections', $e);
            return [];
        }
    }

    /**
     * Get specific section content
     *
     * @param string $key Section key (overview, inclusions, exclusions, etc.)
     * @return string Section content or empty string
     */
    public function get_section($key)
    {
        $table = $this->table('tour_sections');

        if (!$this->table_exists($table)) {
            return '';
        }

        try {
            $result = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT content FROM {$table} WHERE tour_id = %d AND section_key = %s LIMIT 1",
                    $this->tour_id,
                    $key
                )
            );

            return $result ?? '';

        } catch (Exception $e) {
            $this->log_error('get_section', $e);
            return '';
        }
    }

    /**
     * Get pricing rules from aj_tour_pricing_rules
     *
     * @param bool $active_only Only return active rules
     * @return array
     */
    public function get_pricing_rules($active_only = true)
    {
        $table = $this->table('tour_pricing_rules');

        if (!$this->table_exists($table)) {
            return [];
        }

        try {
            $sql = "SELECT * FROM {$table} WHERE tour_id = %d";
            $params = [$this->tour_id];

            if ($active_only) {
                $sql .= " AND is_active = 1";
            }

            $sql .= " ORDER BY start_date ASC";

            $results = $this->wpdb->get_results(
                $this->wpdb->prepare($sql, $params),
                ARRAY_A
            );

            if (!$results) {
                return [];
            }

            return array_map(function ($row) {
                return [
                    'id' => (int) $row['id'],
                    'season_name' => $row['season_name'] ?? '',
                    'start_date' => $row['start_date'] ?? '',
                    'end_date' => $row['end_date'] ?? '',
                    'adult_price' => (float) $row['adult_price'],
                    'child_price' => (float) $row['child_price'],
                    'infant_price' => (float) $row['infant_price'],
                    'is_active' => (bool) $row['is_active'],
                ];
            }, $results);

        } catch (Exception $e) {
            $this->log_error('get_pricing_rules', $e);
            return [];
        }
    }

    /**
     * Get current active pricing rule (based on date)
     *
     * @return array|null Current pricing rule or null
     */
    public function get_current_pricing()
    {
        $rules = $this->get_pricing_rules(true);

        if (empty($rules)) {
            return null;
        }

        $today = date('Y-m-d');

        foreach ($rules as $rule) {
            if (!empty($rule['start_date']) && !empty($rule['end_date'])) {
                if ($today >= $rule['start_date'] && $today <= $rule['end_date']) {
                    return $rule;
                }
            }
        }

        // Return first rule as default if no date match
        return $rules[0] ?? null;
    }

    /**
     * Get departure places configured in Availability tab (Starting from).
     * Supports prefixed schemas inferred from flights table.
     *
     * @param bool $active_only Only return active places when the column exists.
     * @return array<int, array<string, mixed>>
     */
    public function get_departure_places($active_only = true)
    {
        $candidates = [];
        $candidates[] = $this->table('travel_departure_places');
        $candidates[] = $this->wpdb->prefix . 'aj_travel_departure_places';

        $flights_table = function_exists('ajtb_flights_table') ? ajtb_flights_table($this->tour_id) : $this->table('tour_flights');
        if (is_string($flights_table) && preg_match('/^(.*)aj_tour_flights$/', $flights_table, $m)) {
            $alt_prefix = $m[1];
            $candidates[] = $alt_prefix . 'aj_travel_departure_places';
        }

        $candidates = array_values(array_unique(array_filter(array_map('strval', $candidates))));
        $table = '';
        foreach ($candidates as $candidate) {
            if ($this->table_exists($candidate)) {
                $table = $candidate;
                break;
            }
        }

        if ($table === '') {
            return [];
        }

        $has_col = function ($column) use ($table) {
            return (bool) $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                $table,
                $column
            ));
        };

        $tour_col = $has_col('travel_id') ? 'travel_id' : ($has_col('tour_id') ? 'tour_id' : '');
        $name_col = $has_col('name') ? 'name' : ($has_col('title') ? 'title' : '');
        if ($tour_col === '' || $name_col === '') {
            $table = '';
        }

        $out = [];

        if ($table !== '') {
            $code_col = $has_col('code') ? 'code' : '';
            $active_col = $has_col('is_active') ? 'is_active' : ($has_col('active') ? 'active' : '');
            $sort_col = $has_col('sort_order') ? 'sort_order' : '';

            try {
                $sql = "SELECT id, {$name_col} AS place_name";
                if ($code_col !== '') {
                    $sql .= ", {$code_col} AS place_code";
                }
                if ($sort_col !== '') {
                    $sql .= ", {$sort_col} AS sort_order";
                }
                if ($active_col !== '') {
                    $sql .= ", {$active_col} AS is_active_value";
                }
                $sql .= " FROM {$table} WHERE {$tour_col} = %d";

                $params = [$this->tour_id];
                if ($active_only && $active_col !== '') {
                    $sql .= " AND {$active_col} = 1";
                }

                if ($sort_col !== '') {
                    $sql .= " ORDER BY {$sort_col} ASC, id ASC";
                } else {
                    $sql .= " ORDER BY id ASC";
                }

                $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, $params), ARRAY_A);
                if (is_array($rows) && !empty($rows)) {
                    foreach ($rows as $row) {
                        $name = isset($row['place_name']) ? trim((string) $row['place_name']) : '';
                        if ($name === '') {
                            continue;
                        }

                        $code = isset($row['place_code']) ? trim((string) $row['place_code']) : '';
                        $is_active = true;
                        if (array_key_exists('is_active_value', $row)) {
                            $is_active = !empty($row['is_active_value']);
                        }

                        $out[] = [
                            'id' => isset($row['id']) ? (int) $row['id'] : 0,
                            'name' => $name,
                            'code' => $code,
                            'is_active' => $is_active,
                        ];
                    }
                }
            } catch (Exception $e) {
                $this->log_error('get_departure_places_wp', $e);
            }
        }

        // Fallback to Laravel admin tables when WP sync table has no rows.
        if (empty($out)) {
            try {
                $table_voyages = 'voyages';
                $table_places = 'voyage_departure_places';
                if ($this->table_exists($table_voyages) && $this->table_exists($table_places)) {
                    $active_clause = $active_only ? " AND p.is_active = 1" : "";
                    $sql = "
                        SELECT p.id, p.name AS place_name, p.code AS place_code, p.is_active AS is_active_value
                        FROM {$table_places} p
                        INNER JOIN {$table_voyages} v ON v.id = p.voyage_id
                        WHERE v.wp_post_id = %d{$active_clause}
                        ORDER BY p.sort_order ASC, p.id ASC
                    ";
                    $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, $this->tour_id), ARRAY_A);
                    if (is_array($rows) && !empty($rows)) {
                        foreach ($rows as $row) {
                            $name = isset($row['place_name']) ? trim((string) $row['place_name']) : '';
                            if ($name === '') {
                                continue;
                            }
                            $out[] = [
                                'id' => isset($row['id']) ? (int) $row['id'] : 0,
                                'name' => $name,
                                'code' => isset($row['place_code']) ? trim((string) $row['place_code']) : '',
                                'is_active' => array_key_exists('is_active_value', $row) ? !empty($row['is_active_value']) : true,
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                $this->log_error('get_departure_places_laravel_fallback', $e);
            }
        }

        // Ensure unique names to avoid duplicate options due to mixed sources.
        if (!empty($out)) {
            $seen = [];
            $unique = [];
            foreach ($out as $row) {
                $key = strtolower(trim((string) ($row['name'] ?? '')));
                if ($key === '' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $unique[] = $row;
            }
            $out = $unique;
        }

        return $out;
    }

    /**
     * Get departure dates configured in CRUD (Dates de depart).
     * Supports both travel_id and tour_id schemas and optional stock/price columns.
     *
     * @param bool $active_only Only return active dates when the column exists.
     * @return array<int, array<string, mixed>>
     */
    public function get_departure_dates($active_only = true)
    {
        $candidates = [];
        $candidates[] = $this->table('travel_dates');
        $candidates[] = $this->table('tour_dates');

        $flights_table = function_exists('ajtb_flights_table') ? ajtb_flights_table($this->tour_id) : $this->table('tour_flights');
        if (is_string($flights_table) && preg_match('/^(.*)aj_tour_flights$/', $flights_table, $m)) {
            $alt_prefix = $m[1];
            $candidates[] = $alt_prefix . 'aj_travel_dates';
            $candidates[] = $alt_prefix . 'aj_tour_dates';
        }

        $candidates = array_values(array_unique(array_filter(array_map('strval', $candidates))));
        $table = '';
        foreach ($candidates as $candidate) {
            if ($this->table_exists($candidate)) {
                $table = $candidate;
                break;
            }
        }

        if ($table === '') {
            return [];
        }

        $has_col = function ($column) use ($table) {
            return (bool) $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                $table,
                $column
            ));
        };

        $date_col = $has_col('date') ? 'date' : ($has_col('start_date') ? 'start_date' : '');
        $tour_col = $has_col('travel_id') ? 'travel_id' : ($has_col('tour_id') ? 'tour_id' : '');

        if ($date_col === '' || $tour_col === '') {
            return [];
        }

        $stock_col = $has_col('stock') ? 'stock' : ($has_col('places') ? 'places' : ($has_col('available_seats') ? 'available_seats' : ($has_col('seats_available') ? 'seats_available' : '')));
        $price_col = $has_col('specific_price') ? 'specific_price' : ($has_col('adult_price') ? 'adult_price' : ($has_col('price') ? 'price' : ''));
        $active_col = $has_col('is_active') ? 'is_active' : ($has_col('active') ? 'active' : '');
        $place_id_col = $has_col('departure_place_id') ? 'departure_place_id' : '';

        try {
            $sql = "SELECT id, {$date_col} AS departure_date";
            if ($stock_col !== '') {
                $sql .= ", {$stock_col} AS stock_value";
            }
            if ($price_col !== '') {
                $sql .= ", {$price_col} AS price_value";
            }
            if ($active_col !== '') {
                $sql .= ", {$active_col} AS is_active_value";
            }
            if ($place_id_col !== '') {
                $sql .= ", {$place_id_col} AS departure_place_id";
            }
            $sql .= " FROM {$table} WHERE {$tour_col} = %d";

            $params = [$this->tour_id];
            if ($active_only && $active_col !== '') {
                $sql .= " AND {$active_col} = 1";
            }

            $sql .= " ORDER BY {$date_col} ASC, id ASC";

            $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, $params), ARRAY_A);
            if (!$rows) {
                return [];
            }

            $out = [];
            foreach ($rows as $row) {
                $raw_date = isset($row['departure_date']) ? trim((string) $row['departure_date']) : '';
                if ($raw_date === '') {
                    continue;
                }

                $stock = null;
                if (array_key_exists('stock_value', $row) && $row['stock_value'] !== null && $row['stock_value'] !== '') {
                    $stock = (int) $row['stock_value'];
                }

                $specific_price = null;
                if (array_key_exists('price_value', $row) && $row['price_value'] !== null && $row['price_value'] !== '') {
                    $specific_price = (float) $row['price_value'];
                }

                $is_active = true;
                if (array_key_exists('is_active_value', $row)) {
                    $is_active = !empty($row['is_active_value']);
                }

                $out[] = [
                    'id' => isset($row['id']) ? (int) $row['id'] : 0,
                    'date' => $raw_date,
                    'stock' => $stock,
                    'specific_price' => $specific_price,
                    'is_active' => $is_active,
                    'departure_place_id' => isset($row['departure_place_id']) && $row['departure_place_id'] !== '' ? (int) $row['departure_place_id'] : null,
                ];
            }

            return $out;
        } catch (Exception $e) {
            $this->log_error('get_departure_dates', $e);
            return [];
        }
    }

    /**
     * Get inclusions from sections table
     *
     * @return array Array of inclusion items
     */
    public function get_inclusions()
    {
        $content = $this->get_section('inclusions');

        if (empty($content)) {
            return [];
        }

        return ajtb_parse_list_content($content);
    }

    /**
     * Get exclusions from sections table
     *
     * @return array Array of exclusion items
     */
    public function get_exclusions()
    {
        $content = $this->get_section('exclusions');

        if (empty($content)) {
            return [];
        }

        return ajtb_parse_list_content($content);
    }

    /**
     * Get overview from sections table
     *
     * @return string Overview content
     */
    public function get_overview()
    {
        return $this->get_section('overview');
    }

    /**
     * Log error for debugging
     *
     * @param string $method Method name
     * @param Exception $e Exception
     */
    private function log_error($method, $e)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'AJTB Laravel Repository [%s]: %s in %s on line %d',
                $method,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        }
    }
}
