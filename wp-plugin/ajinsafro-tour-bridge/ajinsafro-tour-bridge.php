<?php
/**
 * Plugin Name: Ajinsafro Tour Bridge
 * Plugin URI: https://ajinsafro.ma
 * Description: Clean single st_tours bridge with Ajinsafro-branded V1 UI.
 * Version: 2.0.0
 * Author: Ajinsafro
 * Author URI: https://ajinsafro.ma
 * Text Domain: ajinsafro-tour-bridge
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AJTB_VERSION', '2.0.0');
define('AJTB_PLUGIN_FILE', __FILE__);
define('AJTB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AJTB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AJTB_POST_TYPE', 'st_tours');

require_once AJTB_PLUGIN_DIR . 'includes/helpers.php';
require_once AJTB_PLUGIN_DIR . 'includes/class-tour-repository.php';
require_once AJTB_PLUGIN_DIR . 'includes/class-laravel-repository.php';
require_once AJTB_PLUGIN_DIR . 'includes/class-activity-selections.php';
require_once AJTB_PLUGIN_DIR . 'includes/class-v1-data-provider.php';
require_once AJTB_PLUGIN_DIR . 'includes/class-single-tour-page.php';

add_action('plugins_loaded', static function () {
    AJTB_Single_Tour_Page::boot();
}, 20);

register_activation_hook(__FILE__, static function (): void {
    if (class_exists('AJTB_Single_Tour_Page')) {
        AJTB_Single_Tour_Page::register_recap_endpoint();
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, static function (): void {
    flush_rewrite_rules();
});
