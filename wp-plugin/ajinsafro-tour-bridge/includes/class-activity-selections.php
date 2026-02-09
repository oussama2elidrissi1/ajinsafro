<?php
/**
 * Activity Selections - Client add/remove optional activities
 *
 * Reads/writes aj_tour_activity_selections. Never modifies aj_tour_day_activities.
 *
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJTB_Activity_Selections {

    const COOKIE_NAME = 'aj_tour_session';
    const COOKIE_DAYS = 30;

    /** @var wpdb */
    private $wpdb;

    /** @var string */
    private $table;

    /** @var string */
    private $table_activities;

    /** @var string */
    private $table_catalog;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = ajtb_table('aj_tour_activity_selections');
        $this->table_activities = ajtb_table('aj_tour_day_activities');
        $this->table_catalog = ajtb_table('aj_activities');
    }

    /**
     * Get or create session token (cookie). Used for non-logged-in users.
     *
     * @return string
     */
    public function get_session_token() {
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }
        $token = isset($_COOKIE[self::COOKIE_NAME]) ? sanitize_text_field($_COOKIE[self::COOKIE_NAME]) : '';
        if (strlen($token) < 16) {
            $token = bin2hex(random_bytes(16));
            $set = setcookie(
                self::COOKIE_NAME,
                $token,
                time() + (self::COOKIE_DAYS * DAY_IN_SECONDS),
                '/',
                '',
                is_ssl(),
                true
            );
            if (!$set) {
                // Fallback to transient key by IP if cookie fails
                $token = 'ip_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
            }
        }
        return $token;
    }

    /**
     * Get selections for a tour and session.
     *
     * @param int $tour_id
     * @param string $session_token
     * @return array List of ['day_id' => int, 'activity_id' => int, 'action' => 'added'|'removed']
     */
    public function get_selections($tour_id, $session_token) {
        if (empty($session_token)) {
            return [];
        }
        $table = $this->table;
        if ($this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $table)) != $table) {
            return [];
        }
        $tour_id = (int) $tour_id;
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT day_id, activity_id, action FROM $table WHERE tour_id = %d AND session_token = %s",
                $tour_id,
                $session_token
            ),
            ARRAY_A
        );
        if (!$rows) {
            return [];
        }
        return $rows;
    }

    /**
     * Apply client selections to days: hide removed (non-mandatory), add added (from catalog).
     *
     * @param array $days Array of day arrays (with 'id', 'activities', etc.)
     * @param array $selections From get_selections()
     * @param int $tour_id
     * @return array Modified days (activities filtered + appended)
     */
    public function apply_to_days($days, $selections, $tour_id) {
        if (empty($selections)) {
            return $days;
        }
        $removed = [];
        $added = [];
        foreach ($selections as $row) {
            $key = (int) $row['day_id'] . '_' . (int) $row['activity_id'];
            if ($row['action'] === 'removed') {
                $removed[$key] = true;
            } else {
                $added[$key] = ['day_id' => (int) $row['day_id'], 'activity_id' => (int) $row['activity_id']];
            }
        }
        $added_by_day = [];
        foreach ($added as $a) {
            $added_by_day[$a['day_id']][] = $a['activity_id'];
        }
        $catalog = $this->get_catalog_activities(array_unique(array_column($added, 'activity_id')));

        foreach ($days as &$day) {
            $day_id = (int) $day['id'];
            $new_activities = [];
            foreach ($day['activities'] as $act) {
                $key = $day_id . '_' . (int) $act['activity_id'];
                if (!empty($removed[$key]) && empty($act['is_mandatory'])) {
                    continue;
                }
                $new_activities[] = $act;
            }
            if (!empty($added_by_day[$day_id])) {
                foreach ($added_by_day[$day_id] as $aid) {
                    $cat = isset($catalog[$aid]) ? $catalog[$aid] : [];
                    $new_activities[] = [
                        'id' => 0,
                        'activity_id' => $aid,
                        'title' => isset($cat['title']) ? $cat['title'] : '',
                        'description' => isset($cat['description']) ? $cat['description'] : '',
                        'image_url' => isset($cat['image_url']) ? $cat['image_url'] : null,
                        'activity_image_id' => isset($cat['activity_image_id']) ? $cat['activity_image_id'] : null,
                        'base_price' => isset($cat['base_price']) ? $cat['base_price'] : null,
                        'is_mandatory' => false,
                        'is_included' => true,
                        'client_added' => true,
                    ];
                }
            }
            $day['activities'] = $new_activities;
        }
        unset($day);
        return $days;
    }

    /**
     * Get activity data from catalog by ids (title, description, image_url, base_price, etc.).
     *
     * @param int[] $activity_ids
     * @return array [activity_id => ['title'=>, 'description'=>, 'image_url'=>?, 'activity_image_id'=>?, 'base_price'=>?]]
     */
    private function get_catalog_activities($activity_ids) {
        if (empty($activity_ids)) {
            return [];
        }
        $table = $this->table_catalog;
        if ($this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $table)) != $table) {
            return [];
        }
        $ids = array_map('intval', $activity_ids);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT id, title, description, image_id, base_price FROM $table WHERE id IN ($placeholders)", $ids),
            ARRAY_A
        );
        $out = [];
        foreach ($rows as $r) {
            $image_url = null;
            $image_id = isset($r['image_id']) ? (int) $r['image_id'] : 0;
            if ($image_id && function_exists('ajtb_get_attachment_image_url')) {
                $image_url = ajtb_get_attachment_image_url($image_id, 'medium');
            } elseif ($image_id && function_exists('wp_get_attachment_image_url')) {
                $image_url = wp_get_attachment_image_url($image_id, 'medium');
            }
            $out[(int) $r['id']] = [
                'title' => $r['title'] ?? '',
                'description' => $r['description'] ?? '',
                'image_url' => $image_url,
                'activity_image_id' => $image_id ?: null,
                'base_price' => isset($r['base_price']) && $r['base_price'] !== null ? (float) $r['base_price'] : null,
            ];
        }
        return $out;
    }

    /**
     * Toggle activity: add or remove for this session.
     * Refuses to remove if activity is mandatory.
     *
     * @param int $tour_id
     * @param int $day_id
     * @param int $activity_id
     * @param string $action 'added'|'removed'
     * @param string $session_token
     * @param int|null $user_id
     * @return array ['success' => bool, 'message' => ?, 'day_activities' => ?] for current day after toggle
     */
    public function toggle($tour_id, $day_id, $activity_id, $action, $session_token, $user_id = null) {
        $tour_id = (int) $tour_id;
        $day_id = (int) $day_id;
        $activity_id = (int) $activity_id;
        $action = $action === 'removed' ? 'removed' : 'added';

        if ($action === 'removed') {
            $is_mandatory = $this->is_activity_mandatory_in_day($tour_id, $day_id, $activity_id);
            if ($is_mandatory) {
                return ['success' => false, 'message' => __('Cette activité est obligatoire et ne peut pas être retirée.', 'ajinsafro-tour-bridge')];
            }
        }

        $allowed = $this->activity_allowed_for_day($tour_id, $day_id, $activity_id);
        if (!$allowed) {
            return ['success' => false, 'message' => __('Activité ou jour invalide.', 'ajinsafro-tour-bridge')];
        }

        $table = $this->table;
        if ($this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $table)) != $table) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AJTB Activity_Selections: table missing, name=' . $table . ', prefix=' . $this->wpdb->prefix);
            }
            return ['success' => false, 'message' => __('Table des sélections indisponible.', 'ajinsafro-tour-bridge')];
        }

        $this->wpdb->replace(
            $table,
            [
                'tour_id' => $tour_id,
                'day_id' => $day_id,
                'activity_id' => $activity_id,
                'source_day_activity_id' => null,
                'action' => $action,
                'session_token' => $session_token,
                'user_id' => $user_id ? (int) $user_id : null,
                'updated_at' => current_time('mysql'),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s']
        );

        $selections = $this->get_selections($tour_id, $session_token);
        $repo = new AJTB_Laravel_Repository($tour_id);
        $days = $repo->get_days();
        $days = $this->apply_to_days($days, $selections, $tour_id);
        $day_activities = [];
        foreach ($days as $d) {
            if ((int) $d['id'] === $day_id) {
                $day_activities = $d['activities'];
                break;
            }
        }

        return [
            'success' => true,
            'message' => $action === 'added' ? __('Activité ajoutée.', 'ajinsafro-tour-bridge') : __('Activité retirée.', 'ajinsafro-tour-bridge'),
            'day_activities' => $day_activities,
        ];
    }

    /**
     * Check if activity is mandatory in this day (from aj_tour_day_activities).
     */
    private function is_activity_mandatory_in_day($tour_id, $day_id, $activity_id) {
        $t = $this->table_activities;
        if ($this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $t)) != $t) {
            return false;
        }
        $v = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT is_mandatory FROM $t WHERE tour_id = %d AND day_id = %d AND activity_id = %d LIMIT 1",
            $tour_id,
            $day_id,
            $activity_id
        ));
        return (int) $v === 1;
    }

    /**
     * Activity is allowed: either in aj_tour_day_activities for this day, or exists in catalog (for add).
     */
    private function activity_allowed_for_day($tour_id, $day_id, $activity_id) {
        $t = $this->table_activities;
        $c = $this->table_catalog;
        if ($this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $t)) != $t || $this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $c)) != $c) {
            return false;
        }
        $in_day = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM $t WHERE tour_id = %d AND day_id = %d AND activity_id = %d LIMIT 1",
            $tour_id,
            $day_id,
            $activity_id
        ));
        if ($in_day) {
            return true;
        }
        $in_catalog = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM $c WHERE id = %d LIMIT 1",
            $activity_id
        ));
        return (bool) $in_catalog;
    }
}
