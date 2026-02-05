<?php
/**
 * Plugin Name: Ajinsafro Tour Bridge
 * Plugin URI: https://ajinsafro.ma
 * Description: Override single st_tours template with custom MakeMyTrip-style design. Combines WordPress tour data with Laravel custom tables.
 * Version: 1.0.0
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
define('AJTB_VERSION', '1.0.0');
define('AJTB_PLUGIN_FILE', __FILE__);
define('AJTB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AJTB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AJTB_POST_TYPE', 'st_tours');

/**
 * Laravel tables prefix (after WP prefix)
 * Tables: {wp_prefix}aj_tour_days, {wp_prefix}aj_tour_sections, etc.
 */
define('AJTB_LARAVEL_PREFIX', 'aj_');

/**
 * Load required files
 */
require_once AJTB_PLUGIN_DIR . 'includes/helpers.php';
require_once AJTB_PLUGIN_DIR . 'includes/class-tour-repository.php';
require_once AJTB_PLUGIN_DIR . 'includes/class-laravel-repository.php';
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
        // Initialize template loader
        $this->template_loader = new AJTB_Template_Loader();

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Admin notice if Traveler not active
        add_action('admin_notices', [$this, 'admin_notices']);
    }

    /**
     * Enqueue CSS and JS only on single st_tours
     */
    public function enqueue_assets() {
        // Only on single st_tours
        if (!is_singular(AJTB_POST_TYPE)) {
            return;
        }

        // Main CSS
        wp_enqueue_style(
            'ajtb-tour-css',
            AJTB_PLUGIN_URL . 'assets/css/tour.css',
            [],
            AJTB_VERSION
        );

        // Main JS
        wp_enqueue_script(
            'ajtb-tour-js',
            AJTB_PLUGIN_URL . 'assets/js/tour.js',
            ['jquery'],
            AJTB_VERSION,
            true
        );

        // Pass data to JS
        wp_localize_script('ajtb-tour-js', 'ajtbData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ajtb_nonce'),
            'postId' => get_the_ID(),
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
     * Create Laravel custom tables if they don't exist
     */
    private function maybe_create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . AJTB_LARAVEL_PREFIX;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table: aj_tour_days
        $table_days = $prefix . 'tour_days';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_days'") != $table_days) {
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
        $table_sections = $prefix . 'tour_sections';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_sections'") != $table_sections) {
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
        $table_pricing = $prefix . 'tour_pricing_rules';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_pricing'") != $table_pricing) {
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
