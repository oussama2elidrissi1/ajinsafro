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
     * Table prefix (wp_prefix + laravel_prefix)
     * @var string
     */
    private $prefix;

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
        $this->prefix = $wpdb->prefix . AJTB_LARAVEL_PREFIX;
        $this->tour_id = (int) $tour_id;
    }

    /**
     * Get table name with prefix
     *
     * @param string $table Table name without prefix
     * @return string Full table name
     */
    private function table($table) {
        return $this->prefix . $table;
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
     * @return array
     */
    public function get_all_data() {
        return [
            'days' => $this->get_days(),
            'sections' => $this->get_sections(),
            'pricing_rules' => $this->get_pricing_rules(),
            'has_data' => $this->has_any_data(),
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
     *
     * @return array
     */
    public function get_days() {
        $table = $this->table('tour_days');

        if (!$this->table_exists($table)) {
            return [];
        }

        try {
            $results = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$table} WHERE tour_id = %d ORDER BY day_number ASC",
                    $this->tour_id
                ),
                ARRAY_A
            );

            if (!$results) {
                return [];
            }

            return array_map(function ($row) {
                return [
                    'id' => (int) $row['id'],
                    'day' => (int) $row['day_number'],
                    'title' => $row['title'] ?? '',
                    'description' => $row['description'] ?? '',
                    'meals' => $row['meals'] ?? '',
                    'accommodation' => $row['accommodation'] ?? '',
                    'image' => $row['image_url'] ?? '',
                ];
            }, $results);

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
