<?php
/**
 * Single Tour Page bootstrap (clean V1 architecture)
 *
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

class AJTB_Single_Tour_Page
{
    /**
     * Initialize V1 single tour flow.
     */
    public static function boot(): void
    {
        add_filter('template_include', [self::class, 'override_template'], 999);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets'], 20);
        add_action('wp_ajax_ajtb_v1_toggle_activity', [self::class, 'ajax_toggle_activity']);
        add_action('wp_ajax_nopriv_ajtb_v1_toggle_activity', [self::class, 'ajax_toggle_activity']);
    }

    /**
     * Route single st_tours requests to the clean V1 template.
     */
    public static function override_template(string $template): string
    {
        if (!self::is_target_request()) {
            return $template;
        }

        $v1_template = AJTB_PLUGIN_DIR . 'templates/v1/single-st_tours.php';
        if (file_exists($v1_template)) {
            return $v1_template;
        }

        // Hard fallback if v1 template is missing.
        $legacy_wrapper = AJTB_PLUGIN_DIR . 'templates/single-st_tours.php';
        if (file_exists($legacy_wrapper)) {
            return $legacy_wrapper;
        }

        return $template;
    }

    /**
     * Load only V1 assets on single st_tours.
     */
    public static function enqueue_assets(): void
    {
        if (!self::is_target_request()) {
            return;
        }

        $css_deps = [];

        // Reuse official Ajinsafro public header/footer assets when available.
        if (defined('AJTH_URL') && defined('AJTH_DIR') && file_exists(AJTH_DIR . 'assets/css/home.css')) {
            wp_enqueue_style(
                'ajth-fontawesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                [],
                '6.4.0'
            );

            wp_enqueue_style(
                'ajth-google-fonts',
                'https://fonts.googleapis.com/css2?family=Cairo:wght@700;900&family=Noto+Sans+Arabic:wght@400;600;700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap',
                [],
                null
            );

            wp_enqueue_style(
                'ajth-home-css',
                AJTH_URL . 'assets/css/home.css',
                ['ajth-fontawesome'],
                defined('AJTH_VERSION') ? AJTH_VERSION : AJTB_VERSION
            );
            $css_deps[] = 'ajth-home-css';

            if (file_exists(AJTH_DIR . 'assets/js/home.js')) {
                wp_enqueue_script(
                    'ajth-home-js',
                    AJTH_URL . 'assets/js/home.js',
                    [],
                    defined('AJTH_VERSION') ? AJTH_VERSION : AJTB_VERSION,
                    true
                );
            }
        }

        $tour_css_version = file_exists(AJTB_PLUGIN_DIR . 'assets/css/tour.css')
            ? (string) filemtime(AJTB_PLUGIN_DIR . 'assets/css/tour.css')
            : AJTB_VERSION;
        $tour_js_version = file_exists(AJTB_PLUGIN_DIR . 'assets/js/tour.js')
            ? (string) filemtime(AJTB_PLUGIN_DIR . 'assets/js/tour.js')
            : AJTB_VERSION;

        wp_enqueue_style(
            'ajtb-tour-css',
            AJTB_PLUGIN_URL . 'assets/css/tour.css',
            $css_deps,
            $tour_css_version
        );

        wp_enqueue_script(
            'ajtb-tour-js',
            AJTB_PLUGIN_URL . 'assets/js/tour.js',
            [],
            $tour_js_version,
            true
        );

        $post_id = (int) get_queried_object_id();
        wp_localize_script('ajtb-tour-js', 'ajtbData', [
            'postId' => $post_id,
            'tourId' => $post_id,
            'tourTitle' => $post_id > 0 ? get_the_title($post_id) : '',
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'activityNonce' => wp_create_nonce('ajtb_v1_activity_toggle'),
            'activityMessages' => [
                'added' => __('Activité ajoutée à votre programme.', 'ajinsafro-tour-bridge'),
                'error' => __('Impossible d’ajouter l’activité pour le moment.', 'ajinsafro-tour-bridge'),
            ],
        ]);
    }

    /**
     * Toggle optional activity for the current user/session in V1.
     */
    public static function ajax_toggle_activity(): void
    {
        if (!class_exists('AJTB_Activity_Selections')) {
            wp_send_json_error([
                'message' => __('Service activité indisponible.', 'ajinsafro-tour-bridge'),
            ], 500);
        }

        $nonce_ok = check_ajax_referer('ajtb_v1_activity_toggle', 'nonce', false);
        if (!$nonce_ok) {
            wp_send_json_error([
                'message' => __('Requête non autorisée.', 'ajinsafro-tour-bridge'),
            ], 403);
        }

        $tour_id = isset($_POST['tour_id']) ? (int) $_POST['tour_id'] : 0;
        $day_id = isset($_POST['day_id']) ? (int) $_POST['day_id'] : 0;
        $activity_id = isset($_POST['activity_id']) ? (int) $_POST['activity_id'] : 0;
        $action = isset($_POST['activity_action']) ? sanitize_text_field((string) $_POST['activity_action']) : 'added';
        $action = $action === 'removed' ? 'removed' : 'added';

        if ($tour_id <= 0 || $day_id <= 0 || $activity_id <= 0) {
            wp_send_json_error([
                'message' => __('Paramètres incomplets pour l’activité.', 'ajinsafro-tour-bridge'),
            ], 422);
        }

        $selections = new AJTB_Activity_Selections();
        $session_token = $selections->get_session_token();
        $user_id = is_user_logged_in() ? (int) get_current_user_id() : null;

        $result = $selections->toggle($tour_id, $day_id, $activity_id, $action, $session_token, $user_id);
        if (empty($result['success'])) {
            wp_send_json_error([
                'message' => !empty($result['message']) ? (string) $result['message'] : __('Action refusée.', 'ajinsafro-tour-bridge'),
            ], 400);
        }

        wp_send_json_success([
            'message' => !empty($result['message']) ? (string) $result['message'] : __('Activité mise à jour.', 'ajinsafro-tour-bridge'),
            'day_activities' => isset($result['day_activities']) && is_array($result['day_activities']) ? $result['day_activities'] : [],
        ]);
    }

    /**
     * True when the request is for single st_tours.
     */
    private static function is_target_request(): bool
    {
        return is_singular(AJTB_POST_TYPE);
    }
}
