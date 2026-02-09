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

class AJTB_Laravel_Repository {

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
    public function __construct($tour_id) {
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
    private function table($short) {
        return ajtb_table('aj_' . $short);
    }

    /**
     * Check if table exists
     *
     * @param string $table_name Full table name
     * @return bool
     */
    private function table_exists($table_name) {
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
    public function get_all_data($session_token = null) {
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
    public function get_voyage_flights_from_db() {
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
    public function get_flights($session_token = null) {
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
    private function get_flights_internal() {
        $table_flights = $this->table('tour_flights');
        $table_airlines = $this->table('airlines');

        if (!$this->table_exists($table_flights)) {
            return [];
        }

        $airlines_exist = $this->table_exists($table_airlines);

        $sql = "SELECT f.*";
        if ($airlines_exist) {
            $sql .= ", a.name AS airline_name, a.iata_code AS airline_iata";
        }
        $sql .= " FROM {$table_flights} f";
        if ($airlines_exist) {
            $sql .= " LEFT JOIN {$table_airlines} a ON a.id = f.airline_id";
        }
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
            $flights[] = [
                'id' => (int) $r['id'],
                'tour_id' => (int) $r['tour_id'],
                'flight_type' => $r['flight_type'] ?? (isset($r['segment_number']) && (int) $r['segment_number'] === 1 ? 'outbound' : 'inbound'),
                'segment_number' => isset($r['segment_number']) ? (int) $r['segment_number'] : (($r['flight_type'] ?? '') === 'inbound' ? 2 : 1),
                'airline_id' => isset($r['airline_id']) ? (int) $r['airline_id'] : null,
                'airline_name' => $airlines_exist ? ($r['airline_name'] ?? '') : '',
                'airline_iata' => $airlines_exist ? ($r['airline_iata'] ?? '') : '',
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
     * Get flights keyed by flight_type (outbound, inbound) for attaching to days.
     * Outbound => Jour 1, Inbound => Dernier jour.
     *
     * @return array ['outbound' => flight row or null, 'inbound' => flight row or null]
     */
    private function get_tour_flights_for_days() {
        $table_flights = $this->table('tour_flights');
        $table_airlines = $this->table('airlines');
        if (!$this->table_exists($table_flights)) {
            return ['outbound' => null, 'inbound' => null];
        }
        $airlines_exist = $this->table_exists($table_airlines);
        $has_flight_type = $this->wpdb->get_var($this->wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'flight_type'", $table_flights));
        $sql = "SELECT f.*";
        if ($airlines_exist) {
            $sql .= ", a.name AS airline_name, a.iata_code AS airline_iata";
        }
        $sql .= " FROM {$table_flights} f";
        if ($airlines_exist) {
            $sql .= " LEFT JOIN {$table_airlines} a ON a.id = f.airline_id";
        }
        $sql .= " WHERE f.tour_id = %d ORDER BY " . ($has_flight_type ? "f.flight_type ASC" : "f.segment_number ASC");
        $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, $this->tour_id), ARRAY_A);
        $out = ['outbound' => null, 'inbound' => null];
        if (!$rows) {
            return $out;
        }
        foreach ($rows as $r) {
            $ft = isset($r['flight_type']) ? trim(strtolower((string) $r['flight_type'])) : '';
            if ($ft === '') {
                $ft = (int) ($r['segment_number'] ?? 0) === 1 ? 'outbound' : 'inbound';
            }
            if ($ft !== 'outbound' && $ft !== 'inbound') {
                continue;
            }
            $dep_date = !empty($r['depart_date']) ? $r['depart_date'] : null;
            $arr_date = !empty($r['arrive_date']) ? $r['arrive_date'] : null;
            $from_city = isset($r['from_city']) ? ($r['from_city'] ?? '') : ($r['depart_city'] ?? '');
            $to_city = isset($r['to_city']) ? ($r['to_city'] ?? '') : ($r['arrive_city'] ?? '');
            $row = [
                'id' => (int) $r['id'],
                'flight_type' => $ft,
                'from_city' => $from_city,
                'to_city' => $to_city,
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
                'notes' => $r['notes'] ?? null,
                'airline_name' => $airlines_exist ? ($r['airline_name'] ?? '') : '',
            ];
            $out[ $ft ] = $row;
        }
        return $out;
    }

    /**
     * Get transfers for this tour: arrival (day 1) and departure (last day).
     *
     * @return array ['arrival' => array|null, 'departure' => array|null]
     */
    private function get_tour_transfers() {
        $t = $this->table('tour_transfers');
        if (!$this->table_exists($t)) {
            return ['arrival' => null, 'departure' => null];
        }
        $rows = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$t} WHERE tour_id = %d",
            $this->tour_id
        ), ARRAY_A);
        $out = ['arrival' => null, 'departure' => null];
        if (!$rows) {
            return $out;
        }
        foreach ($rows as $r) {
            $dir = isset($r['direction']) ? trim(strtolower((string) $r['direction'])) : '';
            if (isset($r['image_id']) && $r['image_id'] && function_exists('wp_get_attachment_image_url')) {
                $r['image_url'] = wp_get_attachment_image_url((int) $r['image_id'], 'medium') ?: '';
            } else {
                $r['image_url'] = '';
            }
            if ($dir === 'arrival') {
                $out['arrival'] = $r;
            } elseif ($dir === 'departure') {
                $out['departure'] = $r;
            }
        }
        return $out;
    }

    /**
     * Get the main hotel for this tour (one per tour).
     *
     * @return array|null
     */
    private function get_tour_hotel() {
        $t = $this->table('tour_hotels');
        if (!$this->table_exists($t)) {
            return null;
        }
        $row = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$t} WHERE tour_id = %d LIMIT 1",
            $this->tour_id
        ), ARRAY_A);
        if ($row && isset($row['image_id']) && $row['image_id'] && function_exists('wp_get_attachment_image_url')) {
            $row['image_url'] = wp_get_attachment_image_url((int) $row['image_id'], 'medium') ?: '';
        } else {
            if ($row) {
                $row['image_url'] = '';
            }
        }
        return $row ?: null;
    }

    /**
     * Get all flights for this tour (no session filter). Used on front to show "Add this flight" for non-displayed segments.
     *
     * @return array Same structure as get_flights()
     */
    public function get_raw_flights() {
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
    private function apply_flight_selections(array $flights, $session_token) {
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
    public function get_activities_catalog() {
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
    public function get_activities_for_modal($exclude_ids = [], $search = '', $page = 1, $per_page = 12) {
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
        $select_list = implode(', ', array_map(function($col) {
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
                $image_url = wp_get_attachment_image_url((int) $row['image_id'], 'medium');
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
    public function has_any_data() {
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
    public function get_days($session_token = null) {
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
                    'meals' => $row['meals'] ?? '',
                    'accommodation' => $row['accommodation'] ?? '',
                    'image' => $row['image_url'] ?? '',
                    'mode' => isset($row['mode']) ? $row['mode'] : 'program',
                    'day_title' => isset($row['day_title']) ? $row['day_title'] : '',
                    'notes' => isset($row['notes']) ? $row['notes'] : '',
                    'activities' => [],
                ];
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
                                $image_url = wp_get_attachment_image_url((int) $ar['activity_image_id'], 'medium');
                            }
                            $days_by_id[$day_id]['activities'][] = [
                                'id' => (int) $ar['id'],
                                'activity_id' => (int) $ar['activity_id'],
                                'title' => !empty($ar['custom_title']) ? $ar['custom_title'] : ($ar['activity_title'] ?? ''),
                                'description' => !empty($ar['custom_description']) ? $ar['custom_description'] : ($ar['activity_description'] ?? ''),
                                'custom_price' => $ar['custom_price'] !== null ? (float) $ar['custom_price'] : null,
                                'base_price' => $ar['activity_base_price'] !== null ? (float) $ar['activity_base_price'] : null,
                                'image_url' => $image_url,
                                'start_time' => $ar['start_time'] ?? null,
                                'end_time' => $ar['end_time'] ?? null,
                                'is_mandatory' => !empty($ar['is_mandatory']),
                                'is_included' => !empty($ar['is_included']),
                            ];
                        }
                    }
                }
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

            // Attach flights: outbound => day 1, inbound => last day
            $flights_by_type = $this->get_tour_flights_for_days();
            $last_day_number = count($days_array) > 0 ? (int) $days_array[ count($days_array) - 1 ]['day'] : 0;

            $transfers = $this->get_tour_transfers();
            $hotel = $this->get_tour_hotel();

            foreach ($days_array as &$day) {
                $day['flight'] = null;
                $day['flight_return'] = null;
                $day['transfer'] = null;
                $day['transfer_return'] = null;
                $day['hotel'] = null;
                $day['hotel_checkout'] = false;
                $dn = (int) ($day['day'] ?? 0);
                // Jour 1 : vol aller, transfert arrivée, hôtel (check-in)
                if ($dn === 1) {
                    if (!empty($flights_by_type['outbound'])) {
                        $day['flight'] = $flights_by_type['outbound'];
                    }
                    if (!empty($transfers['arrival'])) {
                        $day['transfer'] = $transfers['arrival'];
                    }
                    if (!empty($hotel)) {
                        $day['hotel'] = $hotel;
                    }
                }
                // Dernier jour : hôtel (check-out), transfert retour, vol retour (peut être le même jour que J1)
                if ($last_day_number > 0 && $dn === $last_day_number) {
                    if (!empty($transfers['departure'])) {
                        $day['transfer_return'] = $transfers['departure'];
                    }
                    if (!empty($hotel)) {
                        if (empty($day['hotel'])) {
                            $day['hotel'] = $hotel;
                        }
                        $day['hotel_checkout'] = true;
                    }
                    if (!empty($flights_by_type['inbound'])) {
                        $day['flight_return'] = $flights_by_type['inbound'];
                    }
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
     * Get tour sections from aj_tour_sections
     *
     * @param string|null $section_key Optional specific section key
     * @return array
     */
    public function get_sections($section_key = null) {
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
    public function get_section($key) {
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
    public function get_pricing_rules($active_only = true) {
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
    public function get_current_pricing() {
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
     * Get inclusions from sections table
     *
     * @return array Array of inclusion items
     */
    public function get_inclusions() {
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
    public function get_exclusions() {
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
    public function get_overview() {
        return $this->get_section('overview');
    }

    /**
     * Log error for debugging
     *
     * @param string $method Method name
     * @param Exception $e Exception
     */
    private function log_error($method, $e) {
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
