<?php
/**
 * Plugin Name: Ajinsafro Core
 * Plugin URI: https://ajinsafro.net
 * Description: Package Builder integration for Laravel booking system with TravelerWP
 * Version: 1.0.0
 * Author: Ajinsafro
 * Author URI: https://ajinsafro.net
 * Text Domain: ajinsafro-core
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('AJINSAFRO_VERSION', '1.0.0');
define('AJINSAFRO_PLUGIN_FILE', __FILE__);
define('AJINSAFRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AJINSAFRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AJINSAFRO_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Ajinsafro\\';
    $base_dir = AJINSAFRO_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Main plugin class
final class Ajinsafro_Core
{
    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_action('plugins_loaded', [$this, 'init'], 0);
        add_action('init', [$this, 'load_textdomain']);
    }

    public function init()
    {
        // Initialize components
        new Ajinsafro\Admin\Settings();
        new Ajinsafro\Frontend\Shortcode();
        new Ajinsafro\Ajax\Handler();
        new Ajinsafro\Sync\RestEndpoint();
        new Ajinsafro\Core\Assets();
    }

    public function load_textdomain()
    {
        load_plugin_textdomain(
            'ajinsafro-core',
            false,
            dirname(AJINSAFRO_PLUGIN_BASENAME) . '/languages'
        );
    }
}

// Initialize plugin
function ajinsafro_core()
{
    return Ajinsafro_Core::instance();
}

ajinsafro_core();
