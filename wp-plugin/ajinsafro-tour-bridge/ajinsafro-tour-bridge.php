<?php
/**
 * Plugin Name: Ajinsafro Tour Bridge
 * Plugin URI: https://ajinsafro.ma
 * Description: Override single st_tours template with custom MakeMyTrip-style design. Combines WordPress tour data with Laravel custom tables.
 * Version: 1.0.2
 * Author: Ajinsafro
 * Author URI: https://ajinsafro.ma
 * Text Domain: ajinsafro-tour-bridge
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Constants
 */
define('AJTB_VERSION', '1.0.2');
define('AJTB_PLUGIN_FILE', __FILE__);
define('AJTB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AJTB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AJTB_POST_TYPE', 'st_tours');

/**
 * Laravel table suffix prefix (use ajtb_table() for full name to avoid double prefix).
 * Tables: ajtb_table('aj_tour_days'), ajtb_table('aj_tour_activity_selections'), etc.
 */
define('AJTB_LARAVEL_PREFIX', 'aj_');

/**
 * Flights table: if Laravel uses a different DB prefix (e.g. cFdgeZ_), set in wp-config.php:
 *   define('AJTB_FLIGHTS_TABLE_PREFIX', 'cFdgeZ_');
 * Or create this file in the plugin folder with: <?php define('AJTB_FLIGHTS_TABLE_PREFIX', 'cFdgeZ_');
 */
if (file_exists(AJTB_PLUGIN_DIR . 'wp-config-ajtb.php')) {
    require_once AJTB_PLUGIN_DIR . 'wp-config-ajtb.php';
}

/**
 * Load required files
 */
require_once AJTB_PLUGIN_DIR . 'includes/helpers.php';
require_once AJTB_PLUGIN_DIR . 'includes/class-tour-repository.php';
require_once AJTB_PLUGIN_DIR . 'includes/class-laravel-repository.php';
require_once AJTB_PLUGIN_DIR . 'includes/class-activity-selections.php';
require_once AJTB_PLUGIN_DIR . 'includes/class-location-service.php';
require_once AJTB_PLUGIN_DIR . 'includes/class-template-loader.php';

/**
 * Initialize Plugin
 */
class Ajinsafro_Tour_Bridge {

    /**
     * Singleton instance
     * @var self|null
     */
    private static $instance = null;

    /**
     * Template Loader instance
     * @var AJTB_Template_Loader
     */
    public $template_loader;

    /**
     * Get singleton instance
     * @return self
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Wait for plugins_loaded to ensure theme is ready
        add_action('plugins_loaded', [$this, 'init'], 20);
        
        // Activation hook
        register_activation_hook(AJTB_PLUGIN_FILE, [$this, 'activate']);
        
        // Deactivation hook
        register_deactivation_hook(AJTB_PLUGIN_FILE, [$this, 'deactivate']);
    }

    /**
     * Initialize plugin components
     */
    public function init() {
        // Ensure selections table exists (e.g. if plugin was installed without activation, or table dropped)
        $this->ensure_selections_table();
        $this->ensure_flight_selections_table();

        // Initialize template loader
        $this->template_loader = new AJTB_Template_Loader();

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX: client toggle optional activities (add/remove)
        add_action('wp_ajax_aj_toggle_activity', [$this, 'ajax_toggle_activity']);
        add_action('wp_ajax_nopriv_aj_toggle_activity', [$this, 'ajax_toggle_activity']);

        // AJAX: get activities for modal (with images, prices)
        add_action('wp_ajax_aj_get_activities_modal', [$this, 'ajax_get_activities_modal']);
        add_action('wp_ajax_nopriv_aj_get_activities_modal', [$this, 'ajax_get_activities_modal']);

        // AJAX: update activity custom fields
        add_action('wp_ajax_aj_update_activity', [$this, 'ajax_update_activity']);
        add_action('wp_ajax_nopriv_aj_update_activity', [$this, 'ajax_update_activity']);

        // AJAX: client toggle flight (add/remove) for display
        add_action('wp_ajax_ajtb_toggle_flight', [$this, 'ajax_toggle_flight']);
        add_action('wp_ajax_nopriv_ajtb_toggle_flight', [$this, 'ajax_toggle_flight']);

        // REST API: ensure Traveler location (country + city)
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Admin notice if Traveler not active
        add_action('admin_notices', [$this, 'admin_notices']);

        // SEO: og:image on single tour (image principale > featured > gallery)
        add_action('wp_head', [$this, 'output_tour_og_image'], 5);
    }

    /**
     * Register REST routes.
     */
    public function register_rest_routes() {
        register_rest_route('ajinsafro/v1', '/ensure-location', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'rest_ensure_location'],
            'permission_callback' => [$this, 'rest_ensure_location_permission'],
            'args' => [
                'country_code' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => function ($value) {
                        return strtoupper(trim((string) $value));
                    },
                ],
                'city_name' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /**
     * REST permission callback for ensure-location.
     * Accepts either:
     * - X-WP-Nonce (wp_rest) for authenticated requests
     * - Authorization: Bearer <shared-token>
     *
     * @param WP_REST_Request $request
     * @return true|WP_Error
     */
    public function rest_ensure_location_permission($request) {
        $nonce = (string) $request->get_header('x_wp_nonce');
        if ($nonce === '') {
            $nonce = (string) $request->get_param('_wpnonce');
        }
        if ($nonce !== '' && is_user_logged_in() && wp_verify_nonce($nonce, 'wp_rest')) {
            return true;
        }

        $provided_token = $this->get_bearer_token($request);
        $shared_token = defined('AJTB_REST_BEARER_TOKEN') && AJTB_REST_BEARER_TOKEN !== ''
            ? AJTB_REST_BEARER_TOKEN
            : get_option('ajsync_webhook_secret', get_option('ajtb_rest_bearer_token', ''));
        $shared_token = (string) apply_filters('ajtb_rest_bearer_token', $shared_token);

        if ($shared_token !== '' && $provided_token !== '' && hash_equals($shared_token, $provided_token)) {
            return true;
        }

        return new WP_Error(
            'ajtb_rest_forbidden',
            __('Unauthorized request for ensure-location.', 'ajinsafro-tour-bridge'),
            ['status' => 401]
        );
    }

    /**
     * REST callback: ensure country + city location in WP.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_ensure_location($request) {
        $country_code = strtoupper(trim((string) $request->get_param('country_code')));
        $city_name = trim((string) $request->get_param('city_name'));
        $service = new AJTB_Location_Service();

        if ($country_code === '' || $city_name === '') {
            return new WP_REST_Response([
                'error' => 'country_code and city_name are required.',
            ], 400);
        }

        if (!preg_match('/^[A-Z]{2}$/', $country_code)) {
            return new WP_REST_Response([
                'error' => 'country_code must be 2 letters.',
            ], 422);
        }

        if (mb_strlen($city_name, 'UTF-8') > 255) {
            return new WP_REST_Response([
                'error' => 'city_name is too long (max: 255).',
            ], 422);
        }

        try {
            $result = $service->ensure_location($country_code, $city_name);
            return new WP_REST_Response($result, 200);
        } catch (InvalidArgumentException $e) {
            return new WP_REST_Response([
                'error' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            $service->log('error', 'REST ensure-location failed', [
                'country_code' => $country_code,
                'city_name' => $city_name,
                'error' => $e->getMessage(),
            ]);

            return new WP_REST_Response([
                'error' => 'Unable to ensure location.',
            ], 500);
        }
    }

    /**
     * Extract Bearer token from Authorization header.
     *
     * @param WP_REST_Request $request
     * @return string
     */
    private function get_bearer_token($request) {
        $authorization = (string) $request->get_header('authorization');
        if ($authorization === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authorization = (string) $_SERVER['HTTP_AUTHORIZATION'];
        }
        if ($authorization === '' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authorization = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (stripos($authorization, 'Bearer ') === 0) {
            return trim(substr($authorization, 7));
        }

        return '';
    }

    /**
     * Output og:image meta for single tour using hero image resolution.
     */
    public function output_tour_og_image() {
        if (!is_singular(AJTB_POST_TYPE)) {
            return;
        }
        $post_id = get_the_ID();
        if (!$post_id) {
            return;
        }
        $repo = new AJTB_Tour_Repository($post_id);
        $data = $repo->get_tour_data();
        if (empty($data['hero_image_url'])) {
            return;
        }
        echo '<meta property="og:image" content="' . esc_url($data['hero_image_url']) . '">' . "\n";
    }

    /**
     * Ensure aj_tour_activity_selections table exists (uses {$wpdb->prefix}aj_tour_activity_selections).
     * Called on init so add/remove activity works even if plugin was not activated.
     */
    public function ensure_selections_table() {
        global $wpdb;
        $table = ajtb_table('aj_tour_activity_selections');
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table) {
            return;
        }
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tour_id bigint(20) UNSIGNED NOT NULL,
            day_id bigint(20) UNSIGNED NOT NULL,
            activity_id bigint(20) UNSIGNED NOT NULL,
            source_day_activity_id bigint(20) UNSIGNED DEFAULT NULL,
            action varchar(20) NOT NULL DEFAULT 'added',
            session_token varchar(64) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at timestamp NULL DEFAULT NULL,
            updated_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id),
            KEY session_token (session_token),
            KEY user_id (user_id),
            KEY tour_day_session (tour_id, day_id, session_token),
            UNIQUE KEY tour_day_activity_session (tour_id, day_id, activity_id, session_token)
        ) $charset_collate;";
        dbDelta($sql);
    }

    /**
     * Ensure aj_tour_flight_selections table exists (uses {$wpdb->prefix}aj_tour_flight_selections).
     */
    public function ensure_flight_selections_table() {
        global $wpdb;
        $table = ajtb_table('aj_tour_flight_selections');
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table) {
            return;
        }
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tour_id bigint(20) UNSIGNED NOT NULL,
            flight_id bigint(20) UNSIGNED NOT NULL,
            session_token varchar(64) NOT NULL,
            action varchar(20) NOT NULL DEFAULT 'added',
            created_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id),
            KEY session_token (session_token),
            UNIQUE KEY tour_flight_session (tour_id, flight_id, session_token)
        ) $charset_collate;";
        dbDelta($sql);
    }

    /**
     * AJAX: toggle activity (added/removed) for client selection. Never modifies aj_tour_day_activities.
     * Persists in {$wpdb->prefix}aj_tour_activity_selections. Returns HTML for the day block.
     */
    public function ajax_toggle_activity() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJ TOGGLE REQUEST ' . json_encode($_POST));
        }
        check_ajax_referer('aj_tour_activity', 'nonce');
        $tour_id = isset($_POST['tour_id']) ? (int) $_POST['tour_id'] : 0;
        $day_id = isset($_POST['day_id']) ? (int) $_POST['day_id'] : 0;
        $activity_id = isset($_POST['activity_id']) ? (int) $_POST['activity_id'] : 0;
        $action = isset($_POST['toggle_action']) ? sanitize_text_field($_POST['toggle_action']) : (isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '');
        if (!in_array($action, ['added', 'removed'], true)) {
            wp_send_json_error(['message' => __('Action invalide.', 'ajinsafro-tour-bridge')]);
        }
        global $wpdb;
        $table_selections = ajtb_table('aj_tour_activity_selections');
        $show_tables_result = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_selections));
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJTB tables: wpdb->prefix=' . $wpdb->prefix . ', table_selections=' . $table_selections . ', SHOW TABLES result=' . ($show_tables_result ?: 'null'));
        }
        if ($show_tables_result != $table_selections) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_selections (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                tour_id bigint(20) UNSIGNED NOT NULL,
                day_id bigint(20) UNSIGNED NOT NULL,
                activity_id bigint(20) UNSIGNED NOT NULL,
                source_day_activity_id bigint(20) UNSIGNED DEFAULT NULL,
                action varchar(20) NOT NULL DEFAULT 'added',
                session_token varchar(64) NOT NULL,
                user_id bigint(20) UNSIGNED DEFAULT NULL,
                created_at timestamp NULL DEFAULT NULL,
                updated_at timestamp NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY session_token (session_token),
                KEY user_id (user_id),
                KEY tour_day_session (tour_id, day_id, session_token),
                UNIQUE KEY tour_day_activity_session (tour_id, day_id, activity_id, session_token)
            ) $charset_collate;";
            dbDelta($sql);
            $show_tables_after = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_selections));
            if ($show_tables_after != $table_selections) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('AJTB table create failed: ' . $table_selections);
                }
                wp_send_json_error(['message' => sprintf(__('Table selections missing: %s', 'ajinsafro-tour-bridge'), $table_selections)]);
            }
        }

        $selections = new AJTB_Activity_Selections();
        $session_token = isset($_POST['session_token']) ? sanitize_text_field($_POST['session_token']) : '';
        if ($session_token === '') {
            $session_token = $selections->get_session_token();
        }
        if (empty($tour_id) || empty($day_id) || empty($activity_id) || $session_token === '') {
            wp_send_json_error(['message' => __('Paramètres manquants.', 'ajinsafro-tour-bridge')]);
        }
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        $result = $selections->toggle($tour_id, $day_id, $activity_id, $action, $session_token, $user_id);
        if (empty($result['success'])) {
            wp_send_json_error(['message' => $result['message'] ?? __('Erreur.', 'ajinsafro-tour-bridge')]);
        }
        $day_activities = $result['day_activities'];
        $repo = new AJTB_Laravel_Repository($tour_id);
        $activities_catalog = $repo->get_activities_catalog();
        $html = ajtb_render_day_activities_html($tour_id, $day_id, $day_activities, $session_token, $activities_catalog);
        $count = is_array($day_activities) ? count(array_filter($day_activities, function ($a) {
            return !empty($a['is_included']);
        })) : 0;
        wp_send_json_success([
            'message' => $result['message'],
            'day_id' => $day_id,
            'html' => $html,
            'count' => $count,
        ]);
    }

    /**
     * AJAX: get activities for modal (with images, prices, duration).
     * Params: tour_id, day_id (to exclude already added), search, page, per_page.
     * Returns: JSON with items array, total, pagination info.
     */
    public function ajax_get_activities_modal() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJTB ajax_get_activities_modal: POST=' . json_encode($_POST));
        }
        
        $tour_id = isset($_POST['tour_id']) ? (int) $_POST['tour_id'] : 0;
        $day_id = isset($_POST['day_id']) ? (int) $_POST['day_id'] : 0;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $page = isset($_POST['page']) ? max(1, (int) $_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? max(1, min(50, (int) $_POST['per_page'])) : 12;

        if ($tour_id <= 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AJTB ajax_get_activities_modal: Tour ID missing');
            }
            wp_send_json_error(['message' => __('Tour ID manquant.', 'ajinsafro-tour-bridge')]);
        }

        $repo = new AJTB_Laravel_Repository($tour_id);
        
        // Get already added activity IDs for this day
        $exclude_ids = [];
        if ($day_id > 0) {
            $day_activities = $repo->get_days();
            foreach ($day_activities as $day) {
                if ((int) ($day['id'] ?? 0) === $day_id && !empty($day['activities'])) {
                    foreach ($day['activities'] as $act) {
                        $act_id = (int) ($act['activity_id'] ?? 0);
                        if ($act_id > 0) {
                            $exclude_ids[] = $act_id;
                        }
                    }
                    break;
                }
            }
        }

        $allowed_ids = $repo->get_tour_activity_ids();
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJTB ajax_get_activities_modal: exclude_ids=' . json_encode($exclude_ids) . ', allowed_ids=' . json_encode($allowed_ids) . ', search=' . $search);
        }

        $result = $repo->get_activities_for_modal($exclude_ids, $search, $page, $per_page, $allowed_ids);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJTB ajax_get_activities_modal: result=' . json_encode($result));
        }
        
        wp_send_json_success($result);
    }

    /**
     * AJAX: update activity custom fields (custom_title, custom_description, custom_price, start_time, end_time).
     * Params: day_activity_id, tour_id, day_id, activity_id, custom_title?, custom_description?, custom_price?, start_time?, end_time?.
     * Returns: JSON with updated HTML for the day activities list.
     */
    public function ajax_update_activity() {
        $day_activity_id = isset($_POST['day_activity_id']) ? (int) $_POST['day_activity_id'] : 0;
        $tour_id = isset($_POST['tour_id']) ? (int) $_POST['tour_id'] : 0;
        $day_id = isset($_POST['day_id']) ? (int) $_POST['day_id'] : 0;

        if ($day_activity_id <= 0 || $tour_id <= 0 || $day_id <= 0) {
            wp_send_json_error(['message' => __('Paramètres manquants.', 'ajinsafro-tour-bridge')]);
        }

        // Get update data
        $custom_title = isset($_POST['custom_title']) ? sanitize_text_field($_POST['custom_title']) : null;
        $custom_description = isset($_POST['custom_description']) ? wp_kses_post($_POST['custom_description']) : null;
        $custom_price = isset($_POST['custom_price']) && $_POST['custom_price'] !== '' ? (float) $_POST['custom_price'] : null;
        $start_time = isset($_POST['start_time']) && $_POST['start_time'] !== '' ? sanitize_text_field($_POST['start_time']) : null;
        $end_time = isset($_POST['end_time']) && $_POST['end_time'] !== '' ? sanitize_text_field($_POST['end_time']) : null;

        // Update via Laravel service
        try {
            $programService = new \App\Services\Wp\TourProgramService();
            $programService->updateDayActivity($day_activity_id, [
                'custom_title' => $custom_title === '' ? null : $custom_title,
                'custom_description' => $custom_description === '' ? null : $custom_description,
                'custom_price' => $custom_price,
                'start_time' => $start_time,
                'end_time' => $end_time,
            ]);

            // Re-render HTML
            $repo = new AJTB_Laravel_Repository($tour_id);
            $days = $repo->get_days();
            $day_activities = [];
            foreach ($days as $d) {
                if ((int) ($d['id'] ?? 0) === $day_id) {
                    $day_activities = $d['activities'] ?? [];
                    break;
                }
            }
            $activities_catalog = $repo->get_activities_catalog();
            $session_token = isset($_POST['session_token']) ? sanitize_text_field($_POST['session_token']) : '';
            if ($session_token === '') {
                $selections = new AJTB_Activity_Selections();
                $session_token = $selections->get_session_token();
            }
            $html = ajtb_render_day_activities_html($tour_id, $day_id, $day_activities, $session_token, $activities_catalog);

            wp_send_json_success([
                'message' => __('Activité mise à jour.', 'ajinsafro-tour-bridge'),
                'day_id' => $day_id,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => __('Erreur lors de la mise à jour.', 'ajinsafro-tour-bridge')]);
        }
    }

    /**
     * AJAX: toggle flight (added/removed) for client display. Never modifies aj_tour_flights.
     * Params: tour_id, flight_id, toggle_action (added|removed), nonce, session_token.
     * Returns: html (fragment of flight cards), count.
     */
    public function ajax_toggle_flight() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ajtb_tour_flight')) {
            wp_send_json_error(['message' => __('Sécurité invalide.', 'ajinsafro-tour-bridge')]);
        }
        $tour_id = isset($_POST['tour_id']) ? (int) $_POST['tour_id'] : 0;
        $flight_id = isset($_POST['flight_id']) ? (int) $_POST['flight_id'] : 0;
        $toggle_action = isset($_POST['toggle_action']) ? sanitize_text_field($_POST['toggle_action']) : '';
        $session_token = isset($_POST['session_token']) ? sanitize_text_field($_POST['session_token']) : '';

        if (!in_array($toggle_action, ['added', 'removed'], true)) {
            wp_send_json_error(['message' => __('Action invalide.', 'ajinsafro-tour-bridge')]);
        }
        if ($tour_id <= 0 || $flight_id <= 0 || $session_token === '') {
            wp_send_json_error(['message' => __('Paramètres manquants.', 'ajinsafro-tour-bridge')]);
        }

        global $wpdb;
        $table = ajtb_table('aj_tour_flight_selections');
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
            $this->ensure_flight_selections_table();
        }

        $wpdb->replace(
            $table,
            [
                'tour_id' => $tour_id,
                'flight_id' => $flight_id,
                'session_token' => $session_token,
                'action' => $toggle_action,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        $repo = new AJTB_Laravel_Repository($tour_id);
        $flights = $repo->get_flights($session_token);
        $all_flights = $repo->get_raw_flights();
        $html = ajtb_render_flights_html($tour_id, $flights, $all_flights, $session_token);
        $count = count($flights);

        wp_send_json_success([
            'html' => $html,
            'count' => $count,
        ]);
    }

    /**
     * Enqueue CSS and JS only on single st_tours
     */
    public function enqueue_assets() {
        // Only on single st_tours
        if (!is_singular(AJTB_POST_TYPE)) {
            return;
        }

        $tour_css_version = file_exists(AJTB_PLUGIN_DIR . 'assets/css/tour.css')
            ? (string) filemtime(AJTB_PLUGIN_DIR . 'assets/css/tour.css')
            : AJTB_VERSION;
        $tour_js_version = file_exists(AJTB_PLUGIN_DIR . 'assets/js/tour.js')
            ? (string) filemtime(AJTB_PLUGIN_DIR . 'assets/js/tour.js')
            : AJTB_VERSION;
        $day_plan_js_version = file_exists(AJTB_PLUGIN_DIR . 'assets/js/day-plan.js')
            ? (string) filemtime(AJTB_PLUGIN_DIR . 'assets/js/day-plan.js')
            : AJTB_VERSION;

        // Main CSS
        wp_enqueue_style(
            'ajtb-tour-css',
            AJTB_PLUGIN_URL . 'assets/css/tour.css',
            [],
            $tour_css_version
        );

        // jQuery UI Datepicker (for travel dates calendar)
        wp_enqueue_script('jquery-ui-datepicker', false, ['jquery', 'jquery-ui-core'], false, true);
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css', [], '1.13.2');

        // Main JS
        wp_enqueue_script(
            'ajtb-tour-js',
            AJTB_PLUGIN_URL . 'assets/js/tour.js',
            ['jquery', 'jquery-ui-datepicker'],
            $tour_js_version,
            true
        );
        wp_enqueue_script(
            'ajtb-day-plan-js',
            AJTB_PLUGIN_URL . 'assets/js/day-plan.js',
            ['jquery'],
            $day_plan_js_version,
            true
        );

        // Pass data to JS (ajax_url + nonces for activities and flights)
        $post_id = get_the_ID();
        $session_token = (new AJTB_Activity_Selections())->get_session_token();
        wp_localize_script('ajtb-tour-js', 'ajtbData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aj_tour_activity'),
            'flight_nonce' => wp_create_nonce('ajtb_tour_flight'),
            'session_token' => $session_token,
            'postId' => $post_id,
            'tour_id' => $post_id,
            'currency' => get_option('st_currency', 'MAD'),
            'currencySymbol' => ajtb_get_currency_symbol(),
        ]);
    }

    /**
     * Admin notices
     */
    public function admin_notices() {
        // Check if st_tours post type exists
        if (!post_type_exists(AJTB_POST_TYPE)) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Ajinsafro Tour Bridge:</strong> ';
            echo 'Le post type <code>st_tours</code> n\'existe pas. ';
            echo 'Assurez-vous que le thème Traveler est actif.</p>';
            echo '</div>';
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Store version
        update_option('ajtb_version', AJTB_VERSION);

        // Create custom tables if needed
        $this->maybe_create_tables();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Create Laravel custom tables if they don't exist (uses ajtb_table = single prefix)
     */
    private function maybe_create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table: aj_tour_days
        $table_days = ajtb_table('aj_tour_days');
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_days)) != $table_days) {
            $sql = "CREATE TABLE $table_days (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                tour_id bigint(20) UNSIGNED NOT NULL,
                day_number int(11) NOT NULL DEFAULT 1,
                title varchar(255) DEFAULT NULL,
                description longtext DEFAULT NULL,
                meals varchar(255) DEFAULT NULL,
                accommodation varchar(255) DEFAULT NULL,
                image_url varchar(500) DEFAULT NULL,
                created_at timestamp NULL DEFAULT NULL,
                updated_at timestamp NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY tour_id (tour_id),
                KEY day_number (day_number)
            ) $charset_collate;";
            dbDelta($sql);
        }

        // Table: aj_tour_sections
        $table_sections = ajtb_table('aj_tour_sections');
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_sections)) != $table_sections) {
            $sql = "CREATE TABLE $table_sections (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                tour_id bigint(20) UNSIGNED NOT NULL,
                section_key varchar(100) NOT NULL,
                content longtext DEFAULT NULL,
                sort_order int(11) NOT NULL DEFAULT 0,
                created_at timestamp NULL DEFAULT NULL,
                updated_at timestamp NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY tour_id (tour_id),
                KEY section_key (section_key)
            ) $charset_collate;";
            dbDelta($sql);
        }

        // Table: aj_tour_pricing_rules
        $table_pricing = ajtb_table('aj_tour_pricing_rules');
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_pricing)) != $table_pricing) {
            $sql = "CREATE TABLE $table_pricing (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                tour_id bigint(20) UNSIGNED NOT NULL,
                season_name varchar(100) DEFAULT NULL,
                start_date date DEFAULT NULL,
                end_date date DEFAULT NULL,
                adult_price decimal(10,2) NOT NULL DEFAULT 0,
                child_price decimal(10,2) NOT NULL DEFAULT 0,
                infant_price decimal(10,2) NOT NULL DEFAULT 0,
                is_active tinyint(1) NOT NULL DEFAULT 1,
                created_at timestamp NULL DEFAULT NULL,
                updated_at timestamp NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY tour_id (tour_id),
                KEY is_active (is_active)
            ) $charset_collate;";
            dbDelta($sql);
        }

        // Table: aj_tour_activity_selections (client add/remove optional activities; never modifies aj_tour_day_activities)
        $table_selections = ajtb_table('aj_tour_activity_selections');
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_selections)) != $table_selections) {
            $sql = "CREATE TABLE $table_selections (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                tour_id bigint(20) UNSIGNED NOT NULL,
                day_id bigint(20) UNSIGNED NOT NULL,
                activity_id bigint(20) UNSIGNED NOT NULL,
                source_day_activity_id bigint(20) UNSIGNED DEFAULT NULL,
                action varchar(20) NOT NULL DEFAULT 'added',
                session_token varchar(64) NOT NULL,
                user_id bigint(20) UNSIGNED DEFAULT NULL,
                created_at timestamp NULL DEFAULT NULL,
                updated_at timestamp NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY session_token (session_token),
                KEY user_id (user_id),
                KEY tour_day_session (tour_id, day_id, session_token),
                UNIQUE KEY tour_day_activity_session (tour_id, day_id, activity_id, session_token)
            ) $charset_collate;";
            dbDelta($sql);
        }
    }
}

/**
 * Main function to get plugin instance
 * @return Ajinsafro_Tour_Bridge
 */
function AJTB() {
    return Ajinsafro_Tour_Bridge::instance();
}

// Initialize plugin
AJTB();
