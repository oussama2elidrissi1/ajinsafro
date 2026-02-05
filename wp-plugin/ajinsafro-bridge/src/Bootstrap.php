<?php
/**
 * Bootstrap class - Initializes all plugin components
 *
 * @package AjinsafroBridge
 */

namespace AjinsafroBridge;

use AjinsafroBridge\Hooks\TemplateOverride;
use AjinsafroBridge\Hooks\Assets;

class Bootstrap
{
    /**
     * Singleton instance
     * @var self|null
     */
    private static $instance = null;

    /**
     * Flag to prevent double initialization
     * @var bool
     */
    private static $initialized = false;

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;
        self::$instance = new self();
    }

    /**
     * Constructor - registers all hooks
     */
    private function __construct()
    {
        $this->registerHooks();
        $this->registerFilters();

        // Debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ajinsafro Bridge: Bootstrap initialized');
        }
    }

    /**
     * Register action hooks
     *
     * @return void
     */
    private function registerHooks(): void
    {
        // Initialize template override handler
        $templateOverride = new TemplateOverride();
        add_filter('template_include', [$templateOverride, 'overrideTemplate'], 99);

        // Initialize assets handler
        $assets = new Assets();
        add_action('wp_enqueue_scripts', [$assets, 'enqueueAssets']);
    }

    /**
     * Register filters
     *
     * @return void
     */
    private function registerFilters(): void
    {
        // Filter to allow external modification of tour data
        add_filter('ajbridge_tour_data', function ($tourData, $postId) {
            return $tourData;
        }, 10, 2);

        // Filter to modify Laravel table prefix
        add_filter('ajbridge_laravel_table_prefix', function ($prefix) {
            return $prefix;
        }, 10, 1);
    }

    /**
     * Get plugin instance
     *
     * @return self|null
     */
    public static function getInstance(): ?self
    {
        return self::$instance;
    }
}
