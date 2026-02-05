<?php
/**
 * Ajinsafro Bridge Uninstall
 *
 * Fired when the plugin is uninstalled.
 * This file is called when the user deletes the plugin from WordPress admin.
 *
 * @package AjinsafroBridge
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data on uninstall
 *
 * Note: We only remove WordPress options created by the plugin.
 * We do NOT touch Laravel tables as they are managed by Laravel.
 */

// Delete plugin options
delete_option('ajbridge_version');

// Delete any transients we may have created
delete_transient('ajbridge_tour_cache');

// Clear any cached data
wp_cache_flush();

// Log uninstall (if debugging)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Ajinsafro Bridge: Plugin uninstalled and cleaned up');
}
