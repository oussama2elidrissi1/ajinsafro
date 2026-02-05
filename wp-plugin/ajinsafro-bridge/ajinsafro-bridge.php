<?php
/**
 * Plugin Name: Ajinsafro Bridge
 * Plugin URI: https://ajinsafro.ma
 * Description: Bridge plugin to display st_tours with WordPress data + Laravel extras (days, inclusions, prices) from shared database.
 * Version: 1.0.0
 * Author: Ajinsafro
 * Author URI: https://ajinsafro.ma
 * Text Domain: ajinsafro-bridge
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * This plugin overrides single-st_tours and archive-st_tours templates
 * to display combined data from WordPress (Traveler theme) and Laravel tables.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin constants
 */
define('AJBRIDGE_VERSION', '1.0.0');
define('AJBRIDGE_PLUGIN_FILE', __FILE__);
define('AJBRIDGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AJBRIDGE_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Laravel tables prefix (default: aj_)
 * Can be filtered via 'ajbridge_laravel_table_prefix'
 */
define('AJBRIDGE_LARAVEL_PREFIX', apply_filters('ajbridge_laravel_table_prefix', 'aj_'));

/**
 * Post type to override
 */
define('AJBRIDGE_POST_TYPE', 'st_tours');

/**
 * Simple PSR-4 style autoloader for plugin classes
 */
spl_autoload_register(function ($class) {
    // Plugin namespace prefix
    $prefix = 'AjinsafroBridge\\';
    $base_dir = AJBRIDGE_PLUGIN_DIR . 'src/';

    // Check if class uses our namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get relative class name
    $relative_class = substr($class, $len);

    // Convert namespace separators to directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Require file if it exists
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Initialize plugin on plugins_loaded
 */
add_action('plugins_loaded', function () {
    // Check if post type exists (Traveler theme must be active)
    if (!post_type_exists(AJBRIDGE_POST_TYPE)) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Ajinsafro Bridge:</strong> Le post type <code>st_tours</code> n\'existe pas. ';
            echo 'Assurez-vous que le thème Traveler est actif.</p>';
            echo '</div>';
        });
        // Don't stop - post type might be registered later
    }

    // Bootstrap the plugin
    \AjinsafroBridge\Bootstrap::init();
}, 20); // Priority 20 to ensure Traveler theme has registered post types

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function () {
    // Flush rewrite rules on activation
    flush_rewrite_rules();

    // Create option to track version
    update_option('ajbridge_version', AJBRIDGE_VERSION);

    // Log activation
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Ajinsafro Bridge: Plugin activated (v' . AJBRIDGE_VERSION . ')');
    }
});

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, function () {
    // Flush rewrite rules on deactivation
    flush_rewrite_rules();

    // Log deactivation
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Ajinsafro Bridge: Plugin deactivated');
    }
});

/**
 * Helper function to get tour data for current post
 * Can be used in templates: $tourData = ajbridge_get_tour_data();
 *
 * @param int|null $post_id Optional post ID, defaults to current post
 * @return array|null Tour data array or null if not found
 */
function ajbridge_get_tour_data($post_id = null) {
    if ($post_id === null) {
        $post_id = get_the_ID();
    }

    if (!$post_id) {
        return null;
    }

    $assembler = new \AjinsafroBridge\Services\TourAssembler();
    return $assembler->getTourData($post_id);
}

/**
 * Helper function to load a partial template
 *
 * @param string $partial Partial name (without .php)
 * @param array $data Data to pass to partial
 */
function ajbridge_partial($partial, $data = []) {
    $file = AJBRIDGE_PLUGIN_DIR . 'templates/tour/partials/' . $partial . '.php';

    if (file_exists($file)) {
        // Extract data to make it available in partial
        extract($data);
        include $file;
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo "<!-- Partial not found: {$partial} -->";
        }
    }
}

/**
 * Helper to get Laravel table name with prefix
 *
 * @param string $table Table name without prefix
 * @return string Full table name
 */
function ajbridge_table($table) {
    global $wpdb;
    return $wpdb->prefix . AJBRIDGE_LARAVEL_PREFIX . $table;
}
